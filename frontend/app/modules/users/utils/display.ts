import type { User } from '../ports/user-directory'

export function initials(name: string): string {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0]?.toUpperCase() ?? '')
    .join('')
}

export function shortName(user: User): string {
  const parts = user.name.trim().split(/\s+/)

  if (parts.length <= 1) {
    return user.name
  }

  return `${parts[0]} ${parts[parts.length - 1]}`
}

export function displayLabel(user: User): string {
  return shortName(user)
}
