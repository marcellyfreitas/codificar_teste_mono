import { FetchHttpAdapter } from '~/modules/core/adapters/http'
import { HttpAuthRepository } from '~/modules/auth/adapters/auth-repository.http'
import { useAuthToken } from '~/modules/core/composable/useAuthToken'

/**
 * Composition root: o único lugar que instancia adapters concretos.
 *
 * O `00` no nome não é decoração — o Nuxt ordena plugins por nome de arquivo, e
 * a reidratação de sessão (`01.session`) precisa de `$authRepository` pronto.
 * Com `enforce: 'pre'` no plugin de sessão, ele rodaria antes de qualquer
 * outro e chamaria `me()` num adapter inexistente.
 *
 * O `FetchHttpAdapter` recebe `getToken` como callback em vez do valor, e não
 * do token. Se recebesse o token, teria de ser recriado a cada login e logout,
 * e o adapter guardado no `nuxtApp` passaria a mandar token velho depois do
 * logout — a race clássica de sessão.
 */
export default defineNuxtPlugin({
  name: '00.adapters',
  setup() {
    const config = useRuntimeConfig()
    const { token } = useAuthToken()

    const http = new FetchHttpAdapter(config.public.apiBase, () => token.value)

    return {
      provide: {
        http,
        authRepository: new HttpAuthRepository(http),
      },
    }
  },
})
