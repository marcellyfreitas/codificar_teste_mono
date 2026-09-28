import { ApiError } from '../ports/http'

/**
 * Normaliza qualquer erro para algo que possa virar toast ou mensagem de
 * formulário. Ponto único de tradução de erro: nenhuma tela deveria fazer
 * `catch (e) { toast.error(e.message) }`, porque `e` pode ser um `TypeError`
 * de código, não uma falha da API.
 */
export function useApiError() {
  /**
   * A mensagem de exibição. Prefere a do backend — ela é escrita em
   * português e diz exatamente o que a policy ou a validação recusou — e cai
   * num genérico quando o erro não veio da API.
   */
  function messageOf(error: unknown): string {
    if (error instanceof ApiError) {
      return error.message
    }

    if (error instanceof Error && error.message) {
      return error.message
    }

    return 'Não foi possível concluir a operação.'
  }

  /**
   * Todas as mensagens de validação, achatadas numa lista. Serve para o toast
   * quando o formulário não tem campo para receber o erro.
   */
  function fieldMessages(error: unknown): string[] {
    if (!(error instanceof ApiError)) {
      return []
    }

    return Object.values(error.errors).flat()
  }

  /** Anexa as mensagens de um campo ao objeto que o formulário controla. */
  function errorsFor(error: unknown): Record<string, string[]> {
    if (!(error instanceof ApiError)) {
      return {}
    }

    return error.errors
  }

  /**
   * Mensagem de um campo, ou string vazia. O `<FormMessage>` do shadcn espera
   * string, não array.
   */
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

  /**
   * Mensagem adequada para 401. O app não distingue "token expirado" de
   * "nunca teve token": nos dois casos a resposta certa é a mesma — limpar a
   * sessão e mandar para o login.
   */
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
