import type { H3Event } from 'h3'

interface BackendBody {
  message?: string,
  errors?: Record<string, string[]>,
}

export function backendErrorBody(event: H3Event, error: unknown): BackendBody {
  const fetchError = error as {
    statusCode?: number,
    status?: number,
    data?: unknown,
    message?: string,
  }

  const status = fetchError.statusCode ?? fetchError.status ?? 500
  const body
    = fetchError.data && typeof fetchError.data === 'object'
      ? (fetchError.data as BackendBody)
      : null

  setResponseStatus(event, status)

  return {
    message: body?.message ?? fetchError.message ?? 'Não foi possível concluir a operação.',
    errors: body?.errors ?? {},
  }
}
