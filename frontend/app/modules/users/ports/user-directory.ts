import type { User, UserRole } from '~/modules/auth/ports/auth-repository'
import type { Paginated } from '~/modules/core/utils/response'

export type { User }

export interface UserDirectoryParams {
  role?: UserRole,
  search?: string,
  perPage?: number,
  page?: number,
}

export interface UserDirectory {
  list(params?: UserDirectoryParams): Promise<Paginated<User>>,
}
