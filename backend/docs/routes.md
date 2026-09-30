# Rotas da API

Base URL: `http://localhost:8000/api/v1`

Autenticação: `Authorization: Bearer <token>` em todas as rotas marcadas com 🔒.

---

## Autenticação

### `POST /register`

Cria uma nova conta com papel `user`.

**Body**
```json
{
  "name": "string (máx. 255)",
  "email": "string (e-mail único, máx. 255)",
  "password": "string (mín. 8)",
  "password_confirmation": "string"
}
```

**Resposta `201`**
```json
{
  "message": "Usuário cadastrado com sucesso.",
  "data": {
    "user": { "id": 1, "name": "...", "email": "...", "role": "user", ... },
    "access_token": "<token>",
    "token_type": "Bearer"
  }
}
```

---

### `POST /login`

Autentica um usuário existente.

**Body**
```json
{
  "email": "string",
  "password": "string"
}
```

**Resposta `200`** — mesmo formato de `/register`.

> Credenciais inválidas retornam `422` com `errors.email`.

---

### 🔒 `GET /me`

Retorna o usuário autenticado.

**Resposta `200`**
```json
{
  "data": { "id": 1, "name": "...", "email": "...", "role": "admin", ... }
}
```

---

## Usuários

### 🔒 `GET /users`

Lista usuários. Acessível a qualquer papel autenticado.

**Query params**

| Parâmetro  | Tipo   | Descrição                                      |
|------------|--------|------------------------------------------------|
| `role`     | string | Filtra por papel: `user`, `gestor` ou `admin`  |
| `search`   | string | Busca em `name` ou `email` (LIKE)              |
| `per_page` | int    | Itens por página — clamp 1–100, padrão 15      |
| `page`     | int    | Número da página                               |

**Resposta `200`** — paginador padrão do Laravel.

> `role` inválido retorna `422`.

---

## Chamados

### 🔒 `GET /tickets`

Lista chamados do sistema com paginação, **já filtrados pelo papel de quem pede**.

| Papel    | Chamados visíveis                          |
|----------|--------------------------------------------|
| `user`   | apenas os que ele abriu (`user_id`)         |
| `gestor` | apenas os que ele trata (`assignee_id`)     |
| `admin`  | todos                                      |

O filtro de papel entra na consulta, junto com os filtros abaixo — nunca no
collection depois da paginação. Passar `user_id` ou `assignee_id` de outra pessoa
devolve lista vazia, e não o chamado alheio.

**Query params**

| Parâmetro      | Tipo   | Descrição                                                    |
|----------------|--------|--------------------------------------------------------------|
| `status`       | string | `open`, `in_progress`, `resolved` ou `closed`               |
| `priority`     | string | `low`, `medium` ou `high`                                    |
| `user_id`      | int    | Filtra pelo autor                                            |
| `assignee_id`  | int    | Filtra pelo responsável                                      |
| `search`       | string | Busca em `title` ou `description` (LIKE)                    |
| `created_from` | date   | Data inicial — formato `YYYY-MM-DD`, inclusivo               |
| `created_to`   | date   | Data final — formato `YYYY-MM-DD`, inclusivo                 |
| `per_page`     | int    | Itens por página — clamp 1–100, padrão 15                    |
| `page`         | int    | Número da página                                             |

> Filtros inválidos (`user_id` não numérico, datas malformadas) são ignorados silenciosamente.

**Resposta `200`** — paginador padrão com objetos `Ticket` (inclui `user` e `assignee` embutidos).

---

### 🔒 `POST /tickets`

Cria um chamado. O autor é sempre o usuário do token.

**Body**
```json
{
  "title": "string (obrigatório, máx. 255)",
  "description": "string (obrigatório)",
  "priority": "low | medium | high (obrigatório)",
  "status": "open | in_progress | resolved | closed (opcional)",
  "assignee_id": "int | null (opcional — somente gestores)",
  "auto_assign": "boolean (opcional, padrão true)"
}
```

> Se `auto_assign` for `true` e `assignee_id` não for informado, o sistema atribui ao gestor com menor carga.
> `assignee_id` com papel `admin` retorna `422`.

**Resposta `201`**
```json
{
  "message": "Chamado criado com sucesso.",
  "data": { <Ticket> }
}
```

---

