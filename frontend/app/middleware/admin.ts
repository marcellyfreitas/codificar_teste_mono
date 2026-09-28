export default defineNuxtRouteMiddleware(() => {
  const { isAdmin, isAuthenticated } = useSession()

  if (!isAuthenticated.value) {
    return navigateTo('/login', { replace: true })
  }

  if (!isAdmin.value) {
    return navigateTo('/chamados', { replace: true })
  }
})
