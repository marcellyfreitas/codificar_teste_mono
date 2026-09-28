import type { HttpPort } from '~/modules/core/ports/http'
import type { ApiEnvelope, Paginated } from '~/modules/core/utils/response'
import type {
  Ticket,
  TicketCreateData,
  TicketListParams,
  TicketRepository,
  TicketUpdateData,
  BalanceResult,
} from '../ports/ticket-repository'

export class HttpTicketRepository implements TicketRepository {
  constructor(private readonly http: HttpPort) {}

  async list(params: TicketListParams = {}): Promise<Paginated<Ticket>> {
    return await this.http.get<Paginated<Ticket>>('/tickets', {
      status: params.status || undefined,
      priority: params.priority || undefined,
      user_id: params.user_id ?? undefined,
      assignee_id: params.assignee_id ?? undefined,
      search: params.search || undefined,
      created_from: params.created_from || undefined,
      created_to: params.created_to || undefined,
      page: params.page,
      per_page: params.per_page,
    })
  }

  async get(id: number): Promise<Ticket> {
    const envelope = await this.http.get<ApiEnvelope<Ticket>>(`/tickets/${id}`)
    return envelope.data
  }

  async create(data: TicketCreateData): Promise<Ticket> {
    const envelope = await this.http.post<ApiEnvelope<Ticket>>('/tickets', data)
    return envelope.data
  }

  async update(id: number, data: TicketUpdateData): Promise<Ticket> {
    const envelope = await this.http.put<ApiEnvelope<Ticket>>(`/tickets/${id}`, data)
    return envelope.data
  }

  async destroy(id: number): Promise<void> {
    await this.http.delete(`/tickets/${id}`)
  }

  async balance(): Promise<BalanceResult> {
    const envelope = await this.http.post<ApiEnvelope<BalanceResult>>('/tickets/balance')
    return envelope.data
  }

  async unassignOpen(): Promise<{ affected: number }> {
    const envelope = await this.http.post<ApiEnvelope<{ affected: number }>>('/tickets/unassign-open')
    return envelope.data
  }
}
