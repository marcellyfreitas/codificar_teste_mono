<script setup lang="ts">
import type { DateRange } from 'reka-ui'
import type { DateValue } from '@internationalized/date'
import { Icon } from '@iconify/vue'
import { parseDate } from '@internationalized/date'

const props = defineProps<{
  from: string,
  to: string,
}>()

const emit = defineEmits<{
  'update:from': [v: string],
  'update:to': [v: string],
}>()

const aberto = ref(false)

const parcial = ref<DateRange | null>(null)

function paraIso(data: DateValue): string {
  return `${data.year}-${String(data.month).padStart(2, '0')}-${String(data.day).padStart(2, '0')}`
}

const intervalo = computed<DateRange>({
  get() {
    if (parcial.value) {
      return parcial.value
    }

    return {
      start: props.from ? parseDate(props.from) : undefined,
      end: props.to ? parseDate(props.to) : undefined,
    }
  },
  set(valor) {
    if (!valor?.start) {
      parcial.value = null
      emit('update:from', '')
      emit('update:to', '')
      return
    }

    if (!valor.end) {
      parcial.value = { start: valor.start, end: undefined }
      return
    }

    parcial.value = null
    emit('update:from', paraIso(valor.start))
    emit('update:to', paraIso(valor.end))
  },
})

watch(() => [props.from, props.to], () => {
  parcial.value = null
})

const rotulo = computed(() => {
  if (!props.from && !props.to) {
    return 'Qualquer período'
  }

  const formatar = (iso: string) => {
    const [, mes, dia] = iso.split('-')
    return `${dia}/${mes}`
  }

  return props.from === props.to
    ? formatar(props.from)
    : `${formatar(props.from)} – ${formatar(props.to)}`
})

function limpar() {
  parcial.value = null
  emit('update:from', '')
  emit('update:to', '')
}
</script>

<template>
  <Popover
    v-model:open="aberto"
  >
    <PopoverTrigger
      as-child
    >
      <Button
        variant="outline"
        class="w-[190px] justify-start gap-2 font-normal"
      >
        <Icon
          icon="lucide:calendar"
          class="size-4 shrink-0 opacity-70"
        />
        <span class="truncate">{{ rotulo }}</span>
      </Button>
    </PopoverTrigger>

    <PopoverContent
      class="w-auto p-0"
      align="start"
    >
      <RangeCalendar
        v-model="intervalo"
        locale="pt-BR"
        weekday-format="short"
        :number-of-months="2"
      />
      <div class="flex items-center justify-between border-t p-2">
        <Button
          variant="ghost"
          size="sm"
          class="gap-1"
          @click="aberto = false"
        >
          Fechar
        </Button>
        <Button
          variant="ghost"
          size="sm"
          @click="limpar"
        >
          Limpar
        </Button>
      </div>
    </PopoverContent>
  </Popover>
</template>
