<script setup lang="ts">
import type { TicketStatus } from '~/modules/tickets/ports/ticket-repository'
import { STATUS_LABEL } from '~/modules/tickets/ports/ticket-repository'

const props = defineProps<{ status: TicketStatus }>()

const colorClass = computed(() => ({
  open: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
  in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
  resolved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
  closed: 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
}[props.status]))
</script>

<template>
  <span
    :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', colorClass]"
  >
    {{ STATUS_LABEL[status] }}
  </span>
</template>
