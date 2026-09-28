// https://nuxt.com/docs/api/configuration/nuxt-config
import tailwindcss from '@tailwindcss/vite'

/**
 * O token vive em `localStorage` e a API é Bearer sem cookie. Com SSR toda
 * leitura de estado precisaria de guarda `import.meta.client` e o plugin de
 * adapters rodaria duas vezes. Ferramenta interna não tem SEO a perder.
 */
export default defineNuxtConfig({

  modules: ['shadcn-nuxt', '@vueuse/nuxt', '@nuxt/eslint'],
  ssr: false,

  components: [
    { path: '~/components/ui', pathPrefix: false },
    { path: '~/modules/auth/components', pathPrefix: false },
    { path: '~/modules/users/components', pathPrefix: false },
    { path: '~/modules/tickets/components', pathPrefix: false },
  ],

  /**
   * `composable/` e `utils/` dos módulos entram no auto-import. `ports/` fica
   * de fora de propósito: port é contrato, e o import explícito documenta a
   * dependência em vez de apagá-la.
   */
  imports: {
    dirs: [
      'modules/*/composable',
      'modules/*/utils',
    ],
  },
  devtools: { enabled: true },

  css: ['~/assets/css/tailwind.css'],

  runtimeConfig: {
    public: {
      apiBase: '/api/v1',
    },
  },

  /**
   * O backend responde CORS hoje: `config/cors.php` não existe no projeto,
   * mas o framework cai no default dele, que libera `api/*` para `*`. Uma
   * chamada direta do browser ao backend funcionaria.
   *
   * O proxy mesmo assim é a escolha certa aqui, por três motivos que não
   * dependem do CORS: a origem da API some do bundle (não é preciso expor
   * `localhost:8000` para quem builda o app), o `Authorization` deixa de
   * depender de preflight no caminho crítico de cada requisição, e o app passa
   * a falar com uma origem só — o que elimina uma classe inteira de problema
   * de cookie e `same-origin` se o backend for para um domínio próprio.
   *
   * O destino é montado aqui, e não de `runtimeConfig.public`, porque
   * `routeRules` é avaliado em build — o `runtimeConfig` ainda não existe
   * quando o proxy é resolvido.
   */
  routeRules: {
    '/api/v1/**': {
      proxy: `${process.env.NUXT_API_ORIGIN || 'http://localhost:8000'}/api/v1/**`,
    },
  },

  compatibilityDate: '2025-07-15',

  // Tailwind v4 entra pelo plugin do Vite. O `@nuxtjs/tailwindcss` é da era v3
  // e não understande o motor atual.
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
