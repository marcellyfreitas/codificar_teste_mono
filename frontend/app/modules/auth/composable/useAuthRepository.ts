import type { AuthRepository } from '../ports/auth-repository'

/**
 * Composition root de `auth`.
 *
 * A UI chama `useAuthRepository()` e recebe a interface. O adapter concreto é
 * registrado uma vez, no plugin, e o `provide` o distribui. Trocar o adapter
 * HTTP por um dublê — num teste, ou num futuro adapter com cache — é mexer em
 * um arquivo só, sem tocar em componente.
 */
export function useAuthRepository(): AuthRepository {
  return useNuxtApp().$authRepository
}
