<script setup lang="ts">
import { PRIORITY_LABEL } from '~/modules/tickets/ports/ticket-repository'
import type { TicketPriority } from '~/modules/tickets/ports/ticket-repository'

const prioridades: TicketPriority[] = ['low', 'medium', 'high']

const model = defineModel<TicketPriority | ''>({ required: true })
</script>

<template>
  <Select
    :model-value="model || 'all'"
    @update:model-value="model = $event === 'all' ? '' : ($event as TicketPriority)"
  >
    <SelectTrigger class="w-35">
      <SelectValue placeholder="Prioridade" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="all">
        Prioridade
      </SelectItem>
      <SelectItem
        v-for="p in prioridades"
        :key="p"
        :value="p"
      >
        {{ PRIORITY_LABEL[p] }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>
