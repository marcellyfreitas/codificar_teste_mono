<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { Ticket, TicketStatus, TicketPriority } from '~/modules/tickets/ports/ticket-repository'
import { TICKET_STATUSES, STATUS_LABEL, PRIORITY_LABEL } from '~/modules/tickets/ports/ticket-repository'
import { ApiError } from '~/modules/core/ports/http'
const props = defineProps<{
  open: boolean,
  ticket: Ticket | null,
  modoEdicao: boolean,
}>()

const emit = defineEmits<{
  'update:open': [v: boolean],
  salvo: [],
}>()

interface Draft {
  title: string,
  description: string,
  priority: TicketPriority | '',
  status: TicketStatus | '',
  assignee_id: number | null,
}

type DraftErrors = Partial<Record<keyof Draft, string[]>>

const draft = reactive<Draft>({
  title: '',
  description: '',
  priority: '',
  status: '',
  assignee_id: null,
})

const errors = reactive<DraftErrors>({})
const submitting = ref(false)

const { create, update } = useTicketActions()

watch(() => props.open, (aberto) => {
  if (!aberto) return

  limparErros()

  if (props.modoEdicao && props.ticket) {
    draft.title = props.ticket.title
    draft.description = props.ticket.description
    draft.priority = props.ticket.priority
    draft.status = props.ticket.status
    draft.assignee_id = props.ticket.assignee_id
  }
  else {
    draft.title = ''
    draft.description = ''
    draft.priority = ''
    draft.status = ''
    draft.assignee_id = null
  }
})

function limparErros() {
  for (const k of Object.keys(errors) as Array<keyof DraftErrors>) {
    delete errors[k]
  }
}

function limparErro(campo: keyof DraftErrors) {
  delete errors[campo]
}

function validar(): boolean {
  limparErros()
  let ok = true

  if (!draft.title.trim()) {
    errors.title = ['O campo título é obrigatório.']
    ok = false
  }
  else if (draft.title.length > 255) {
    errors.title = ['O campo título não pode ter mais que 255 caracteres.']
    ok = false
  }

  if (!draft.description.trim()) {
    errors.description = ['O campo descrição é obrigatório.']
    ok = false
  }

  if (!props.modoEdicao && !draft.priority) {
    errors.priority = ['O campo prioridade é obrigatório.']
    ok = false
  }

  return ok
}

function alocarErros422(err: ApiError) {
  for (const [campo, msgs] of Object.entries(err.errors)) {
    const k = campo as keyof DraftErrors
    errors[k] = msgs
  }
}

async function enviar() {
  if (!validar()) return

  submitting.value = true

  try {
    let apiError: ApiError | null = null

    if (props.modoEdicao && props.ticket) {
      const patch: Record<string, unknown> = {}
      if (draft.title !== props.ticket.title) patch.title = draft.title
      if (draft.description !== props.ticket.description) patch.description = draft.description
      if (draft.priority && draft.priority !== props.ticket.priority) patch.priority = draft.priority
      if (draft.status && draft.status !== props.ticket.status) patch.status = draft.status
      if (draft.assignee_id !== props.ticket.assignee_id) patch.assignee_id = draft.assignee_id

      apiError = await update(props.ticket.id, patch)
    }
    else {
      apiError = await create({
        title: draft.title,
        description: draft.description,
        priority: draft.priority as TicketPriority,
        status: draft.status || undefined,
        assignee_id: draft.assignee_id,
        auto_assign: false,
      })
    }

    if (apiError?.isValidation) {
      alocarErros422(apiError)
      return
    }

    if (!apiError) {
      emit('update:open', false)
      emit('salvo')
    }
  }
  finally {
    submitting.value = false
  }
}

const prioridades: TicketPriority[] = ['low', 'medium', 'high']
</script>

<template>
  <Sheet
    :open="open"
    @update:open="emit('update:open', $event)"
  >
    <SheetContent class="w-96! sm:max-w-none! overflow-y-auto">
      <SheetHeader>
        <SheetTitle>
          {{ modoEdicao ? 'Editar chamado' : 'Novo chamado' }}
        </SheetTitle>
        <SheetDescription>
          {{
            modoEdicao
              ? 'Atualize os campos que mudaram.'
              : 'Preencha os dados do novo chamado.'
          }}
        </SheetDescription>
      </SheetHeader>

      <form
        class="mt-6 flex flex-col gap-5"
        novalidate
        @submit.prevent="enviar"
      >
        <div class="grid gap-4 px-4">
          <Field :invalid="Boolean(errors.title?.length)">
            <FieldLabel for="form-title">
              Título <span class="text-destructive">*</span>
            </FieldLabel>
            <Input
              id="form-title"
              v-model="draft.title"
              :disabled="submitting"
              maxlength="255"
              @input="limparErro('title')"
            />
            <FieldError :errors="errors.title" />
          </Field>

          <Field :invalid="Boolean(errors.description?.length)">
            <FieldLabel for="form-desc">
              Descrição <span class="text-destructive">*</span>
            </FieldLabel>
            <Textarea
              id="form-desc"
              v-model="draft.description"
              :disabled="submitting"
              rows="4"
              @input="limparErro('description')"
            />
            <FieldError :errors="errors.description" />
          </Field>

          <Field :invalid="Boolean(errors.priority?.length)">
            <FieldLabel>
              Prioridade <span class="text-destructive">*</span>
            </FieldLabel>
            <Select
              v-model="draft.priority"
              :disabled="submitting"
              @update:model-value="limparErro('priority')"
            >
              <SelectTrigger>
                <SelectValue placeholder="Selecionar prioridade…" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="p in prioridades"
                  :key="p"
                  :value="p"
                >
                  {{ PRIORITY_LABEL[p] }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FieldError :errors="errors.priority" />
          </Field>

          <Field :invalid="Boolean(errors.status?.length)">
            <FieldLabel>Status</FieldLabel>
            <Select
              :model-value="draft.status || 'default'"
              :disabled="submitting"
              @update:model-value="draft.status = $event === 'default' ? '' : ($event as TicketStatus); limparErro('status')"
            >
              <SelectTrigger>
                <SelectValue placeholder="Padrão (aberto)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="default">
                  Padrão (aberto)
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
            <FieldError :errors="errors.status" />
          </Field>

          <Field :invalid="Boolean(errors.assignee_id?.length)">
            <FieldLabel>Responsável</FieldLabel>
            <AssigneeSelect
              v-model="draft.assignee_id"
              :disabled="submitting"
              @update:model-value="limparErro('assignee_id')"
            />
            <FieldError :errors="errors.assignee_id" />
          </Field>
        </div>

        <SheetFooter class="grid grid-cols-2 gap-4">
          <Button
            type="button"
            variant="outline"
            :disabled="submitting"
            @click="emit('update:open', false)"
          >
            Cancelar
          </Button>
          <Button
            type="submit"
            :disabled="submitting"
            class="gap-2"
          >
            <Icon
              v-if="submitting"
              icon="lucide:loader-circle"
              class="size-4 animate-spin"
            />
            {{ modoEdicao ? 'Salvar alterações' : 'Criar chamado' }}
          </Button>
        </SheetFooter>
      </form>
    </SheetContent>
  </Sheet>
</template>
