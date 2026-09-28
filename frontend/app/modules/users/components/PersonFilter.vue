<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { UserRole } from '~/modules/auth/ports/auth-repository'
import { shortName } from '~/modules/users/utils/display'
const props = defineProps<{
  modelValue: number | null | undefined,
  role?: UserRole,
  placeholder?: string,
  disabled?: boolean,
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | null],
}>()

const { gestores, todos, loading, fetchGestores, fetchAll } = useUserDirectory()

const search = ref('')
const open = ref(false)

const lista = computed(() =>
  props.role === 'gestor' ? gestores.value.data : todos.value.data,
)

const selecionado = computed(() =>
  lista.value.find(u => u.id === props.modelValue),
)

watch(open, async (val) => {
  if (!val) return

  if (props.role === 'gestor') {
    if (gestores.value.data.length === 0) await fetchGestores()
  }
  else {
    if (todos.value.data.length === 0) await fetchAll()
  }
})

watch(search, async (val) => {
  if (props.role === 'gestor') {
    await fetchGestores(val)
  }
  else {
    await fetchAll(undefined, val)
  }
})

function selecionar(id: number | null) {
  emit('update:modelValue', id)
  open.value = false
}

function limpar() {
  emit('update:modelValue', null)
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
          {{ selecionado ? shortName(selecionado) : (placeholder ?? 'Qualquer pessoa') }}
        </span>
        <div class="ml-2 flex items-center gap-1">
          <button
            v-if="modelValue"
            type="button"
            class="rounded hover:text-foreground text-muted-foreground"
            aria-label="Limpar filtro"
            @click.stop="limpar"
          >
            <Icon
              icon="lucide:x"
              class="size-3"
            />
          </button>
          <Icon
            icon="lucide:chevrons-up-down"
            class="size-4 shrink-0 opacity-50"
          />
        </div>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-[280px] p-0">
      <Command>
        <CommandInput
          v-model="search"
          placeholder="Buscar…"
        />
        <CommandEmpty>
          <span v-if="loading">Carregando…</span>
          <span v-else>Nenhum usuário encontrado.</span>
        </CommandEmpty>
        <CommandList>
          <CommandGroup>
            <CommandItem
              v-for="u in lista"
              :key="u.id"
              :value="String(u.id)"
              class="flex flex-col items-start"
              @select="selecionar(u.id)"
            >
              <div class="flex w-full items-center justify-between">
                <span class="font-medium">{{ shortName(u) }}</span>
                <Icon
                  v-if="u.id === modelValue"
                  icon="lucide:check"
                  class="size-4 text-primary"
                />
              </div>
              <span class="text-xs text-muted-foreground">{{ u.email }}</span>
            </CommandItem>
          </CommandGroup>
        </CommandList>
      </Command>
    </PopoverContent>
  </Popover>
</template>
