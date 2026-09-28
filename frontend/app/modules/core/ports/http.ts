/**
 * Contrato de saída para a rede. Nenhuma regra de negócio aqui: o módulo que
 * chama implementa a intenção (listar chamados, salvar, …) e este port
 * concern-se só com transporte e forma do erro.
 */
export interface HttpPort {
  /** Resposta paginada do Laravel, já com o envelope aberto. */
  get<T>(path: string, query?: QueryParams, options?: RequestOptions): Promise<T>,

  post<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T>,

  put<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T>,

  /**
   * `DELETE /tickets/{id}` responde 204 sem corpo. Por isso o retorno é
   * `Promise<void>` e o adapter não tenta parsear nada.
   */
  delete(path: string, options?: RequestOptions): Promise<void>,
}

export type QueryParams = Record<
  string,
  string | number | boolean | null | undefined
>

export interface RequestOptions {
  /** Cancela a requisição quando o componente é desmontado. */
  signal?: AbortSignal,
  /** Numero de tentativas em erro de rede ou 5xx. Padrão: 0. */
  retries?: number,
}

/**
 * Erro normalizado da API.
 *
 * `errors` traz apenas as mensagens por campo do Laravel. O resto do corpo da
 * resposta é descartado na origem — com `APP_DEBUG=true` no backend, 403 e 404
 * voltam com a stack trace inteira, e espalhar isso num toast derruba a tela.
 */
export class ApiError extends Error {
  readonly status: number

  readonly errors: Record<string, string[]>

  constructor(
    message: string,
    status: number,
    errors: Record<string, string[]> = {},
  ) {
    super(message)

    this.name = 'ApiError'
    this.status = status
    this.errors = errors

    // Necessário porque o `Error` perde a prototype chain quando o TS compila
    // para ES5, e o `instanceof ApiError` é o que o app usa para decidir entre
    // toast de erro e redirecionamento para o login.
    Object.setPrototypeOf(this, ApiError.prototype)
  }

  /** Mensagens de um campo específico, para colar no formulário. */
  fieldErrors(field: string): string[] {
    return this.errors[field] ?? []
  }

  get isUnauthenticated(): boolean {
    return this.status === 401
  }

  get isForbidden(): boolean {
    return this.status === 403
  }

  /** 422 é a resposta de validação do Laravel. */
  get isValidation(): boolean {
    return this.status === 422
  }
}
