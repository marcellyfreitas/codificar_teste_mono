import type { HttpPort } from '~/modules/core/ports/http'
import type { Paginated } from '~/modules/core/utils/response'
import type { User, UserDirectory, UserDirectoryParams } from '../ports/user-directory'

export class HttpUserDirectory implements UserDirectory {
  constructor(private readonly http: HttpPort) {}

  async list(params: UserDirectoryParams = {}): Promise<Paginated<User>> {
    return await this.http.get<Paginated<User>>('/users', {
      role: params.role,
      search: params.search,
      per_page: params.perPage,
      page: params.page,
    })
  }
}
