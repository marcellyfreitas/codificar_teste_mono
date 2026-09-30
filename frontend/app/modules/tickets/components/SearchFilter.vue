<script setup lang="ts">
import { Icon } from '@iconify/vue'

const model = defineModel<string>({ required: true })
</script>

<template>
  <div class="relative min-w-[200px] flex-1">
    <Icon
      icon="lucide:search"
      class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none"
    />
    <Input
      :model-value="model"
      type="search"
      placeholder="Buscar por protocolo, título ou descrição…"
      class="pl-8"
      @update:model-value="model = String($event)"
    />
  </div>
</template>
