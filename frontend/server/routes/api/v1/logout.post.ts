import { SESSION_COOKIE, cookieOptions } from '../../../utils/auth-cookie'

export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const token = getCookie(event, SESSION_COOKIE)

  if (token) {
    await $fetch(`${config.apiOrigin}/api/v1/logout`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
    }).catch(() => {})
  }

  setCookie(event, SESSION_COOKIE, '', { ...cookieOptions, maxAge: 0 })

  return { message: 'Sessão encerrada.' }
})
