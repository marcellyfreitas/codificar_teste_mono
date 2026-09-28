// @ts-check
import withNuxt from './.nuxt/eslint.config.mjs'

export default withNuxt(
  {
    // `app/components/ui/` e `app/lib/` são gerados pela CLI do shadcn-vue.
    // Lintá-los só produziria ruído que o próximo `npx shadcn-vue add` volta a
    // introduzir, e formatá-los geraria diff na próxima atualização de upstream.
    // O gate vale para o código que este app mantém.
    ignores: ['app/components/ui/**', 'app/lib/**'],
  },
  {
    rules: {
      '@stylistic/quotes': ['error', 'single'],
      '@stylistic/semi': ['error', 'never'],
      '@stylistic/indent': ['error', 2],
      '@stylistic/comma-dangle': ['error', 'always-multiline'],
      '@stylistic/member-delimiter-style': [
        'error',
        {
          multiline: { delimiter: 'comma', requireLast: true },
          singleline: { delimiter: 'comma', requireLast: false },
        },
      ],
    },
  },
)
