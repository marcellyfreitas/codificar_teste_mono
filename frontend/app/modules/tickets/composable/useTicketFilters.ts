import { useDebounce } from '@vueuse/core'
import type { TicketListParams, TicketPriority, TicketStatus } from '../ports/ticket-repository'
import { currentMonth } from '../utils/format'

export function useTicketFilters() {
  const route = useRoute()
  const router = useRouter()

  const [defaultFrom, defaultTo] = currentMonth()

  const status = ref<TicketStatus | ''>((route.query.status as TicketStatus) || '')
  const priority = ref<TicketPriority | ''>((route.query.priority as TicketPriority) || '')
  const userId = ref<number | null>(Number(route.query.user_id) || null)
  const assigneeId = ref<number | null>(Number(route.query.assignee_id) || null)
  const searchRaw = ref<string>((route.query.search as string) || '')
  const createdFrom = ref<string>((route.query.created_from as string) || defaultFrom)
  const createdTo = ref<string>((route.query.created_to as string) || defaultTo)
  const page = ref<number>(Number(route.query.page) || 1)

  const search = useDebounce(searchRaw, 400)

  watch(
    [status, priority, userId, assigneeId, search, createdFrom, createdTo, page],
    () => {
      router.replace({
        query: {
          ...(status.value ? { status: status.value } : {}),
          ...(priority.value ? { priority: priority.value } : {}),
          ...(userId.value ? { user_id: String(userId.value) } : {}),
          ...(assigneeId.value ? { assignee_id: String(assigneeId.value) } : {}),
          ...(search.value ? { search: search.value } : {}),
          ...(createdFrom.value ? { created_from: createdFrom.value } : {}),
          ...(createdTo.value ? { created_to: createdTo.value } : {}),
          ...(page.value > 1 ? { page: String(page.value) } : {}),
        },
      })
    },
  )

  const params = computed<TicketListParams>(() => ({
    status: status.value || undefined,
    priority: priority.value || undefined,
    user_id: userId.value || undefined,
    assignee_id: assigneeId.value || undefined,
    search: search.value || undefined,
    created_from: createdFrom.value || undefined,
    created_to: createdTo.value || undefined,
    page: page.value,
    per_page: 15,
  }))

  function limpar() {
    status.value = ''
    priority.value = ''
    userId.value = null
    assigneeId.value = null
    searchRaw.value = ''
    const [f, t] = currentMonth()
    createdFrom.value = f
    createdTo.value = t
    page.value = 1
  }

  return {
    status,
    priority,
    userId,
    assigneeId,
    searchRaw,
    search,
    createdFrom,
    createdTo,
    page,
    params,
    limpar,
  }
}
