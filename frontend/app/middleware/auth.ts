/**
 * `auth` — a rota exige sessão.
 *
 * Aplicado por nome: `definePageMeta({ middleware: ['auth'] })`. A alternativa
 * a `global: true` em `nuxt.config` é que este é o único grupo que exige
 * sessão; o resto do app decide por página, e um guard global esconderia
 * `/login` atrás do próprio login.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const { requireAuth } = useAuthGuard()

  return requireAuth(to)
})
