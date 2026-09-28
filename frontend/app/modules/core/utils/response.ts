export interface ApiEnvelope<T> {
  message: string,
  data: T,
}

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

export function unwrap<T>(envelope: ApiEnvelope<T>): T {
  return envelope.data
}

export function messageOf(envelope: { message?: string }): string {
  return envelope.message ?? 'Operação concluída.'
}

type UnknownRecord = Record<string, unknown>

function isRecord(value: unknown): value is UnknownRecord {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

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
