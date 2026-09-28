import { AUTH_TOKEN_STORAGE_KEY } from '~/modules/core/composable/useAuthToken'

export default defineNuxtPlugin({
  name: '01.session',
  async setup() {
    if (import.meta.client) {
      localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY)
    }

    const { restore } = useSession()

    try {
      await restore()
    }
    catch (error) {
      console.warn('[sessão] não foi possível reidratar a sessão.', error)
    }
  },
})
