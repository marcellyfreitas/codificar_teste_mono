<script setup lang="ts">
import { toast } from 'vue-sonner'
import { Icon } from '@iconify/vue'
import type { BalanceResult } from '~/modules/tickets/ports/ticket-repository'

definePageMeta({
  layout: 'dashboard',
  middleware: 'admin',
})

useHead({ title: 'Fila · Painel de Chamados' })

const { $ticketRepository } = useNuxtApp()
const { reload: reloadList } = useTicketList()
const { reload: reloadBoard } = useTicketBoard()
const { gestores, fetchGestores } = useUserDirectory()

const balanceResult = ref<BalanceResult | null>(null)
const runningBalance = ref(false)

async function executarBalance() {
  runningBalance.value = true

  try {
    balanceResult.value = await $ticketRepository.balance()
    toast.success('Redistribuição concluída.')
    await Promise.all([reloadList(), reloadBoard()])
  }
  catch (error) {
    const msg = error instanceof Error ? error.message : 'Erro ao redistribuir.'
    toast.error(msg)
  }
  finally {
    runningBalance.value = false
  }
}

const unassignAberto = ref(false)
const runningUnassign = ref(false)
const unassignResult = ref<{ affected: number } | null>(null)

async function confirmarUnassign() {
  runningUnassign.value = true

  try {
    unassignResult.value = await $ticketRepository.unassignOpen()
    toast.success('Responsáveis removidos dos chamados em aberto.')
    unassignAberto.value = false
    await Promise.all([reloadList(), reloadBoard()])
  }
  catch (error) {
    const msg = error instanceof Error ? error.message : 'Erro ao desatribuir.'
    toast.error(msg)
  }
  finally {
    runningUnassign.value = false
  }
}

onMounted(() => fetchGestores())

interface LoadRow { id: number, nome: string, email: string, carga: number }

const loadRows = computed((): LoadRow[] => {
  if (!balanceResult.value) return []

  const byId = Object.fromEntries(
    gestores.value.data.map(u => [String(u.id), u]),
  )

  return Object.entries(balanceResult.value.load_by_gestor)
    .map(([id, carga]) => {
      const gestor = byId[id]
      return {
        id: Number(id),
        nome: gestor?.name ?? `Gestor #${id}`,
        email: gestor?.email ?? '',
        carga: carga ?? 0,
      }
    })
    .sort((a, b) => b.carga - a.carga)
})

type SortKey = 'nome' | 'carga'
const sortKey = ref<SortKey>('carga')
const sortAsc = ref(false)

const loadRowsSorted = computed(() => {
  const rows = [...loadRows.value]
  rows.sort((a, b) => {
    const va = a[sortKey.value]
    const vb = b[sortKey.value]
    const cmp = typeof va === 'number' && typeof vb === 'number'
      ? va - vb
      : String(va).localeCompare(String(vb), 'pt-BR')
    return sortAsc.value ? cmp : -cmp
  })
  return rows
})

function alternarOrdem(key: SortKey) {
  if (sortKey.value === key) {
    sortAsc.value = !sortAsc.value
  }
  else {
    sortKey.value = key
    sortAsc.value = false
  }
}
</script>

