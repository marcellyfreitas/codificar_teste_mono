import { FetchHttpAdapter } from '../adapters/http'

import type { HttpPort } from '../ports/http'

/**
 * Entrega o port único de HTTP, pronto para uso.
 *
 * O token não entra aqui: o adapter pergunta por ele a cada requisição via
 * callback, para que o logout — que limpa o `localStorage` — derrube a
 * autenticação sem precisar recriar o adapter.
 */
export function useHttp(): HttpPort {
  const config = useRuntimeConfig()
  const { token } = useAuthToken()

  const adapter = new FetchHttpAdapter(config.public.apiBase, () => token.value)

  return adapter
}

/** Versão fora de setup: para o plugin de sessão e os adapters. */
export function createHttp(baseUrl: string, getToken: () => string | null): HttpPort {
  return new FetchHttpAdapter(baseUrl, getToken)
}
