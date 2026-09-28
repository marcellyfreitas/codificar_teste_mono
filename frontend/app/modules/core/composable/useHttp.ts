import { FetchHttpAdapter } from '../adapters/http'

import type { HttpPort } from '../ports/http'

export function useHttp(): HttpPort {
  const config = useRuntimeConfig()
  return new FetchHttpAdapter(config.public.apiBase)
}
