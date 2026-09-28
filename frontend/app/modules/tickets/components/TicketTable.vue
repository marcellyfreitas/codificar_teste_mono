<script setup lang="ts">
import type { Ticket } from '~/modules/tickets/ports/ticket-repository'
import type { Paginated } from '~/modules/core/utils/response'

const props = defineProps<{
  page: Paginated<Ticket>,
  loading: boolean,
}>()

const emit = defineEmits<{
  ver: [ticket: Ticket],
  editar: [ticket: Ticket],
  excluir: [ticket: Ticket],
  'update:page': [p: number],
}>()

const currentPage = computed(() => props.page.current_page)
const lastPage = computed(() => props.page.last_page)
const total = computed(() => props.page.total)
const showing = computed(() => props.page.data.length)
const truncada = computed(() => showing.value < total.value && lastPage.value === 1)
</script>

<template>
  <div class="space-y-3">
    <!-- Contagem -->
    <div
      v-if="!loading && page.total > 0"
      class="text-sm text-muted-foreground"
    >
      Mostrando {{ showing }} de {{ total }} chamados
    </div>

    <!-- Tabela -->
    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead class="w-16">
              #
            </TableHead>
            <TableHead>Título</TableHead>
            <TableHead>Status</TableHead>
            <TableHead>Prioridade</TableHead>
            <TableHead>Autor</TableHead>
            <TableHead>Responsável</TableHead>
            <TableHead>Criado</TableHead>
            <TableHead class="w-10" />
          </TableRow>
        </TableHeader>

        <TableBody>
          <!-- Skeleton enquanto carrega -->
          <template v-if="loading">
            <TableRow
              v-for="i in 8"
              :key="i"
            >
              <TableCell
                v-for="j in 8"
                :key="j"
              >
                <Skeleton class="h-4 w-full" />
              </TableCell>
            </TableRow>
          </template>

          <!-- Dados -->
          <template v-else-if="page.data.length > 0">
            <TicketRow
              v-for="ticket in page.data"
              :key="ticket.id"
              :ticket="ticket"
              @ver="emit('ver', $event)"
              @editar="emit('editar', $event)"
              @excluir="emit('excluir', $event)"
            />
          </template>

          <!-- Estado vazio -->
          <template v-else>
            <TableRow>
              <TableCell
                colspan="8"
                class="h-32 text-center"
              >
                <Empty
                  title="Nenhum chamado encontrado"
                  description="Tente ajustar os filtros ou criar um novo chamado."
                />
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>

    <!-- Aviso de truncagem (só quando há 1 página e total > data.length) -->
    <p
      v-if="truncada"
      class="text-xs text-yellow-700 dark:text-yellow-400"
    >
      A janela de data retornou {{ total }} chamados mas a página está limitada a
      {{ showing }}. Refine os filtros para ver todos.
    </p>

    <!-- Paginação -->
    <Pagination
      v-if="lastPage > 1"
      :total="total"
      :items-per-page="page.per_page"
      :sibling-count="1"
      show-edges
      :default-page="currentPage"
      @update:page="emit('update:page', $event)"
    >
      <PaginationContent
        v-slot="{ items }"
        class="flex items-center gap-1"
      >
        <PaginationFirst />
        <PaginationPrevious />

        <template
          v-for="item in items"
          :key="item.type === 'page' ? item.value : item.type"
        >
          <PaginationItem
            v-if="item.type === 'page'"
            :value="item.value"
            :is-active="item.value === currentPage"
          >
            {{ item.value }}
          </PaginationItem>
          <PaginationEllipsis v-else />
        </template>

        <PaginationNext />
        <PaginationLast />
      </PaginationContent>
    </Pagination>
  </div>
</template>