<template>
  <div class="space-y-6 max-w-4xl">
    <div>
      <h1 class="text-xl font-semibold">
        Operações de Fila
      </h1>
      <p class="text-sm text-muted-foreground mt-1">
        Exclusivo para administradores. O fluxo recomendado é
        <strong>Desatribuir</strong> → <strong>Redistribuir</strong>.
      </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-base">
            <Icon
              icon="lucide:user-x"
              class="size-5 text-orange-500"
            />
            Desatribuir chamados
          </CardTitle>
          <CardDescription>
            Remove o responsável de todos os chamados em aberto
            (<code>open</code> + <code>in_progress</code>). Chamados resolvidos
            ou fechados mantêm o responsável.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p
            v-if="unassignResult"
            class="text-sm text-muted-foreground mb-3"
          >
            Último resultado: <strong>{{ unassignResult.affected }}</strong>
            chamados desatribuídos.
          </p>
          <Button
            variant="outline"
            class="gap-2 border-orange-300 text-orange-700 hover:bg-orange-50 dark:border-orange-700 dark:text-orange-400 dark:hover:bg-orange-950/30"
            @click="unassignAberto = true"
          >
            <Icon
              icon="lucide:user-x"
              class="size-4"
            />
            Desatribuir todos
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-base">
            <Icon
              icon="lucide:scale"
              class="size-5 text-blue-500"
            />
            Redistribuir chamados
          </CardTitle>
          <CardDescription>
            Distribui os chamados <em>sem responsável</em> entre os gestores,
            um a um, para o menos carregado. Idempotente.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Button
            class="gap-2"
            :disabled="runningBalance"
            @click="executarBalance"
          >
            <Icon
              v-if="runningBalance"
              icon="lucide:loader-circle"
              class="size-4 animate-spin"
            />
            <Icon
              v-else
              icon="lucide:scale"
              class="size-4"
            />
            Redistribuir agora
          </Button>
        </CardContent>
      </Card>
    </div>

    <template v-if="balanceResult">
      <Card>
        <CardHeader>
          <CardTitle class="text-base">
            Resultado da redistribuição
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="rounded-lg border p-3 text-center">
              <p class="text-2xl font-bold">
                {{ balanceResult.distributed }}
              </p>
              <p class="text-xs text-muted-foreground mt-0.5">
                Distribuídos
              </p>
            </div>
            <div class="rounded-lg border p-3 text-center">
              <p class="text-2xl font-bold">
                {{ balanceResult.difference }}
              </p>
              <p class="text-xs text-muted-foreground mt-0.5">
                Diferença ({{ balanceResult.difference === 0 ? 'perfeito' : 'empate' }})
              </p>
            </div>
            <div class="rounded-lg border p-3 text-center">
              <p class="text-2xl font-bold">
                {{ balanceResult.min_open }}
              </p>
              <p class="text-xs text-muted-foreground mt-0.5">
                Mínimo por gestor
              </p>
            </div>
            <div class="rounded-lg border p-3 text-center">
              <p class="text-2xl font-bold">
                {{ balanceResult.max_open }}
              </p>
              <p class="text-xs text-muted-foreground mt-0.5">
                Máximo por gestor
              </p>
            </div>
          </div>

          <div>
            <p class="text-xs text-muted-foreground mb-2">
              Carga por gestor após redistribuição
              (chamados <strong>atribuídos</strong> em aberto).
            </p>
            <div class="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-8 text-center">
                      #
                    </TableHead>
                    <TableHead>
                      <button
                        class="flex items-center gap-1 hover:text-foreground"
                        @click="alternarOrdem('nome')"
                      >
                        Gestor
                        <Icon
                          :icon="sortKey === 'nome' ? (sortAsc ? 'lucide:chevron-up' : 'lucide:chevron-down') : 'lucide:chevrons-up-down'"
                          class="size-3"
                        />
                      </button>
                    </TableHead>
                    <TableHead>
                      <button
                        class="flex items-center gap-1 hover:text-foreground"
                        @click="alternarOrdem('carga')"
                      >
                        Carga
                        <Icon
                          :icon="sortKey === 'carga' ? (sortAsc ? 'lucide:chevron-up' : 'lucide:chevron-down') : 'lucide:chevrons-up-down'"
                          class="size-3"
                        />
                      </button>
                    </TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="(row, i) in loadRowsSorted"
                    :key="row.id"
                  >
                    <TableCell class="text-center text-muted-foreground text-xs">
                      {{ i + 1 }}
                    </TableCell>
                    <TableCell>
                      <p class="font-medium text-sm">
                        {{ row.nome }}
                      </p>
                      <p class="text-xs text-muted-foreground">
                        {{ row.email }}
                      </p>
                    </TableCell>
                    <TableCell>
                      <Badge
                        :variant="row.carga === balanceResult!.max_open ? 'default' : 'secondary'"
                      >
                        {{ row.carga }}
                      </Badge>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>
        </CardContent>
      </Card>
    </template>

    <AlertDialog
      :open="unassignAberto"
      @update:open="unassignAberto = $event"
    >
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle class="flex items-center gap-2">
            <Icon
              icon="lucide:triangle-alert"
              class="size-5 text-orange-500"
            />
            Desatribuir chamados em aberto
          </AlertDialogTitle>
          <AlertDialogDescription class="space-y-2">
            <p>
              Esta ação remove o responsável de <strong>todos</strong> os chamados
              com status <code>open</code> ou <code>in_progress</code>.
            </p>
            <p>
              Chamados resolvidos ou fechados não são afetados.
              Após desatribuir, use <strong>Redistribuir</strong> para
              realocar a fila do zero.
            </p>
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel :disabled="runningUnassign">
            Cancelar
          </AlertDialogCancel>
          <AlertDialogAction
            :disabled="runningUnassign"
            class="bg-orange-600 text-white hover:bg-orange-700 gap-2"
            @click.prevent="confirmarUnassign"
          >
            <Icon
              v-if="runningUnassign"
              icon="lucide:loader-circle"
              class="size-4 animate-spin"
            />
            Confirmar desatribuição
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>
