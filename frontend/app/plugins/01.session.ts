/**
 * Reidrata a sessão a partir do token guardado.
 *
 * Precisa rodar depois de `00.adapters` (que monta os adapters) e antes dos
 * middlewares de rota, que já leem `isAuthenticated`. Sem este plugin, um F5
 * na página autenticada jogaria para `/login` mesmo com a sessão válida.
 *
 * Sem `enforce: 'pre'`: essa flag tiraria o plugin da fila por nome e o faria
 * rodar antes de tudo, inclusive antes do composition root.
 */
export default defineNuxtPlugin({
  name: '01.session',
  async setup() {
    const { restore } = useSession()

    try {
      await restore()
    }
    catch (error) {
      // Falha de rede ao reidratar não pode derrubar o app inteiro: o pior
      // caso é o usuário cair no login, e ele ainda pode tentar de novo. Um
      // token inválido já foi limpo por `restore`.
      console.warn('[sessão] não foi possível reidratar a sessão.', error)
    }
  },
})
