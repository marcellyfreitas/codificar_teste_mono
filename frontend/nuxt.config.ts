import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  modules: ['shadcn-nuxt', '@vueuse/nuxt', '@nuxt/eslint'],
  ssr: false,

  components: [
    { path: '~/components/ui', pathPrefix: false },
    { path: '~/modules/auth/components', pathPrefix: false },
    { path: '~/modules/users/components', pathPrefix: false },
    { path: '~/modules/tickets/components', pathPrefix: false },
  ],

  imports: {
    dirs: [
      'modules/*/composable',
      'modules/*/utils',
    ],
  },

  devtools: { enabled: true },

  css: ['~/assets/css/tailwind.css'],

  runtimeConfig: {
    apiOrigin: process.env.NUXT_API_ORIGIN || 'http://localhost:8000',
    public: {
      apiBase: '/api/v1',
    },
  },

  compatibilityDate: '2025-07-15',

  vite: {
    plugins: [tailwindcss()],
  },

  eslint: {
    config: {
      stylistic: true,
    },
  },

  shadcn: {
    prefix: '',
    componentDir: '~/components/ui',
  },
})
