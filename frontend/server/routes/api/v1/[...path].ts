import { SESSION_COOKIE } from '../../../utils/auth-cookie'

export default defineEventHandler((event) => {
  const config = useRuntimeConfig(event)
  const token = getCookie(event, SESSION_COOKIE)

  const path = getRouterParam(event, 'path') ?? ''

  const query = getQuery(event)
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(query)) {
    if (value !== undefined && value !== null && value !== '') {
      params.set(key, String(value))
    }
  }

  const qs = params.toString()
  const target = `${config.apiOrigin}/api/v1/${path}${qs ? `?${qs}` : ''}`

  return proxyRequest(event, target, {
    fetchOptions: token
      ? { headers: { Authorization: `Bearer ${token}` } }
      : undefined,
  })
})
