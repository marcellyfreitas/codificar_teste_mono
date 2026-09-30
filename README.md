# Sistema de Controle de Chamados Internos

API REST para gestão de chamados de suporte e manutenção interna, com **distribuição automática de carga** entre os responsáveis.

**Laravel 13 · PHP 8.4 · Nuxt 4 com Vue 3**

---

## O que o sistema faz

O atendimento interno costuma se espalhar por WhatsApp, e-mail e papel: ninguém sabe quem está sobrecarregado, chamados se perdem no caminho e a distribuição depende de alguém lembrar de delegar.

Aqui o chamado nasce com responsável definido pelo sistema, escolhido por quem tem menos trabalho pendente.

* **Chamados:** com ciclo de vida completo: abertura, edição, atribuição, resolução e exclusão.
* **Atribuição automática:** no momento da abertura, por carga de trabalho. O responsável também pode ser informado manualmente, ou o chamado ir para a fila de espera.
* **Balanceamento de fila:** redistribui os chamados sem responsável entre os gestores, um a um, para o menos carregado. Há também operação para esvaziar todos os responsáveis dos chamados abertos e redistribuir do zero.
* **Três níveis de permissão:** e autenticação por token.

---

## Papéis

`user` < `gestor` < `admin`

* **Usuário:** cria ticket.
* **Usuário gestor:** visualiza, avança e retorna ticket por etapas.
* **Administração:** pode fazer tudo, inclusive editar e remover o ticket.
* **Visualização dos tickets:** respeita as regras de permissão:

  * `user` pode visualizar tickets criados por ele.
  * `gestor` visualiza tickets que ele é responsável.
  * `admin` pode ver todos.

---

## Como executar

### Setup backend

```bash
cd backend

composer install

cp .env.example .env && php artisan key:generate

touch database/database.sqlite

php artisan migrate --seed

php artisan serve

# http://localhost:8000
```

### Setup frontend

```bash
cd frontend

npm install

cp .env.example .env

npm run dev

# http://localhost:3000
```

---

## Credenciais de acesso

Os seguintes usuários são adicionados ao banco de dados com o comando de seeder:

* **Usuário comum:** `test@example.com`
* **Usuário gestor:** `gestor@example.com`
* **Usuário admin:** `admin@example.com`

**Senha geral:** `password`

---

## Decisões técnicas

### Backend e frontend separados

* Tendo em vista uma equipe com papéis específicos de back e front, a decisão de trabalhar em projetos separados pode facilitar para que cada time tenha mais autonomia para executar suas tarefas.
* A decisão de criar um "monorepo" foi tomada para facilitar a entrega do teste. Em um caso real, provavelmente escolheria criar dois repositórios separados.
* Com a separação de back e front, o frontend se torna mais escalável e flexível, podendo consumir a API REST do back em qualquer linguagem, inclusive em uma aplicação mobile.

### Estrutura base 
* O backend usa a estrutura de MVC que vem com o Laravel por padrão, mas tomei a decisão de adicionar uma camada extra de Services para concentrar regras de negócio e não sobrecarregar os controllers.
* No frontend, o uso do framework Nuxt facilita em regras de roteamento, e o uso de rotas proxy para esconder a URL da API original. Deixando o projeto mais seguro e fácil de dar manutenção.
* No frontend, a arquitetura adotada foi a hexagonal. Arquitetura que combina bem com o front e, por ser modular, pode ser fácil de escalonar.
