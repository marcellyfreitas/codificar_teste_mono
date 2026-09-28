/**
 * `guest` — a rota só faz sentido sem sessão.
 *
 * `/login` e `/register` usam este. Sem ele, quem já está autenticado e digita
 * `/login` na barra de endereço vê o formulário em vez de ser levado ao app.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const { requireAuth } = useAuthGuard()

  return requireAuth(to, true)
})
