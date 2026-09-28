import type { Registration } from '../ports/auth-repository'

const EMAIL_MAX = 255
const NAME_MAX = 255
const PASSWORD_MIN = 8

const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

export function validateEmail(value: string): string | null {
  if (!value.trim()) {
    return 'O campo e-mail é obrigatório.'
  }

  if (!EMAIL_PATTERN.test(value.trim())) {
    return 'O campo e-mail deve ser um endereço de e-mail válido.'
  }

  if (value.trim().length > EMAIL_MAX) {
    return `O campo e-mail não pode ter mais que ${EMAIL_MAX} caracteres.`
  }

  return null
}

export function validatePassword(value: string): string | null {
  if (!value) {
    return 'O campo senha é obrigatório.'
  }

  if (value.length < PASSWORD_MIN) {
    return `O campo senha deve ter no mínimo ${PASSWORD_MIN} caracteres.`
  }

  return null
}

export function validatePasswordConfirmation(
  password: string,
  confirmation: string,
): string | null {
  if (!confirmation) {
    return 'O campo confirmação de senha é obrigatório.'
  }

  if (password !== confirmation) {
    return 'A confirmação de senha não confere.'
  }

  return null
}

export function validateName(value: string): string | null {
  if (!value.trim()) {
    return 'O campo nome é obrigatório.'
  }

  if (value.trim().length > NAME_MAX) {
    return `O campo nome não pode ter mais que ${NAME_MAX} caracteres.`
  }

  return null
}

export interface LoginDraft {
  email: string,
  password: string,
}

export type LoginErrors = Partial<Record<keyof LoginDraft, string[]>>

export function validateLogin(draft: LoginDraft): LoginErrors {
  const errors: LoginErrors = {}

  const email = validateEmail(draft.email)

  if (email) {
    errors.email = [email]
  }

  if (!draft.password) {
    errors.password = ['O campo senha é obrigatório.']
  }

  return errors
}

export type RegisterDraft = Registration

export type RegisterErrors = Partial<Record<keyof RegisterDraft, string[]>>

export function validateRegister(draft: RegisterDraft): RegisterErrors {
  const errors: RegisterErrors = {}

  const name = validateName(draft.name)

  if (name) {
    errors.name = [name]
  }

  const email = validateEmail(draft.email)

  if (email) {
    errors.email = [email]
  }

  const password = validatePassword(draft.password)

  if (password) {
    errors.password = [password]
  }

  const confirmation = validatePasswordConfirmation(
    draft.password,
    draft.password_confirmation,
  )

  if (confirmation) {
    errors.password_confirmation = [confirmation]
  }

  return errors
}

export function isValid(errors: Record<string, string[] | undefined>): boolean {
  return Object.values(errors).every(
    mensagens => !mensagens || mensagens.length === 0,
  )
}
