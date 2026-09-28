export type UserRole = 'user' | 'gestor' | 'admin'

export interface User {
  id: number,
  name: string,
  email: string,
  email_verified_at: string | null,
  role: UserRole,
  created_at: string,
  updated_at: string,
}

export interface Session {
  user: User,
  token: string,
}

export interface Credentials {
  email: string,
  password: string,
}

export interface Registration {
  name: string,
  email: string,
  password: string,
  password_confirmation: string,
}

export interface AuthRepository {
  login(credentials: Credentials): Promise<User>,
  register(payload: Registration): Promise<User>,
  me(): Promise<User>,
}
