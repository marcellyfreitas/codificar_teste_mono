import type { RouteLocationNormalizedLoaded } from 'vue-router'

export function useAuthGuard() {
  const { isAuthenticated } = useSession()

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
  const destino = to.fullPath

  return destino.startsWith('/') && !destino.startsWith('//')
    ? { redirect: destino }
    : {}
}
