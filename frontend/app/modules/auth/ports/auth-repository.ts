/** Papel do usuário. Espelha o enum `UserRole` do backend. */
export type UserRole = 'user' | 'gestor' | 'admin'

/** Usuário como a API o devolve. */
export interface User {
  id: number,
  name: string,
  email: string,
  email_verified_at: string | null,
  role: UserRole,
  created_at: string,
  updated_at: string,
}

/** Resposta de `POST /login` e de `POST /register`. */
export interface Session {
  user: User,
  token: string,
}

export interface Credentials {
  email: string,
  password: string,
}

/**
 * O campo `role` NÃO é enviado no cadastro: o Form Request do backend ignora
 * qualquer papel que venha no body, e o banco aplica o default `user`.
 * Expor o campo na UI daria a ilusão de que dá para se cadastrar como gestor.
 */
export interface Registration {
  name: string,
  email: string,
  password: string,
  password_confirmation: string,
}

/**
 * Autenticação como a UI enxerga. A implementação HTTP fica no adapter; um
 * teste pode trocar por um dublê sem tocar em componente.
 */
export interface AuthRepository {
  login(credentials: Credentials): Promise<Session>,
  register(payload: Registration): Promise<Session>,
  me(): Promise<User>,
}
