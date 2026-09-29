<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { shortName } from '~/modules/users/utils/display'

const props = defineProps<{
  modelValue: number | null,
  disabled?: boolean,
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | null],
}>()

const { gestores, loading, fetchGestores } = useUserDirectory()
const search = ref('')

const opcoes = computed(() => [
  { id: null as number | null, label: 'Fila', sub: 'Sem responsável' },
  ...gestores.value.data.map(u => ({
    id: u.id as number | null,
    label: shortName(u),
    sub: u.email,
  })),
])

const selecionado = computed(() =>
  opcoes.value.find(o => o.id === props.modelValue),
)

const open = ref(false)

watch(open, async (val) => {
  if (val && gestores.value.data.length === 0) {
    await fetchGestores()
  }
})

watch(search, async (val) => {
  await fetchGestores(val)
})

function selecionar(id: number | null) {
  emit('update:modelValue', id)
  open.value = false
}
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        class="w-full justify-between font-normal"
        :disabled="disabled"
      >
        <span class="truncate">
          {{ selecionado?.label ?? 'Selecionar responsável…' }}
        </span>
        <Icon
          icon="lucide:chevrons-up-down"
          class="ml-2 size-4 shrink-0 opacity-50"
        />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[280px] p-0">
      <Command>
        <CommandInput
          v-model="search"
          placeholder="Buscar gestor…"
        />
        <CommandEmpty>
          <span v-if="loading">Carregando…</span>
          <span v-else>Nenhum gestor encontrado.</span>
        </CommandEmpty>
        <CommandList>
          <CommandGroup>
            <CommandItem
              v-for="opcao in opcoes"
              :key="String(opcao.id)"
              :value="String(opcao.id)"
              class="flex flex-col items-start"
              @select="selecionar(opcao.id)"
            >
              <div class="flex w-full items-center justify-between">
                <span class="font-medium">{{ opcao.label }}</span>
                <Icon
                  v-if="opcao.id === modelValue"
                  icon="lucide:check"
                  class="size-4 text-primary"
                />
              </div>
              <span class="text-xs text-muted-foreground">{{ opcao.sub }}</span>
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
