<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useTimeAgo } from '@vueuse/core'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'
import { truncate, absoluteDate } from '~/modules/tickets/utils/format'
import { shortName } from '~/modules/users/utils/display'
import { nextStatus, prevStatus } from '~/modules/tickets/ports/ticket-repository'
import { canAdvanceTicket, canEditTicket, canDeleteTicket } from '~/modules/tickets/utils/permissions'

const props = defineProps<{ ticket: Ticket }>()

const emit = defineEmits<{
  ver: [ticket: Ticket],
  avancar: [ticket: Ticket],
  retornar: [ticket: Ticket],
  editar: [ticket: Ticket],
  excluir: [ticket: Ticket],
}>()

const { user } = useSession()
const podeEditar = computed(() => user.value ? canEditTicket(user.value) : false)
const podeExcluir = computed(() => user.value ? canDeleteTicket(user.value) : false)

const podeMover = computed(() => user.value ? canAdvanceTicket(user.value) : false)
const podeAvancar = computed(() => podeMover.value && nextStatus(props.ticket.status) !== null)
const podeRetornar = computed(() => podeMover.value && prevStatus(props.ticket.status) !== null)

const tempoRelativo = useTimeAgo(() => new Date(props.ticket.created_at))
</script>

<template>
  <Card class="cursor-grab active:cursor-grabbing select-none shadow-sm hover:shadow-md transition-shadow">
    <CardContent class="p-3 space-y-2">
      <div class="flex items-start justify-between gap-2">
        <p
          class="text-sm font-medium leading-snug"
          :title="ticket.title"
        >
          {{ truncate(ticket.title, 60) }}
        </p>

        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              class="size-6 shrink-0 -mr-1"
              aria-label="Ações"
              @click.stop
            >
              <Icon
                icon="lucide:ellipsis"
                class="size-3"
              />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem
              class="gap-2"
              @click="emit('ver', ticket)"
            >
              <Icon
                icon="lucide:eye"
                class="size-4"
              />
              Visualizar
            </DropdownMenuItem>

            <DropdownMenuItem
              v-if="podeAvancar"
              class="gap-2"
              @click="emit('avancar', ticket)"
            >
              <Icon
                icon="lucide:arrow-right"
                class="size-4"
              />
              Avançar
            </DropdownMenuItem>

            <DropdownMenuItem
              v-if="podeRetornar"
              class="gap-2"
              @click="emit('retornar', ticket)"
            >
              <Icon
                icon="lucide:arrow-left"
                class="size-4"
              />
              Retornar
            </DropdownMenuItem>

            <DropdownMenuSeparator v-if="podeEditar || podeExcluir" />

            <DropdownMenuItem
              v-if="podeEditar"
              class="gap-2"
              @click="emit('editar', ticket)"
            >
              <Icon
                icon="lucide:pencil"
                class="size-4"
              />
              Editar
            </DropdownMenuItem>

            <DropdownMenuItem
              v-if="podeExcluir"
              class="gap-2 text-destructive focus:text-destructive"
              @click="emit('excluir', ticket)"
            >
              <Icon
                icon="lucide:trash-2"
                class="size-4"
              />
              Excluir
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <div class="flex items-center gap-1.5 flex-wrap">
        <TicketPriorityBadge :priority="ticket.priority" />
        <span class="text-xs text-muted-foreground font-mono">#{{ ticket.id }}</span>
      </div>

      <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
        <Icon
          icon="lucide:user"
          class="size-3"
        />
        <span>
          {{ ticket.assignee ? shortName(ticket.assignee) : 'Fila' }}
        </span>
        <span class="ml-auto">
          <span :title="absoluteDate(ticket.created_at)">{{ tempoRelativo }}</span>
        </span>
      </div>
    </CardContent>
  </Card>
</template>
