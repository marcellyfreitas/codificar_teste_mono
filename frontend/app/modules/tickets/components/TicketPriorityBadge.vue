<script setup lang="ts">
import type { TicketPriority } from '~/modules/tickets/ports/ticket-repository'
import { PRIORITY_LABEL } from '~/modules/tickets/ports/ticket-repository'

const props = defineProps<{ priority: TicketPriority }>()

const colorClass = computed(() => ({
  low: 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
  medium: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
  high: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
}[props.priority]))
</script>

<template>
  <span
    :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', colorClass]"
  >
    {{ PRIORITY_LABEL[priority] }}
  </span>
</template>
