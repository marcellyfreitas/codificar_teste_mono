<script setup lang="ts">
import { useTimeAgo } from '@vueuse/core'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'
import { STATUS_LABEL, PRIORITY_LABEL } from '~/modules/tickets/ports/ticket-repository'
import { absoluteDate } from '~/modules/tickets/utils/format'
import { shortName } from '~/modules/users/utils/display'

const props = defineProps<{
  open: boolean,
  ticket: Ticket | null,
}>()

const emit = defineEmits<{
  'update:open': [v: boolean],
}>()

const tempoRelativo = useTimeAgo(() =>
  props.ticket ? new Date(props.ticket.created_at) : new Date(),
)
</script>

<template>
  <Sheet
    :open="open"
    @update:open="emit('update:open', $event)"
  >
    <SheetContent class="w-96! sm:max-w-none! overflow-y-auto">
      <SheetHeader>
        <SheetTitle>Chamado #{{ ticket?.id }}</SheetTitle>
        <SheetDescription>Detalhes do chamado.</SheetDescription>
      </SheetHeader>

      <div
        v-if="ticket"
        class="px-4 space-y-4"
      >
        <div>
          <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
            Título
          </p>
          <p class="text-sm font-medium">
            {{ ticket.title }}
          </p>
        </div>

        <div>
          <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
            Descrição
          </p>
          <p class="text-sm whitespace-pre-wrap">
            {{ ticket.description }}
          </p>
        </div>

        <div class="flex items-center gap-4">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
              Status
            </p>
            <TicketStatusBadge :status="ticket.status" />
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
              Prioridade
            </p>
            <TicketPriorityBadge :priority="ticket.priority" />
          </div>
        </div>

        <Separator />

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
              Autor
            </p>
            <p class="text-sm">
              {{ shortName(ticket.user) }}
            </p>
            <p class="text-xs text-muted-foreground">
              {{ ticket.user.email }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
              Responsável
            </p>
            <p
              v-if="ticket.assignee"
              class="text-sm"
            >
              {{ shortName(ticket.assignee) }}
            </p>
            <p
              v-else
              class="text-sm italic text-muted-foreground"
            >
              Fila
            </p>
          </div>
        </div>

        <Separator />

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
              Criado
            </p>
            <p
              class="text-sm"
              :title="absoluteDate(ticket.created_at)"
            >
              {{ tempoRelativo }}
            </p>
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">
              Atualizado
            </p>
            <p class="text-sm">
              {{ absoluteDate(ticket.updated_at) }}
            </p>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>
