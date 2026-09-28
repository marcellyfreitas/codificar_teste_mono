<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'

const props = defineProps<{
  open: boolean,
  ticket: Ticket | null,
}>()

const emit = defineEmits<{
  'update:open': [v: boolean],
  excluido: [],
}>()

const { destroy } = useTicketActions()
const submitting = ref(false)

async function confirmar() {
  if (!props.ticket) return

  submitting.value = true

  try {
    const ok = await destroy(props.ticket.id)

    if (ok) {
      emit('update:open', false)
      emit('excluido')
    }
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <AlertDialog
    :open="open"
    @update:open="emit('update:open', $event)"
  >
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle class="flex items-center gap-2">
          <Icon
            icon="lucide:triangle-alert"
            class="size-5 text-destructive"
          />
          Excluir chamado
        </AlertDialogTitle>
        <AlertDialogDescription class="space-y-2">
          <p>
            Tem certeza que deseja excluir o chamado
            <strong class="text-foreground">{{ ticket?.title }}</strong>?
          </p>
          <p class="text-xs">
            Esta ação arquiva o chamado — ele some das listas e do quadro,
            mas o histórico de atendimento é preservado.
          </p>
        </AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter>
        <AlertDialogCancel :disabled="submitting">
          Cancelar
        </AlertDialogCancel>
        <AlertDialogAction
          :disabled="submitting"
          class="bg-destructive text-white hover:bg-destructive/90 gap-2"
          @click.prevent="confirmar"
        >
          <Icon
            v-if="submitting"
            icon="lucide:loader-circle"
            class="size-4 animate-spin"
          />
          Excluir
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
