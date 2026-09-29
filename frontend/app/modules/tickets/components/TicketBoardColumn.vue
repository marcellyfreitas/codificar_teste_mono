<script setup lang="ts">
import { VueDraggable } from 'vue-draggable-plus'
import { Icon } from '@iconify/vue'
import type { Ticket, TicketStatus } from '~/modules/tickets/ports/ticket-repository'
import { STATUS_LABEL } from '~/modules/tickets/ports/ticket-repository'
import { canMoveTicket } from '~/modules/tickets/utils/permissions'

const props = defineProps<{
  status: TicketStatus,
  tickets: Ticket[],
  total: number,
  filtersQuery: string,
}>()

const emit = defineEmits<{
  move: [ticket: Ticket, toStatus: TicketStatus],
  ver: [ticket: Ticket],
  avancar: [ticket: Ticket],
  retornar: [ticket: Ticket],
  editar: [ticket: Ticket],
  excluir: [ticket: Ticket],
}>()

const { user } = useSession()
const podeArrastar = computed(() => user.value ? canMoveTicket(user.value) : false)

const localTickets = ref<Ticket[]>([...props.tickets])
watch(() => props.tickets, (val) => { localTickets.value = [...val] })

const headerColor = computed(() => ({
  open: 'bg-blue-50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800',
  in_progress: 'bg-yellow-50 dark:bg-yellow-950/30 border-yellow-200 dark:border-yellow-800',
  resolved: 'bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-800',
  closed: 'bg-neutral-50 dark:bg-neutral-900/40 border-neutral-200 dark:border-neutral-700',
}[props.status]))

const truncada = computed(() => props.tickets.length < props.total)
const verTodosHref = computed(() =>
  `/chamados?status=${props.status}&${props.filtersQuery}`,
)

function aoSoltar(event: { data: Ticket, from: HTMLElement, to: HTMLElement }) {
  const fromStatus = event.from.dataset.status as TicketStatus | undefined
  const toStatus = event.to.dataset.status as TicketStatus | undefined

  if (!fromStatus || !toStatus || fromStatus === toStatus) return

  emit('move', event.data, toStatus)
}
</script>

<template>
  <div class="flex flex-col min-w-[260px] max-w-[300px] w-full">
    <div :class="['rounded-t-lg border px-3 py-2 flex items-center justify-between', headerColor]">
      <div class="flex items-center gap-2">
        <span class="font-semibold text-sm">{{ STATUS_LABEL[status] }}</span>
        <Badge
          variant="secondary"
          class="text-xs px-1.5"
        >
          {{ tickets.length }}
          <span
            v-if="truncada"
            class="text-muted-foreground"
          >/{{ total }}</span>
        </Badge>
      </div>

      <Tooltip v-if="!podeArrastar">
        <TooltipTrigger as-child>
          <Icon
            icon="lucide:lock"
            class="size-4 text-muted-foreground"
          />
        </TooltipTrigger>
        <TooltipContent>
          Apenas gestores e administradores podem mover chamados
        </TooltipContent>
      </Tooltip>
    </div>

    <p
      v-if="truncada"
      class="bg-yellow-50 dark:bg-yellow-950/30 border-x border-yellow-200 dark:border-yellow-800 px-3 py-1 text-xs text-yellow-700 dark:text-yellow-400"
    >
      Mostrando {{ tickets.length }} de {{ total }}.
      <NuxtLink
        :to="verTodosHref"
        class="underline font-medium"
      >
        Ver todos na lista
      </NuxtLink>
    </p>

    <VueDraggable
      v-model="localTickets"
      group="kanban"
      :disabled="!podeArrastar"
      item-key="id"
      class="flex flex-col gap-2 flex-1 rounded-b-lg border border-t-0 p-2 min-h-[120px] bg-muted/30"
      :data-status="status"
      @end="aoSoltar"
    >
      <TicketCard
        v-for="ticket in localTickets"
        :key="ticket.id"
        :ticket="ticket"
        @ver="emit('ver', $event)"
        @avancar="emit('avancar', $event)"
        @retornar="emit('retornar', $event)"
        @editar="emit('editar', $event)"
        @excluir="emit('excluir', $event)"
      />
    </VueDraggable>
  </div>
</template>
