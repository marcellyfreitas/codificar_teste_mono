<script setup lang="ts">
import { TICKET_STATUSES, STATUS_LABEL } from '~/modules/tickets/ports/ticket-repository'
import type { TicketStatus } from '~/modules/tickets/ports/ticket-repository'

const model = defineModel<TicketStatus | ''>({ required: true })
</script>

<template>
  <Select
    :model-value="model || 'all'"
    @update:model-value="model = $event === 'all' ? '' : ($event as TicketStatus)"
  >
    <SelectTrigger class="w-[150px]">
      <SelectValue placeholder="Status" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="all">
        Status
      </SelectItem>
      <SelectItem
        v-for="s in TICKET_STATUSES"
        :key="s"
        :value="s"
      >
        {{ STATUS_LABEL[s] }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>
