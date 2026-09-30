import type { User, UserRole } from '~/modules/auth/ports/auth-repository'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'

export const isAdmin = (u: Pick<User, 'role'>): boolean => u.role === 'admin'

export const isManager = (u: Pick<User, 'role'>): boolean =>
  u.role === 'gestor' || u.role === 'admin'

/**
 * O usuário comum edita o próprio chamado, mas só enquanto ele está aberto.
 * Espelha a policy `update`: gestor e admin não dependem de autor nem status.
 */
export function canEditTicket(
  u: Pick<User, 'id' | 'role'>,
  t: Pick<Ticket, 'user_id' | 'status'>,
): boolean {
  if (isManager(u)) {
    return true
  }

  return t.status === 'open' && String(t.user_id) === String(u.id)
}

export const canAdvanceTicket = isManager

export const canDeleteTicket = isAdmin

export const canMoveTicket = isManager

export const canManageQueue = isAdmin

export const assignableRoles: UserRole[] = ['gestor']
