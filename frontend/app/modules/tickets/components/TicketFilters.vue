<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { TICKET_STATUSES, STATUS_LABEL, PRIORITY_LABEL } from '~/modules/tickets/ports/ticket-repository'
import type { TicketStatus, TicketPriority } from '~/modules/tickets/ports/ticket-repository'

const props = defineProps<{
  status: TicketStatus | '',
  priority: TicketPriority | '',
  userId: number | null,
  assigneeId: number | null,
  search: string,
  createdFrom: string,
  createdTo: string,
}>()

const emit = defineEmits<{
  'update:status': [v: TicketStatus | ''],
  'update:priority': [v: TicketPriority | ''],
  'update:userId': [v: number | null],
  'update:assigneeId': [v: number | null],
  'update:search': [v: string],
  'update:createdFrom': [v: string],
  'update:createdTo': [v: string],
  limpar: [],
}>()

const temFiltros = computed(() =>
  !!(props.status || props.priority || props.userId || props.assigneeId
    || props.search),
)

const prioridades: TicketPriority[] = ['low', 'medium', 'high']
</script>

<template>
  <div class="flex flex-wrap gap-2 items-end">
    <!-- Busca textual -->
    <div class="relative min-w-[200px] flex-1">
      <Icon
        icon="lucide:search"
        class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"
      />
      <Input
        :model-value="search"
        type="search"
        placeholder="Buscar título ou descrição…"
        class="pl-8"
        @update:model-value="emit('update:search', String($event))"
      />
    </div>

    <!-- Status -->
    <Select
      :model-value="status || 'all'"
      @update:model-value="emit('update:status', $event === 'all' ? '' : ($event as TicketStatus))"
    >
      <SelectTrigger class="w-[150px]">
        <SelectValue placeholder="Status" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">
          Qualquer status
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

    <!-- Prioridade -->
    <Select
      :model-value="priority || 'all'"
      @update:model-value="emit('update:priority', $event === 'all' ? '' : ($event as TicketPriority))"
    >
      <SelectTrigger class="w-[140px]">
        <SelectValue placeholder="Prioridade" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">
          Qualquer prioridade
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

    <!-- Autor -->
    <div class="w-[200px]">
      <PersonFilter
        :model-value="userId"
        placeholder="Qualquer autor"
        @update:model-value="emit('update:userId', $event)"
      />
    </div>

    <!-- Responsável (só gestores) -->
    <div class="w-[200px]">
      <PersonFilter
        :model-value="assigneeId"
        role="gestor"
        placeholder="Qualquer responsável"
        @update:model-value="emit('update:assigneeId', $event)"
      />
    </div>

    <!-- Janela de data -->
    <div class="flex items-center gap-1">
      <Input
        :model-value="createdFrom"
        type="date"
        class="w-[140px]"
        aria-label="Data inicial"
        @update:model-value="emit('update:createdFrom', String($event))"
      />
      <span class="text-muted-foreground text-sm">até</span>
      <Input
        :model-value="createdTo"
        type="date"
        class="w-[140px]"
        aria-label="Data final"
        @update:model-value="emit('update:createdTo', String($event))"
      />
    </div>

    <!-- Limpar filtros -->
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
