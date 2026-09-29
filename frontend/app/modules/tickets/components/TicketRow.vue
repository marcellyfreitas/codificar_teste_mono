<script setup lang="ts">
import { useTimeAgo } from '@vueuse/core'
import { Icon } from '@iconify/vue'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'
import { absoluteDate, truncate } from '~/modules/tickets/utils/format'
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
  <TableRow>
    <TableCell class="font-mono text-xs text-muted-foreground">
      #{{ ticket.id }}
    </TableCell>

    <TableCell class="max-w-[240px]">
      <span
        class="font-medium"
        :title="ticket.title"
      >
        {{ truncate(ticket.title, 50) }}
      </span>
    </TableCell>

    <TableCell>
      <TicketStatusBadge :status="ticket.status" />
    </TableCell>

    <TableCell>
      <TicketPriorityBadge :priority="ticket.priority" />
    </TableCell>

    <TableCell>
      <span
        :title="absoluteDate(ticket.created_at)"
        class="text-sm text-muted-foreground"
      >
        {{ tempoRelativo }}
      </span>
    </TableCell>

    <TableCell>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            size="icon"
            class="size-8"
            aria-label="Ações"
          >
            <Icon
              icon="lucide:ellipsis"
              class="size-4"
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
    </TableCell>
  </TableRow>
</template>
