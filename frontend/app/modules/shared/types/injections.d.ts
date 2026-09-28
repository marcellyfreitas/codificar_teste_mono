import type { AuthRepository } from '~/modules/auth/ports/auth-repository'
import type { HttpPort } from '~/modules/core/ports/http'
import type { UserDirectory } from '~/modules/users/ports/user-directory'
import type { TicketRepository } from '~/modules/tickets/ports/ticket-repository'

declare module '#app' {
  interface NuxtApp {
    $http: HttpPort,
    $authRepository: AuthRepository,
    $userDirectory: UserDirectory,
    $ticketRepository: TicketRepository,
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $http: HttpPort,
    $authRepository: AuthRepository,
    $userDirectory: UserDirectory,
    $ticketRepository: TicketRepository,
  }
}

export {}
