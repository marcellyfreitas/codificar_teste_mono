# Sistema de Controle de Chamados Internos

API REST para gestão de chamados de suporte e manutenção interna, com **distribuição
automática de carga** entre os responsáveis.

Laravel 13 · PHP 8.4.1+ · Laravel Sanctum · SQLite (dev/teste) ou MySQL 8 (produção) ·
Pest 5

---

## O que o sistema faz

O atendimento interno costuma se espalhar por WhatsApp, e-mail e papel: ninguém sabe quem
está sobrecarregado, chamados se perdem no caminho e a distribuição depende de alguém
lembrar de delegar. Aqui o chamado nasce com responsável definido pelo sistema, escolhido
por quem tem menos trabalho pendente.

- **Chamados** com ciclo de vida completo: abertura, edição, atribuição, resolução e
  exclusão.
- **Atribuição automática** no momento da abertura, por carga de trabalho. O responsável
  também pode ser informado manualmente, ou o chamado ir para a fila de espera.
- **Balanceamento de fila**: redistribui os chamados sem responsável entre os gestores, um a
  um, para o menos carregado. Há também operação para esvaziar todos os responsáveis dos
  chamados abertos e redistribuir do zero.
- **Filtros e busca** por status, prioridade, autor, responsável e texto livre, com
  paginação.
- **Três níveis de permissão** e autenticação por token.

Excluir um chamado é *soft delete*: ele some das respostas e deixa de contar na carga, mas
o histórico de quem atendeu permanece para auditoria.

## Modelo de domínio

### Carga de trabalho

Carga de um gestor é a quantidade de chamados **atribuídos a ele** em status `open` +
`in_progress` — os dois estados que ainda exigem trabalho. `resolved` e `closed` não
contam. Toda distribuição, automática ou por balanceamento, elege o gestor de menor carga,
com desempate pelo menor `id` para ser determinística quando todos estão igualmente
ociosos.

### Os dois usuários de um chamado

Um chamado referencia `users` duas vezes, e as duas referências têm contratos diferentes:

| Campo | Papel | Origem | Nulo? |
|---|---|---|---|
| `user_id` | **autor** — quem requisitou | sempre o token de quem abre | não |
| `assignee_id` | **responsável** — quem executa | sorteado, enviado no payload, ou esvaziado | sim (fila) |

São eixos independentes. "O que eu abri" e "o que está comigo" são consultas distintas, e
cada uma tem filtro próprio.

**O autor vem exclusivamente do token.** O campo não tem regra de validação: enviá-lo no
payload é descartado em silêncio, e a autoria é imutável no update. É a mesma razão que
`role` não ser preenchível no cadastro — se qualquer campo de identidade viesse do body,
qualquer autenticado abriria chamado em nome de outro. O autor pode ser de qualquer papel.

**Só gestor executa chamado.** O responsável precisa ter papel `gestor` ou `admin`, o que a
regra `ExistsAsGestor` valida no Form Request; um usuário comum leva `422`. Isso impede que
um pedido vire tarefa de quem o fez, e mantém a métrica de carga sobre pessoas efetivamente
capazes de atendê-lo. Um gestor pode ser autor e responsável do mesmo chamado.

**A fila** é o estado `assignee_id` nulo. Fica assim quando o chamado é aberto com
atribuição desligada, quando um gestor esvazia o responsável, ou quando a fila é zerada em
lote. O balanceamento consome a fila; não existe filtro para "sem responsável", porque a
comparação é numérica.

### Papéis

`user` < `gestor` < `admin`, como enum *backed by string* com cast no model. `admin` é
também gestor. O papel de execução é o que habilita alguém como responsável, e o papel de
administração é o que habilita a redistribuição da fila.

## Desenho técnico

**API sem estado.** Não há `routes/web.php`, view, Blade ou Vite, então o middleware de
sessão e CSRF fica fora do caminho das requisições. Cada chamada é autocontida e qualquer
instância atende qualquer requisição.

