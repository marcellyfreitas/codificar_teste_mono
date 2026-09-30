import type { User } from '~/modules/auth/ports/auth-repository'
import type { Paginated } from '~/modules/core/utils/response'

export type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed'
export type TicketPriority = 'low' | 'medium' | 'high'

export const TICKET_STATUSES: TicketStatus[] = ['open', 'in_progress', 'resolved', 'closed']

export function nextStatus(status: TicketStatus): TicketStatus | null {
  const atual = TICKET_STATUSES.indexOf(status)

  return atual < 0 ? null : TICKET_STATUSES[atual + 1] ?? null
}

export function prevStatus(status: TicketStatus): TicketStatus | null {
  const atual = TICKET_STATUSES.indexOf(status)

  return atual <= 0 ? null : TICKET_STATUSES[atual - 1] ?? null
}

export const STATUS_LABEL: Record<TicketStatus, string> = {
  open: 'Aberto',
  in_progress: 'Em andamento',
  resolved: 'Resolvido',
  closed: 'Fechado',
}

export const PRIORITY_LABEL: Record<TicketPriority, string> = {
  low: 'Baixa',
  medium: 'Média',
  high: 'Alta',
}

export interface Ticket {
  id: number,
  protocol: string,
  title: string,
  description: string,
  status: TicketStatus,
  priority: TicketPriority,
  user_id: number,
  assignee_id: number | null,
  user: User,
  assignee: User | null,
  created_at: string,
  updated_at: string,
}

export interface TicketListParams {
  status?: TicketStatus | '',
  priority?: TicketPriority | '',
  user_id?: number | null,
  assignee_id?: number | null,
  search?: string,
  created_from?: string,
  created_to?: string,
  page?: number,
  per_page?: number,
}

export interface TicketCreateData {
  title: string,
  description: string,
  priority: TicketPriority,
  status?: TicketStatus,
  assignee_id?: number | null,
  auto_assign?: boolean,
}

export interface TicketUpdateData {
  title?: string,
  description?: string,
  priority?: TicketPriority,
  status?: TicketStatus,
  assignee_id?: number | null,
}

export interface BalanceResult {
  distributed: number,
  kept_open_untouched: number,
  gestores: number,
  min_open: number,
  max_open: number,
  difference: number,
  load_by_gestor: Record<string, number>,
}

export interface TicketRepository {
  list(params?: TicketListParams): Promise<Paginated<Ticket>>,
  get(id: number): Promise<Ticket>,
  create(data: TicketCreateData): Promise<Ticket>,
  update(id: number, data: TicketUpdateData): Promise<Ticket>,
  destroy(id: number): Promise<void>,
  balance(): Promise<BalanceResult>,
  unassignOpen(): Promise<{ affected: number }>,
}
