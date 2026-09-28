import { SESSION_COOKIE, cookieOptions } from '../../../utils/auth-cookie'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const body = await readBody(event)

  const response = await $fetch<{
    message: string
    data: { user: Record<string, unknown>, access_token: string, token_type: string }
  }>(`${config.apiOrigin}/api/v1/login`, {
    method: 'POST',
    body,
  })

  setCookie(event, SESSION_COOKIE, response.data.access_token, cookieOptions)

  return {
    message: response.message,
    data: { user: response.data.user },
  }
})
