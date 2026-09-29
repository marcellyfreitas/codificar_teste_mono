import type { UserRole } from '~/modules/auth/ports/auth-repository'
import type { Paginated } from '~/modules/core/utils/response'
import { toPage } from '~/modules/core/utils/response'
import type { User } from '../ports/user-directory'

export function useUserDirectory() {
  const { $userDirectory } = useNuxtApp()

  const gestores = useState<Paginated<User>>('users:gestores', () => toPage<User>(null))
  const todos = useState<Paginated<User>>('users:todos', () => toPage<User>(null))
  const loading = useState<boolean>('users:loading', () => false)
  const error = useState<string | null>('users:error', () => null)

  async function fetchGestores(search?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      gestores.value = await $userDirectory.list({
        role: 'gestor',
        search,
        perPage: 100,
      })
    }
    catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao carregar gestores.'
    }
    finally {
      loading.value = false
    }
  }

  async function fetchAll(role?: UserRole, search?: string): Promise<void> {
    loading.value = true
    error.value = null

    try {
      todos.value = await $userDirectory.list({ role, search, perPage: 100 })
    }
    catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao carregar usuários.'
    }
    finally {
      loading.value = false
    }
  }

  return {
    gestores: readonly(gestores),
    todos: readonly(todos),
    loading: readonly(loading),
    error: readonly(error),
    fetchGestores,
    fetchAll,
  }
}