### 🔒 `GET /tickets/{id}`

Retorna um chamado pelo ID, se ele for visível para o papel de quem pede (mesma
regra da listagem).

**Resposta `200`**
```json
{
  "data": { <Ticket> }
}
```

**Erros**
- `403` — chamado existe mas não é visível para o papel:
  - `user` — `"Você só vê os chamados que abriu."`
  - `gestor` — `"Gestores só veem os chamados sob sua responsabilidade."`
- `404` — chamado não existe

---

### 🔒 `PUT /tickets/{id}` — gestor ou admin

Atualiza campos do chamado. Todos os campos são opcionais (parcial).

**Body**
```json
{
  "title": "string (opcional, máx. 255)",
  "description": "string (opcional)",
  "priority": "low | medium | high (opcional)",
  "status": "open | in_progress | resolved | closed (opcional)",
  "assignee_id": "int | null (opcional)"
}
```

**Resposta `200`**
```json
{
  "message": "Chamado atualizado com sucesso.",
  "data": { <Ticket> }
}
```

**Erros**
- `403` — usuário sem permissão: `"Apenas gestores e administradores podem editar chamados."`

---

### 🔒 `DELETE /tickets/{id}` — gestor ou admin

Exclui um chamado (soft delete).

**Resposta `204`** — sem corpo.

**Erros**
- `403` — usuário sem permissão: `"Apenas gestores e administradores podem excluir chamados."`

---

### 🔒 `POST /tickets/balance` — admin

Distribui todos os chamados sem responsável entre os gestores, um a um, para o menos carregado. Idempotente.

**Resposta `200`**
```json
{
  "message": "Redistribuição concluída.",
  "data": {
    "distributed": 703,
    "kept_open_untouched": 0,
    "gestores": 50,
    "min_open": 14,
    "max_open": 15,
    "difference": 1,
    "load_by_gestor": { "1": 14, "2": 15 }
  }
}
```

| Campo                  | Descrição                                              |
|------------------------|--------------------------------------------------------|
| `distributed`          | Chamados distribuídos nesta execução                   |
| `kept_open_untouched`  | Chamados sem responsável fora dos status abertos       |
| `gestores`             | Total de gestores no sistema                           |
| `min_open`             | Menor carga entre os gestores após distribuição        |
| `max_open`             | Maior carga entre os gestores após distribuição        |
| `difference`           | `max_open - min_open` — 0 = distribuição perfeita      |
| `load_by_gestor`       | Carga de cada gestor pelo ID após distribuição         |

**Erros**
- `403` — `"Apenas administradores podem redistribuir os chamados."`

---

### 🔒 `POST /tickets/unassign-open` — admin

Remove o responsável de todos os chamados com status `open`.

Chamados `in_progress`, `resolved` e `closed` mantêm o responsável.

**Resposta `200`**
```json
{
  "message": "Responsáveis removidos dos chamados abertos.",
  "data": { "affected": 705 }
}
```

**Erros**
- `403` — `"Apenas administradores podem remover os responsáveis."`

---

## Respostas de erro

| Status | Situação                             | Formato                                      |
|--------|--------------------------------------|----------------------------------------------|
| `401`  | Token ausente ou inválido            | `{ "message": "Unauthenticated." }`          |
| `403`  | Sem permissão                        | `{ "message": "<mensagem da policy>" }`      |
| `404`  | Recurso não encontrado               | `{ "message": "No query results..." }`       |
| `422`  | Falha de validação                   | `{ "message": "...", "errors": { campo: [msgs] } }` |
| `500`  | Erro interno                         | `{ "message": "...", "error": "..." }`       |

---

## Modelo `Ticket`

```json
{
  "id": 1,
  "title": "string",
  "description": "string",
  "status": "open | in_progress | resolved | closed",
  "priority": "low | medium | high",
  "user_id": 5,
  "assignee_id": 3,
  "user": { <User> },
  "assignee": { <User> } | null,
  "created_at": "ISO 8601",
  "updated_at": "ISO 8601"
}
```

## Modelo `User`

```json
{
  "id": 1,
  "name": "string",
  "email": "string",
  "role": "user | gestor | admin",
  "email_verified_at": "ISO 8601" | null,
  "created_at": "ISO 8601",
  "updated_at": "ISO 8601"
}
```
