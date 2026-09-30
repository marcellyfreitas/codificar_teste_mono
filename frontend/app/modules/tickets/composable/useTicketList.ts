import type { Paginated } from '~/modules/core/utils/response'
import { toPage } from '~/modules/core/utils/response'
import type { Ticket, TicketListParams } from '../ports/ticket-repository'

export function useTicketList() {
  const { $ticketRepository } = useNuxtApp()

  const page = useState<Paginated<Ticket>>('tickets:list', () => toPage<Ticket>(null))
  const loading = useState<boolean>('tickets:list:loading', () => false)
  const error = useState<string | null>('tickets:list:error', () => null)
  const lastParams = useState<TicketListParams>('tickets:list:params', () => ({}))

  async function load(params: TicketListParams = {}): Promise<void> {
    loading.value = true
    error.value = null
    lastParams.value = params

    try {
      page.value = await $ticketRepository.list(params)
    }
    catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao carregar chamados.'
    }
    finally {
      loading.value = false
    }
  }

  async function reload(): Promise<void> {
    await load(lastParams.value)
  }

  /**
   * Devolve o estado ao valor inicial. Usado ao encerrar a sessao: a pagina
   * guarda chamado de um usuario escopado a sessao anterior.
   */
  function reset(): void {
    page.value = toPage<Ticket>(null)
    loading.value = false
    error.value = null
    lastParams.value = {}
  }

  return {
    page: readonly(page),
    loading: readonly(loading),
    error: readonly(error),
    load,
    reload,
    reset,
  }
}
