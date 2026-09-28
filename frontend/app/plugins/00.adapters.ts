import { FetchHttpAdapter } from '~/modules/core/adapters/http'
import { HttpAuthRepository } from '~/modules/auth/adapters/auth-repository.http'
import { HttpUserDirectory } from '~/modules/users/adapters/user-directory.http'
import { HttpTicketRepository } from '~/modules/tickets/adapters/ticket-repository.http'

export default defineNuxtPlugin({
  name: '00.adapters',
  setup() {
    const config = useRuntimeConfig()

    const http = new FetchHttpAdapter(config.public.apiBase)

    return {
      provide: {
        http,
        authRepository: new HttpAuthRepository(http),
        userDirectory: new HttpUserDirectory(http),
        ticketRepository: new HttpTicketRepository(http),
      },
    }
  },
})
