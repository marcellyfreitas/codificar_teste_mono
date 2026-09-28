import type { AuthRepository } from '../ports/auth-repository'

export function useAuthRepository(): AuthRepository {
  return useNuxtApp().$authRepository
}
