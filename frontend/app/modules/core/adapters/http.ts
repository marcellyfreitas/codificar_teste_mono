import { ApiError } from '../ports/http'

import type { HttpPort, QueryParams, RequestOptions } from '../ports/http'

export class FetchHttpAdapter implements HttpPort {
  constructor(
    private readonly baseUrl: string,
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
    credentials: RequestCredentials,
  } {
    return {
      headers: {},
      signal: options.signal,
      credentials: 'include',
      retry: options.retries ?? 0,
    }
  }

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
