import { ApiError } from '../ports/http'

export function useApiError() {
  function messageOf(error: unknown): string {
    if (error instanceof ApiError) {
      return error.message
    }

    if (error instanceof Error && error.message) {
      return error.message
    }

    return 'Não foi possível concluir a operação.'
  }

  function fieldMessages(error: unknown): string[] {
    if (!(error instanceof ApiError)) {
      return []
    }

    return Object.values(error.errors).flat()
  }

  function errorsFor(error: unknown): Record<string, string[]> {
    if (!(error instanceof ApiError)) {
      return {}
    }

    return error.errors
  }

  function messageFor(error: unknown, field: string): string {
    if (!(error instanceof ApiError)) {
      return ''
    }

    return error.fieldErrors(field)[0] ?? ''
  }

  function statusOf(error: unknown): number {
    return error instanceof ApiError ? error.status : 0
  }

  function isUnauthenticated(error: unknown): boolean {
    return error instanceof ApiError && error.isUnauthenticated
  }

  function isForbidden(error: unknown): boolean {
    return error instanceof ApiError && error.isForbidden
  }

  function isValidation(error: unknown): boolean {
    return error instanceof ApiError && error.isValidation
  }

  function isSessionError(error: unknown): boolean {
    return isUnauthenticated(error)
  }

  return {
    messageOf,
    fieldMessages,
    errorsFor,
    messageFor,
    statusOf,
    isUnauthenticated,
    isForbidden,
    isValidation,
    isSessionError,
  }
}
