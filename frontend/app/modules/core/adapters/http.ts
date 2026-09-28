import { ApiError } from '../ports/http'

import type { HttpPort, QueryParams, RequestOptions } from '../ports/http'

/**
 * Única implementação de `HttpPort` sobre `$fetch`, e o único lugar do
 * código que fala com a rede.
 *
 * Todo o resto do app depende do port, nunca de `$fetch` direto. Isso mantém
 * a substituição por um dublê em teste, ou a troca de transporte, em um arquivo
 * só.
 */
export class FetchHttpAdapter implements HttpPort {
  constructor(
    private readonly baseUrl: string,
    private readonly getToken: () => string | null,
  ) {}

  async get<T>(
    path: string,
    query?: QueryParams,
    options: RequestOptions = {},
  ): Promise<T> {
    return await this.request<T>(() =>
      $fetch<T>(this.url(path), {
        query: this.cleanQuery(query),
        method: 'GET',
        ...this.fetchOptions(options),
      }),
    )
  }

  async post<T>(
    path: string,
    body?: unknown,
    options: RequestOptions = {},
  ): Promise<T> {
    return await this.request<T>(() =>
      $fetch<T>(this.url(path), {
        // O `$fetch` só aceita corpo serializável; `unknown` passa, mas o
        // tipo do ofetch não. Todo payload deste app é um objeto plano, e o
        // cast deixa isso explícito em vez de silencioso.
        body: body as Record<string, unknown> | undefined,
        method: 'POST',
        ...this.fetchOptions(options),
      }),
    )
  }

  async put<T>(
    path: string,
    body?: unknown,
    options: RequestOptions = {},
  ): Promise<T> {
    return await this.request<T>(() =>
      $fetch<T>(this.url(path), {
        body: body as Record<string, unknown> | undefined,
        method: 'PUT',
        ...this.fetchOptions(options),
      }),
    )
  }

  /**
   * Sem parse: o backend responde 204 sem corpo aqui, e `JSON.parse('')`
   * estouraria. O `void` no retorno é o contrato — quem chama não espera dado.
   */
  async delete(path: string, options: RequestOptions = {}): Promise<void> {
    await this.request<undefined>(() =>
      $fetch<undefined>(this.url(path), {
        method: 'DELETE',
        ...this.fetchOptions(options),
      }),
    )
  }

  private url(path: string): string {
    return `${this.baseUrl}${path.startsWith('/') ? path : `/${path}`}`
  }

  private fetchOptions(options: RequestOptions): {
    headers: Record<string, string>,
    signal?: AbortSignal,
    retry: number,
  } {
    const token = this.getToken()

    // `Record<string, string>` explícito, e não o objeto literal condicional:
    // o `{}` sem chave inferia `{ Authorization?: undefined }`, que o tipo de
    // headers do `$fetch` recusa porque `undefined` não é `string`.
    const headers: Record<string, string> = {}

    if (token) {
      headers.Authorization = `Bearer ${token}`
    }

    return {
      headers,
      signal: options.signal,
      // O token nunca é trazido por cookie, então 401 aqui é sessão expirada
      // de verdade, e não um 401 de CSRF que valha repetir.
      retry: options.retries ?? 0,
    }
  }

  /**
   * `?page=undefined` na URL é lixo que volta como string `"undefined"` para
   * qualquer parser do outro lado. Filtro fora daqui, uma vez só.
   */
  private cleanQuery(query?: QueryParams): QueryParams | undefined {
    if (!query) {
      return undefined
    }

    const cleaned = Object.fromEntries(
      Object.entries(query).filter(
        ([, value]) => value !== undefined && value !== null && value !== '',
      ),
    )

    return Object.keys(cleaned).length > 0 ? cleaned : undefined
  }

  private async request<T>(call: () => Promise<T>): Promise<T> {
    try {
      return await call()
    }
    catch (error) {
      throw this.normalize(error)
    }
  }

  /**
   * Converte o `FetchError` do ofetch em `ApiError`, lendo **apenas** `message`
   * e `errors` do corpo. Nunca espalhar o corpo: com `APP_DEBUG=true` o
   * backend manda a stack trace inteira em 403 e 404.
   */
  private normalize(error: unknown): ApiError {
    if (error instanceof ApiError) {
      return error
    }

    const fetchError = error as {
      status?: number,
      statusCode?: number,
      data?: unknown,
      message?: string,
    }

    const status = fetchError.status ?? fetchError.statusCode ?? 0
    const body = this.parseBody(fetchError.data)

    return new ApiError(
      body?.message ?? fetchError.message ?? 'Não foi possível concluir a operação.',
      status,
      body?.errors ?? {},
    )
  }

  /**
   * O corpo só é JSON quando o status é de erro. Em 204 — e no DELETE — não há
   * o que ler, e o `ofetch` deixa `data` como string vazia.
   */
  private parseBody(data: unknown): { message?: string, errors?: Record<string, string[]> } | null {
    if (!data || typeof data !== 'object') {
      return null
    }

    const body = data as { message?: unknown, errors?: unknown }

    const errors
      = body.errors && typeof body.errors === 'object'
        ? Object.fromEntries(
          Object.entries(body.errors as Record<string, unknown>).map(
            ([field, messages]) => [
              field,
              Array.isArray(messages) ? messages.map(String) : [String(messages)],
            ],
          ),
        )
        : {}

    return {
      message: typeof body.message === 'string' ? body.message : undefined,
      errors,
    }
  }
}
