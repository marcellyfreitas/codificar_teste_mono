export const AUTH_TOKEN_STORAGE_KEY = 'chamados:token'

export function useAuthToken() {
  return {
    token: computed(() => null as null),
    isAuthenticated: computed(() => false),
    setToken: (_value: string) => {},
    clear: () => {},
  }
}
