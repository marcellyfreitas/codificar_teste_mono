import type { User } from '../ports/user-directory'

/** Retorna as iniciais do nome (máx. 2 letras). */
export function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0]?.toUpperCase() ?? '')
    .join('')
}

/** Nome curto: primeiro + último nome. */
export function shortName(user: User): string {
  const parts = user.name.trim().split(/\s+/)

  if (parts.length <= 1) {
    return user.name
  }

  return `${parts[0]} ${parts[parts.length - 1]}`
}

/** Label para exibição no seletor. */
export function displayLabel(user: User): string {
  return shortName(user)
}
