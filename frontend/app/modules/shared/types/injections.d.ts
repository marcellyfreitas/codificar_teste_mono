import type { AuthRepository } from '~/modules/auth/ports/auth-repository'
import type { HttpPort } from '~/modules/core/ports/http'

declare module '#app' {
  interface NuxtApp {
    $http: HttpPort,
    $authRepository: AuthRepository,
  }
}

declare module 'vue' {
  interface ComponentCustomProperties {
    $http: HttpPort,
    $authRepository: AuthRepository,
  }
}

export {}
