import type { Credentials, Registration, User } from '../ports/auth-repository'
import { ApiError } from '../../core/ports/http'

export function useSession() {
  const user = useState<User | null>('auth:user', () => null)

  const isAuthenticated = computed(() => user.value !== null)

  const role = computed(() => user.value?.role ?? null)
  const isAdmin = computed(() => role.value === 'admin')
  const isManager = computed(() => role.value === 'gestor' || role.value === 'admin')

  function handleSessionError(error: unknown): void {
    if (error instanceof ApiError && error.isUnauthenticated) {
      forget()
    }
  }

  function forget(): void {
    user.value = null

    // Tudo que vive em `useState` sobrevive a navegacao e escopa por sessao:
    // chamado, quadro e diretorio sao carregados sob o papel de quem logou.
    // Sem esta limpeza, o proximo usuario enxerga os dados do anterior ate a
    // proxima busca responder. Ao criar um cache novo em `useState`, inclua o
    // `reset()` dele aqui.
    useTicketList().reset()
    useTicketBoard().reset()
    useUserDirectory().reset()
  }

  async function login(credentials: Credentials): Promise<User> {
    const repository = useAuthRepository()
    const loggedUser = await repository.login(credentials)
    user.value = loggedUser
    return loggedUser
  }

  async function register(payload: Registration): Promise<User> {
    const repository = useAuthRepository()
    const newUser = await repository.register(payload)
    user.value = newUser
    return newUser
  }

  async function restore(): Promise<User | null> {
    if (user.value) {
      return user.value
    }

    const repository = useAuthRepository()

    try {
      const atual = await repository.me()
      user.value = atual
      return atual
    }
    catch (error) {
      handleSessionError(error)

      if (!(error instanceof ApiError && error.isUnauthenticated)) {
        throw error
      }

      return null
    }
  }

  async function logout(): Promise<void> {
    try {
      await $fetch('/api/v1/logout', { method: 'POST' })
    }
    catch {
      // Encerrar localmente mesmo se o servidor nao responder: negar a saida
      // e pior do que deixar um token morto para tras.
    }
    finally {
      forget()
      await navigateTo('/login', { replace: true })
    }
  }

  return {
    user: readonly(user),
    role,
    isAuthenticated,
    isAdmin,
    isManager,
    hasToken: isAuthenticated,
    login,
    register,
    logout,
    restore,
    forget,
    handleSessionError,
  }
}
