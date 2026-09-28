/**
 * Envelopes da API em um lugar só.
 *
 * A API responde em duas formas: a paginada do Resource, direto; e a de
 * ação, embrulhada em `{ message, data }`. Abrir o envelope aqui evita que
 * cada chamada repita o `.data?.data`.
 */

/** Envelope das respostas de ação: `{ message, data }`. */
export interface ApiEnvelope<T> {
  message: string,
  data: T,
}

/** Metadados de paginação do Laravel. */
export interface PaginationMeta {
  current_page: number,
  data: unknown[],
  first_page_url: string,
  from: number | null,
  last_page: number,
  last_page_url: string,
  links: Array<{ url: string | null, label: string, active: boolean }>,
  next_page_url: string | null,
  path: string,
  per_page: number,
  prev_page_url: string | null,
  to: number | null,
  total: number,
}

/** Envelope paginado: o Resource do Laravel já vem com os metadados no topo. */
export interface Paginated<T> {
  current_page: number,
  data: T[],
  first_page_url: string,
  from: number | null,
  last_page: number,
  last_page_url: string,
  next_page_url: string | null,
  path: string,
  per_page: number,
  prev_page_url: string | null,
  total: number,
}

/** Abre o envelope `{ message, data }` das respostas de ação. */
export function unwrap<T>(envelope: ApiEnvelope<T>): T {
  return envelope.data
}

/** Mensagem de sucesso vinda no envelope, para o toast. */
export function messageOf(envelope: { message?: string }): string {
  return envelope.message ?? 'Operação concluída.'
}

type UnknownRecord = Record<string, unknown>

function isRecord(value: unknown): value is UnknownRecord {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

/**
 * Valida a forma da resposta paginada em runtime.
 *
 * `$fetch` é genérico e não verifica nada: sem esta guarda, um
 * `{ message: "Unauthenticated." }` chegando por engano no lugar da lista
 * estouraria em `data.map` três telas adiante, com mensagem de `undefined`.
 */
export function isPaginated<T>(value: unknown): value is Paginated<T> {
  if (!isRecord(value)) {
    return false
  }

  return (
    Array.isArray(value.data)
    && typeof value.total === 'number'
    && typeof value.current_page === 'number'
    && typeof value.last_page === 'number'
    && typeof value.per_page === 'number'
  )
}

/**
 * Normaliza o que o Laravel devolve para uma lista vazia, em vez de deixar
 * `undefined` vazar para o template.
 */
export function toPage<T>(value: unknown): Paginated<T> {
  if (isPaginated<T>(value)) {
    return value
  }

  return {
    current_page: 1,
    data: [],
    first_page_url: '',
    from: null,
    last_page: 1,
    last_page_url: '',
    next_page_url: null,
    path: '',
    per_page: 15,
    prev_page_url: null,
    total: 0,
  }
}
