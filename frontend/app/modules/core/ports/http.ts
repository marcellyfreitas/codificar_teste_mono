export interface HttpPort {
  get<T>(path: string, query?: QueryParams, options?: RequestOptions): Promise<T>,
  post<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T>,
  put<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T>,
  delete(path: string, options?: RequestOptions): Promise<void>,
}

export type QueryParams = Record<
  string,
  string | number | boolean | null | undefined
>

export interface RequestOptions {
  signal?: AbortSignal,
  retries?: number,
}

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

    Object.setPrototypeOf(this, ApiError.prototype)
  }

  fieldErrors(field: string): string[] {
    return this.errors[field] ?? []
  }

  get isUnauthenticated(): boolean {
    return this.status === 401
  }

  get isForbidden(): boolean {
    return this.status === 403
  }

  get isValidation(): boolean {
    return this.status === 422
  }
}
