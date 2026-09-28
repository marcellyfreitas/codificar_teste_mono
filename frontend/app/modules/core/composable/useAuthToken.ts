import { useStorage } from '@vueuse/core'

/**
 * Guarda do token, fora do módulo `auth` de propósito.
 *
 * O `core` monta o adapter de HTTP e precisa saber ler o token; se o token
 * morasse no módulo `auth`, teríamos `core -> auth` e `auth -> core`, e o
 * ciclo quebraria a ordem de auto-import. O módulo `auth` cuida de sessão,
 * `/me` e guards; a credencial crua é infraestrutura de transporte.
 *
 * `useStorage` já embrulha `localStorage`, que é a exigência do `FRONTEND.md`
 * §5. O nome da chave é exportado para o logout limpar a mesma entrada.
 */
export const AUTH_TOKEN_STORAGE_KEY = 'chamados:token'

export function useAuthToken() {
  // A assinatura de `useStorage` é `(key, default, storage, options)`: o
  // storage é o TERCEIRO argumento. Passar o objeto de opções no lugar dele
  // quebra em runtime com `storage.getItem is not a function` — e só na
  // primeira requisição, porque o `localStorage` só é tocado quando o adapter
  // monta.
  const token = useStorage<string | null>(AUTH_TOKEN_STORAGE_KEY, null, localStorage, {
    // Grava `null` no logout para que a chave não sobreviva a um F5 com sessão
    // encerrada, em vez de devolver o token antigo do item anterior.
    writeDefaults: true,
  })

  const isAuthenticated = computed(() => Boolean(token.value))

  function setToken(value: string): void {
    token.value = value
  }

  function clear(): void {
    token.value = null
  }

  return { token: readonly(token), accessToken: token, isAuthenticated, setToken, clear }
}
