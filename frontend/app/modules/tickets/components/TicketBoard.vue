<script setup lang="ts">
import { toast } from 'vue-sonner'
import type { Ticket, TicketListParams, TicketStatus } from '~/modules/tickets/ports/ticket-repository'
import { TICKET_STATUSES } from '~/modules/tickets/ports/ticket-repository'

const props = defineProps<{
  filters: Omit<TicketListParams, 'status' | 'page' | 'per_page'>,
}>()

const emit = defineEmits<{
  ver: [ticket: Ticket],
  editar: [ticket: Ticket],
  excluir: [ticket: Ticket],
}>()

const { columns, loading, load, reload } = useTicketBoard()

watch(() => props.filters, (f) => { load(f) }, { immediate: true, deep: true })

const filtersQuery = computed(() => {
  const f = props.filters as Record<string, unknown>
  return Object.entries(f)
    .filter(([, v]) => v !== undefined && v !== null && v !== '')
    .map(([k, v]) => `${k}=${encodeURIComponent(String(v))}`)
    .join('&')
})

async function aoMover(ticket: Ticket, toStatus: TicketStatus) {
  const fromStatus = ticket.status

  if (fromStatus === toStatus) return

  const { $ticketRepository } = useNuxtApp()

  try {
    await $ticketRepository.update(ticket.id, { status: toStatus })
    toast.success(`Chamado movido para "${toStatus === 'in_progress' ? 'Em andamento' : toStatus}".`)
    await reload()
  }
  catch (error) {
    const msg = error instanceof Error ? error.message : 'Erro ao mover chamado.'
    toast.error(msg)
    await reload()
  }
}
</script>

<template>
  <div>
    <div
      v-if="loading && Object.values(columns).every(c => c.data.length === 0)"
      class="flex gap-3 overflow-x-auto pb-2"
    >
      <div
        v-for="s in TICKET_STATUSES"
        :key="s"
        class="min-w-[260px]"
      >
        <Skeleton class="h-10 w-full rounded-t-lg" />
        <div class="flex flex-col gap-2 p-2 border border-t-0 rounded-b-lg">
          <Skeleton
            v-for="i in 4"
            :key="i"
            class="h-24 w-full rounded-lg"
          />
        </div>
      </div>
    </div>

    <TooltipProvider v-else>
      <div class="flex gap-3 overflow-x-auto pb-4 items-start">
        <TicketBoardColumn
          v-for="status in TICKET_STATUSES"
          :key="status"
          :status="status"
          :tickets="columns[status].data"
          :total="columns[status].total"
          :filters-query="filtersQuery"
          @move="aoMover"
          @ver="emit('ver', $event)"
          @editar="emit('editar', $event)"
          @excluir="emit('excluir', $event)"
        />
      </div>
    </TooltipProvider>
  </div>
</template>