**Services para a regra de negócio.** Os controllers só recebem request, delegam e
formatam. Distribuição, cálculo de carga e filtro de listagem vivem em `TicketService`.
As duas operações que atuam sobre a fila inteira — balancear e esvaziar responsáveis —
são Actions invocáveis, porque não pertencem ao CRUD de um recurso e assim recebem nome
próprio em vez de inflar o service.

**Enums no lugar de strings.** `TicketStatus`, `TicketPriority` e `UserRole` definem os
domínios válidos e os rótulos de exibição. A definição do que conta como carga está em
`TicketStatus::openStatuses()`, então a regra existe num método nomeado em vez de um
`whereIn` repetido.

**Transação em toda mutação.** Criação, edição e exclusão rodam dentro de
`DB::transaction()` com `try/catch` e log. O balanceamento repete até 3 vezes em caso de
deadlock.

**Concorrência na distribuição.** A escolha do responsável menos carregado trava os gestores
com `lockForUpdate()` dentro da transação, para que dois chamados simultâneos não sejam
atribuídos ao mesmo gestor sem considerar o primeiro. O balanceamento processa do chamado
mais antigo para o mais novo e incrementa a carga em memória a cada distribuição, o que
equivale ao que o fluxo normal produziria.

**A carga conta atribuições.** A contagem usa `withCount` sobre a relação de chamados
**tratados**, nunca sobre a de chamados **abrir** — abrir muitos chamados não deixa
ninguém mais ocupado, e medir pela relação errada distribuiria pelo volume de pedidos que
a pessoa fez, sem falhar em lugar nenhum observável. O mesmo vale para o agrupamento do
balanceamento.

**Permissão é papel, não vínculo.** As abilities por recurso ficam na policy, que nega
com mensagem legível em vez de erro genérico. As duas operações sobre a fila não têm um
chamado em mãos, e o Gate do Laravel só resolve policy quando o primeiro argumento é um
modelo — por isso são registradas como abilities e checadas pelo nome. Uma consequência de
montagem: a checagem de autorização precisa ficar **fora** do `try/catch`, senão a exceção é
capturada pelo `catch` genérico e o cliente recebe `500` em vez de `403`.

**Validação em português.** Form Requests com mensagens e nomes de campo próprios, e o
payload de erro devolvido com os mesmos nomes. O campo de responsável passa por uma regra
própria (`ExistsAsGestor`), que é uma restrição de dado, não de permissão.

## Dados

Cinco migrations, todas de criação.

- `users` — papel como `enum` indexado, com `user` como padrão.
- `tickets` — `user_id` e `assignee_id` para `users`, com contratos de exclusão
  **assimétricos**: `restrictOnDelete` no autor, para que apagar o solicitante não deixe o
  chamado órfão, e `nullOnDelete` no responsável, para que a saída de uma pessoa devolva a
  carga à fila em vez de sumir com ela. Na prática a exclusão só passa para quem nunca
  abriu um chamado.
- Índices em `(assignee_id, status)` — sustenta a contagem de carga — e em `user_id`, para
  o filtro de autoria.
- `personal_access_tokens` para o Sanctum.

## Como executar

### Setup completo (ambos os lados)

```bash
# 1. Backend
cd backend
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
# deixa rodando: http://localhost:8000/api/v1

# 2. Frontend (em outro terminal)
cd frontend
npm install
cp .env.example .env
npm run dev
# http://localhost:3000
```

### Só o backend

```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve                      # http://localhost:8000/api/v1
```

O seeder popula os três papéis e 1000 chamados, 70% deles ainda em aberto para que o
balanceamento tenha dado significativo. Todos com a senha `password`, e três contas de
papel fixo para conveniently exercitar cada nível de permissão: `test@example.com`,
`gestor@example.com` e `admin@example.com`.

A API responde sob o prefixo `/api/v1`, e o health check em `/up`.

## Frontend

Nuxt 4 · Tailwind CSS v4 · shadcn-vue · Vue Draggable Plus · @vueuse/core

