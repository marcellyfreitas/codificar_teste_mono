export default defineNuxtRouteMiddleware(async (to) => {
  const { requireAuth } = useAuthGuard()

  return requireAuth(to)
})
