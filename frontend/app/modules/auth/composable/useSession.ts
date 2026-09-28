import type { Credentials, Registration, Session, User } from '../ports/auth-repository'
import { ApiError } from '../../core/ports/http'

/**
 * Estado da sessão e as três operações que a mudam.
 *
 * `user` e `token` vivem em `useState`, não em `ref` de módulo: o `useState` do
 * Nuxt sobrevive a HMR e é compartilhado entre todos os chamadores, que é o que
 * faz o guard e o layout enxergarem a mesma sessão.
 *
 * O token em si fica em `localStorage` (via `useAuthToken`, no `core`), porque
 * precisa sobreviver ao F5; o usuário reidratado é re-buscado por `me()` a cada
 * boot, já que o backend tem `/me` e não vale guardar dado de pessoa em dois
 * lugares.
 */
export function useSession() {
  const user = useState<User | null>('auth:user', () => null)
  const { token, setToken, clear: clearToken } = useAuthToken()

  /**
   * Token presente **e** usuário conhecido. Exigir os dois evita o estado
   * "meio-autenticado", em que o layout esconde a sidebar mas as chamadas já
   * saem com o token.
   */
  const isAuthenticated = computed(
    () => Boolean(token.value) && user.value !== null,
  )

  /** Papel, para ramificar a UI sem repetir `user.value?.role` em cada tela. */
  const role = computed(() => user.value?.role ?? null)

  const isAdmin = computed(() => role.value === 'admin')

  const isManager = computed(() => role.value === 'gestor' || role.value === 'admin')

  /**
   * 401 significa sessão encerrada, e não "token inválido": o backend não
   * distingue token expirado de token ausente, e o token dele não expira. Nos
   * dois casos a resposta é a mesma — limpar tudo e voltar ao login.
   */
  function handleSessionError(error: unknown): void {
    if (error instanceof ApiError && error.isUnauthenticated) {
      forget()
    }
  }

  function forget(): void {
    clearToken()
    user.value = null
  }

  /**
   * Não navega: quem chama decide para onde ir, porque o destino depende de
   * quem disparou — o guard manda por query string, o formulário da home
   * manda para a home. Empurrar `navigateTo` para dentro daqui apagaria essa
   * distinção.
   */
  async function login(credentials: Credentials): Promise<Session> {
    const repository = useAuthRepository()
    const session = await repository.login(credentials)

    setToken(session.token)
    user.value = session.user

    return session
  }

  async function register(payload: Registration): Promise<Session> {
    const repository = useAuthRepository()
    const session = await repository.register(payload)

    setToken(session.token)
    user.value = session.user

    return session
  }

  /**
   * Recupera o usuário a partir do token guardado. Devolve `null` em vez de
   * lançar quando o token é inválido: quem chama é o plugin de reidratação, e
   * um token forjado no `localStorage` acontece o tempo todo no dia a dia do
   * desenvolvimento, e não é um erro de aplicação.
   */
  async function restore(): Promise<User | null> {
    if (!token.value) {
      return null
    }

    // Já resolvido nesta sessão: não há motivo para chamar `/me` de novo.
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

  /**
   * Logout. O `replace` importa: sem ele, o botão "voltar" do browser
   * devolve o usuário à página protegida que ele acabou de abandonar, e o
   * guard o manda para o login num loop.
   */
  async function logout(): Promise<void> {
    forget()

    await navigateTo('/login', { replace: true })
  }

  return {
    user: readonly(user),
    role,
    isAuthenticated,
    isAdmin,
    isManager,
    hasToken: computed(() => Boolean(token.value)),
    login,
    register,
    logout,
    restore,
    forget,
    handleSessionError,
  }
}