O frontend consome a API pelo proxy do Nuxt — todas as chamadas saem de `/api/v1/**`
e são repassadas para o backend configurado em `NUXT_API_ORIGIN`. **Não há CORS
configurado no backend**; sem o proxy o browser não consegue chamar a API diretamente.

### Rotas

| Rota | Guard | Descrição |
|---|---|---|
| `/login` | guest | Formulário de login |
| `/register` | guest | Cadastro de conta |
| `/chamados` | auth | Lista e quadro Kanban de chamados |
| `/fila` | admin | Operações de distribuição de fila |

### Papéis e permissões

| Ação | user | gestor | admin |
|---|---|---|---|
| Abrir chamado | ✓ | ✓ | ✓ |
| Editar chamado | — | ✓ | ✓ |
| Excluir chamado | — | ✓ | ✓ |
| Ser responsável | — | ✓ | — |
| Operar a fila | — | — | ✓ |

> **Atenção:** `admin` pode editar e excluir, mas **não pode ser responsável** por um chamado.
> A regra `ExistsAsGestor` do backend aceita apenas `role = gestor` como `assignee_id`.
> O `README.md` antigo dizia "gestor ou admin" aqui e estava errado — confiar no código.

### Métricas de distribuição

`POST /api/v1/tickets/balance` devolve:

- `distributed` — chamados distribuídos nesta execução
- `difference` — `max_open - min_open` por gestor. `0` = distribuição perfeita.
- `min_open` / `max_open` — faixa de carga entre gestores
- `load_by_gestor` — `Record<id, number>` com 50 entradas (uma por gestor)

O balanceamento é idempotente: rodar duas vezes sem mudança entre as execuções não altera nada.

### Nota sobre o bundle

`npx shadcn-vue add --all` instala ~70 componentes (peers incluem `@tanstack/vue-table`,
`chart.js`, `embla-carousel-vue`, `vee-validate`, `zod`, `date-fns`). O app usa
uma fração disso; o Nuxt/Vite descarta o restante no build final via tree-shaking.

## Rotas da API (completo)

## Testes

```bash
cd backend
php artisan test
```

**118 testes, 332 assertions.** Suíte com Pest, no estilo funcional, com os nomes
declarando o comportamento esperado.

Os testes sobem a aplicação de verdade, com migrations em SQLite em memória
(`RefreshDatabase` declarado por arquivo) e exercitam a API por HTTP. Não há mock de regra
de negócio: o balanceamento só significa algo quando a consulta real, a transação e o
filtro de soft delete acontecem de fato, e um dublê daria sinal falso de segurança. O
custo aceito é uma suíte mais lenta e que falha de forma mais ruidosa quando o defeito
está longe da causa.

A cobertura é de regra de negócio e fronteira de permissão: a métrica de carga e seu
desempate, a idempotência do balanceamento, a diferença entre autor e responsável, a
imutabilidade da autoria, a matriz de permissão por papel, e as respostas de erro.


```
POST   /api/v1/register                → 201  {message, data:{user, access_token, token_type}}
POST   /api/v1/login                   → 200  idem
GET    /api/v1/me                      → 200  {data: User}
GET    /api/v1/users                   → 200  paginador (filtros: role, search, per_page)
GET    /api/v1/tickets                 → 200  paginador (filtros: status, priority, user_id, assignee_id, search, created_from, created_to)
POST   /api/v1/tickets                 → 201  {message, data: Ticket}
GET    /api/v1/tickets/{id}            → 200  {data: Ticket}
PUT    /api/v1/tickets/{id}            → 200  {message, data: Ticket}   gestor|admin
DELETE /api/v1/tickets/{id}            → 204  sem corpo                 gestor|admin
POST   /api/v1/tickets/balance         → 200  {message, data:{...}}     admin
POST   /api/v1/tickets/unassign-open   → 200  {message, data:{affected}} admin
```

Erros: `{message, errors?: {campo: [mensagem]}}`.
- `401` — `Unauthenticated.`
- `403` — mensagem da policy
- `422` — erros por campo (mensagens em português)

## Testes

```bash
cd backend
php artisan test        # 145 testes, ~3s
./vendor/bin/pint --test
```
