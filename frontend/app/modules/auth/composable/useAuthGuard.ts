import type { RouteLocationNormalizedLoaded } from 'vue-router'

/**
 * Guard de rota nomeado, invocado pelos middlewares `auth` e `guest`.
 *
 * O destino vem por parâmetro, e não de `useRoute()`: dentro de um middleware
 * o `useRoute()` devolve a rota anterior (o Nuxt avisa com `NUXT_E2005`), e o
 * `redirect` do guard acabaria apontando para onde o usuário veio, não para
 * onde ele queria ir.
 */
export function useAuthGuard() {
  const { isAuthenticated } = useSession()

  /**
   * Protege a rota atual. `guestOnly` inverte o sentido: `/login` e
   * `/register` não fazem sentido para quem já está autenticado, e mandar
   * quem já entrou para o login seria um laço.
   *
   * O retorno é o valor de `navigateTo`, não `void`: o middleware do Nuxt
   * interpreta um valor não-undefined como "navegou, para a interpolação de
   * rota", e devolver `void` explicitamente quebra essa sinalização.
   */
  function requireAuth(
    to: RouteLocationNormalizedLoaded,
    guestOnly = false,
  ) {
    if (guestOnly) {
      if (isAuthenticated.value) {
        return navigateTo('/chamados', { replace: true })
      }

      return undefined
    }

    if (isAuthenticated.value) {
      return undefined
    }

    return navigateTo({ path: '/login', query: destinoDe(to) }, { replace: true })
  }

  return { requireAuth }
}

function destinoDe(to: RouteLocationNormalizedLoaded): Record<string, string> {
  // Só `fullPath` relativo. Aceitar uma URL absoluta abriria um redirecionamento
  // para fora do app com o usuário prestes a autenticar.
  const destino = to.fullPath

  return destino.startsWith('/') && !destino.startsWith('//')
    ? { redirect: destino }
    : {}
}
