import type { TicketListParams, Ticket, TicketStatus } from '../ports/ticket-repository'
import { TICKET_STATUSES } from '../ports/ticket-repository'
import type { Paginated } from '~/modules/core/utils/response'
import { toPage } from '~/modules/core/utils/response'

export type BoardColumns = Record<TicketStatus, Paginated<Ticket>>

function colunasVazias(): BoardColumns {
  return Object.fromEntries(
    TICKET_STATUSES.map(s => [s, toPage<Ticket>(null)]),
  ) as BoardColumns
}

export function useTicketBoard() {
  const { $ticketRepository } = useNuxtApp()

  const columns = useState<BoardColumns>('tickets:board', colunasVazias)

  const loading = useState<boolean>('tickets:board:loading', () => false)
  const lastFilters = useState<Omit<TicketListParams, 'status' | 'page' | 'per_page'>>(
    'tickets:board:filters',
    () => ({}),
  )

  async function load(filters: Omit<TicketListParams, 'status' | 'page' | 'per_page'> = {}): Promise<void> {
    loading.value = true
    lastFilters.value = filters

    const results = await Promise.allSettled(
      TICKET_STATUSES.map(status =>
        $ticketRepository.list({ ...filters, status, page: 1, per_page: 100 }),
      ),
    )

    for (let i = 0; i < TICKET_STATUSES.length; i++) {
      const status = TICKET_STATUSES[i]!
      const result = results[i]!

      if (result.status === 'fulfilled') {
        columns.value[status] = result.value
      }
    }

    loading.value = false
  }

  async function reload(): Promise<void> {
    await load(lastFilters.value)
  }

  async function moveTicket(
    ticket: Ticket,
    fromStatus: TicketStatus,
    toStatus: TicketStatus,
  ): Promise<string | null> {
    if (fromStatus === toStatus) return null

    columns.value[fromStatus] = {
      ...columns.value[fromStatus],
      data: columns.value[fromStatus].data.filter(t => t.id !== ticket.id),
      total: columns.value[fromStatus].total - 1,
    }
    columns.value[toStatus] = {
      ...columns.value[toStatus],
      data: [{ ...ticket, status: toStatus }, ...columns.value[toStatus].data],
      total: columns.value[toStatus].total + 1,
    }

    try {
      await $ticketRepository.update(ticket.id, { status: toStatus })
      return null
    }
    catch (error) {
      columns.value[toStatus] = {
        ...columns.value[toStatus],
        data: columns.value[toStatus].data.filter(t => t.id !== ticket.id),
        total: columns.value[toStatus].total - 1,
      }
      columns.value[fromStatus] = {
        ...columns.value[fromStatus],
        data: [ticket, ...columns.value[fromStatus].data],
        total: columns.value[fromStatus].total + 1,
      }
      return error instanceof Error ? error.message : 'Erro ao mover chamado.'
    }
  }

  /**
   * Esvazia as colunas. Usado ao encerrar a sessao: um quadro carregado por
   * um admin mostra chamados que um gestor nao enxerga.
   */
  function reset(): void {
    columns.value = colunasVazias()
    loading.value = false
    lastFilters.value = {}
  }

  return {
    columns: readonly(columns),
    loading: readonly(loading),
    load,
    reload,
    moveTicket,
    reset,
  }
}
