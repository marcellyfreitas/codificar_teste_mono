<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'
import { nextStatus, prevStatus } from '~/modules/tickets/ports/ticket-repository'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useHead({ title: 'Chamados · Painel de Chamados' })

const filters = useTicketFilters()
const { page, loading, error, load, reload } = useTicketList()
const { reload: reloadBoard } = useTicketBoard()
const { advance } = useTicketActions()

watch(
  filters.params,
  (params) => { load(params) },
  { immediate: true },
)

type Aba = 'lista' | 'quadro'
const aba = ref<Aba>('quadro')

const ticketAtivo = ref<Ticket | null>(null)

const verAberto = ref(false)
function abrirVer(ticket: Ticket) {
  ticketAtivo.value = ticket
  verAberto.value = true
}

const formAberto = ref(false)
const modoEdicao = ref(false)
function abrirCriar() {
  ticketAtivo.value = null
  modoEdicao.value = false
  formAberto.value = true
}
function abrirEditar(ticket: Ticket) {
  ticketAtivo.value = ticket
  modoEdicao.value = true
  formAberto.value = true
}

const excluirAberto = ref(false)
function abrirExcluir(ticket: Ticket) {
  ticketAtivo.value = ticket
  excluirAberto.value = true
}

async function aoAvancar(ticket: Ticket) {
  const proxima = nextStatus(ticket.status)
  if (!proxima) return

  await advance(ticket.id, proxima)
}

async function aoRetornar(ticket: Ticket) {
  const anterior = prevStatus(ticket.status)
  if (!anterior) return

  await advance(ticket.id, anterior)
}

async function aoSalvar() {
  formAberto.value = false
  await Promise.all([reload(), reloadBoard()])
}

async function aoExcluir() {
  excluirAberto.value = false
  await Promise.all([reload(), reloadBoard()])
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">
        Chamados
      </h1>
      <Button
        class="gap-2"
        @click="abrirCriar"
      >
        <Icon
          icon="lucide:plus"
          class="size-4"
        />
        Novo chamado
      </Button>
    </div>

    <TicketFilters
      v-model:status="filters.status.value"
      v-model:priority="filters.priority.value"
      v-model:search="filters.searchRaw.value"
      v-model:created-from="filters.createdFrom.value"
      v-model:created-to="filters.createdTo.value"
      @limpar="filters.limpar()"
    />

    <p
      v-if="error"
      class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-2 text-sm text-destructive"
    >
      {{ error }}
    </p>

    <Tabs
      :model-value="aba"
      @update:model-value="aba = $event as Aba"
    >
      <TabsList>
        <TabsTrigger value="quadro">
          <Icon
            icon="lucide:layout-dashboard"
            class="size-4 mr-1.5"
          />
          Quadro
        </TabsTrigger>
        <TabsTrigger value="lista">
          <Icon
            icon="lucide:list"
            class="size-4 mr-1.5"
          />
          Lista
        </TabsTrigger>
      </TabsList>

      <TabsContent value="quadro">
        <TicketBoard
          :filters="filters.params.value"
          @avancar="aoAvancar"
          @retornar="aoRetornar"
          @editar="abrirEditar"
          @excluir="abrirExcluir"
          @ver="abrirVer"
        />
      </TabsContent>

      <TabsContent value="lista">
        <TicketTable
          :page="page"
          :loading="loading"
          @ver="abrirVer"
          @avancar="aoAvancar"
          @retornar="aoRetornar"
          @editar="abrirEditar"
          @excluir="abrirExcluir"
          @update:page="filters.page.value = $event"
        />
      </TabsContent>
    </Tabs>

    <TicketDetailSheet
      v-model:open="verAberto"
      :ticket="ticketAtivo"
    />

    <TicketFormSheet
      v-model:open="formAberto"
      :ticket="ticketAtivo"
      :modo-edicao="modoEdicao"
      @salvo="aoSalvar"
    />

    <TicketDeleteDialog
      v-model:open="excluirAberto"
      :ticket="ticketAtivo"
      @excluido="aoExcluir"
    />
  </div>
</template>
