<script setup lang="ts">
import { useTimeAgo } from '@vueuse/core'
import { Icon } from '@iconify/vue'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'
import { absoluteDate, truncate } from '~/modules/tickets/utils/format'
import { shortName } from '~/modules/users/utils/display'
import { canEditTicket, canDeleteTicket } from '~/modules/tickets/utils/permissions'

const props = defineProps<{ ticket: Ticket }>()

const emit = defineEmits<{
  ver: [ticket: Ticket],
  editar: [ticket: Ticket],
  excluir: [ticket: Ticket],
}>()

const { user } = useSession()

const podeEditar = computed(() => user.value ? canEditTicket(user.value) : false)
const podeExcluir = computed(() => user.value ? canDeleteTicket(user.value) : false)

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

    <!-- Autor e responsável são colunas separadas — papéis distintos com
         contratos distintos. Colapsá-los em "usuário" destrói a informação. -->
    <TableCell class="text-sm">
      {{ shortName(ticket.user) }}
    </TableCell>

    <TableCell class="text-sm">
      <span
        v-if="ticket.assignee"
        class="text-foreground"
      >
        {{ shortName(ticket.assignee) }}
      </span>
      <span
        v-else
        class="text-muted-foreground italic"
      >
        Fila
      </span>
    </TableCell>

    <TableCell>
      <span
        :title="absoluteDate(ticket.created_at)"
        class="text-sm text-muted-foreground"
      >
        {{ tempoRelativo }}
      </span>
    </TableCell>

    <!-- Ações dentro de DropdownMenu (FRONTEND.md §4) -->
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

          <template v-if="podeEditar">
            <DropdownMenuSeparator />
            <DropdownMenuItem
              class="gap-2"
              @click="emit('editar', ticket)"
            >
              <Icon
                icon="lucide:pencil"
                class="size-4"
              />
              Editar
            </DropdownMenuItem>
          </template>

          <template v-if="podeExcluir">
            <DropdownMenuItem
              class="gap-2 text-destructive focus:text-destructive"
              @click="emit('excluir', ticket)"
            >
              <Icon
                icon="lucide:trash-2"
                class="size-4"
              />
              Excluir
            </DropdownMenuItem>
          </template>
        </DropdownMenuContent>
      </DropdownMenu>
    </TableCell>
  </TableRow>
</template>
