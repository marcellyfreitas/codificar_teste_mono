<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { TicketPriority, TicketStatus } from '~/modules/tickets/ports/ticket-repository'

const status = defineModel<TicketStatus | ''>('status', { required: true })
const priority = defineModel<TicketPriority | ''>('priority', { required: true })
const search = defineModel<string>('search', { required: true })
const createdFrom = defineModel<string>('createdFrom', { required: true })
const createdTo = defineModel<string>('createdTo', { required: true })

const emit = defineEmits<{
  limpar: [],
}>()

const temFiltros = computed(() => !!(status.value || priority.value || search.value))
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-[1fr_auto_auto_auto] gap-2">
    <SearchFilter v-model="search" />

    <StatusFilter v-model="status" />

    <PriorityFilter v-model="priority" />

    <PeriodFilter
      v-model:from="createdFrom"
      v-model:to="createdTo"
    />

    <Button
      v-if="temFiltros"
      variant="ghost"
      size="sm"
      class="gap-1"
      @click="emit('limpar')"
    >
      <Icon
        icon="lucide:x"
        class="size-4"
      />
      Limpar
    </Button>
  </div>
</template>
