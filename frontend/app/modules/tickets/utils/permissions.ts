import type { User, UserRole } from '~/modules/auth/ports/auth-repository'

export const isAdmin = (u: Pick<User, 'role'>): boolean => u.role === 'admin'

export const isManager = (u: Pick<User, 'role'>): boolean =>
  u.role === 'gestor' || u.role === 'admin'

export const canEditTicket = isManager

export const canDeleteTicket = isManager

export const canManageQueue = isAdmin

export const assignableRoles: UserRole[] = ['gestor']
