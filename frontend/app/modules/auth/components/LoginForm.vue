<script setup lang="ts">
import { ApiError } from '~/modules/core/ports/http'
import { isValid, validateLogin } from '~/modules/auth/utils/credentials'
// `vue-sonner` exporta a função `toast`, não um composable `useToast`. O
// `useToast` que parece existir é o do próprio Nuxt, que resolve para outra
// coisa e joga `ReferenceError` em tempo de execução — bem num `try/catch` que
// o typecheck não pega.
import { toast } from 'vue-sonner'

import type { LoginDraft, LoginErrors } from '~/modules/auth/utils/credentials'

/**
 * Formulário de login.
 *
 * O ponto que exige atenção: senha errada responde **422**, não 401 — o
 * backend lança `ValidationException`, não falha de autenticação. Código que
 * só trata 401 cairia no erro genérico e a mensagem "As credenciais fornecidas
 * estão incorretas." nunca apareceria no campo.
 *
 * Por isso os 422 são tratados como erro de campo, e o `message` do envelope
 * só vira toast quando não há campo correspondente.
 */
const draft = reactive<LoginDraft>({ email: '', password: '' })
const errors = reactive<LoginErrors>({})
const submitting = ref(false)

const { login } = useSession()
const { messageOf, errorsFor } = useApiError()

const rota = useRoute()
const router = useRouter()

function campoError(campo: keyof LoginDraft): string[] | undefined {
  return errors[campo]
}

function limparErro(campo: keyof LoginDraft): void {
  errors[campo] = undefined
}

/** Zera os erros de um envio anterior, para o 422 novo não se somar ao velho. */
function limparTodos(): void {
  for (const campo of Object.keys(errors) as Array<keyof LoginDraft>) {
    errors[campo] = undefined
  }
}

async function enviar(): Promise<void> {
  limparTodos()

  const locais = validateLogin(draft)

  if (!isValid(locais)) {
    Object.assign(errors, locais)

    return
  }

  submitting.value = true

  try {
    const session = await login({ email: draft.email, password: draft.password })

    toast.success(`Bem-vindo, ${session.user.name}.`)

    // O `redirect` vem do guard. Sanear o valor antes de navegar: sem isso, um
    // `?redirect=https://exemplo.invalido` digitado à mão viraria um
    // redirecionamento para fora do app com o usuário logado.
    const destino = rota.query.redirect

    if (typeof destino === 'string' && destino.startsWith('/') && !destino.startsWith('//')) {
      await router.push(destino)

      return
    }

    await router.push('/chamados')
  }
  catch (error) {
    if (error instanceof ApiError && error.isValidation) {
      Object.assign(errors, errorsFor(error))

      return
    }

    if (error instanceof ApiError && error.isUnauthenticated) {
      Object.assign(errors, { email: error.message })

      return
    }

    toast.error(messageOf(error))
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Entrar</CardTitle>
      <CardDescription>
        Use o e-mail e a senha da sua conta.
      </CardDescription>
    </CardHeader>

    <CardContent>
      <form
        class="flex flex-col gap-4"
        novalidate
        @submit.prevent="enviar"
      >
        <Field :invalid="Boolean(errors.email)">
          <FieldLabel for="login-email">
            E-mail
          </FieldLabel>
          <Input
            id="login-email"
            v-model="draft.email"
            type="email"
            autocomplete="email"
            placeholder="voce@empresa.com"
            :disabled="submitting"
            @input="limparErro('email')"
          />
          <FieldError :errors="campoError('email')" />
        </Field>

        <Field :invalid="Boolean(errors.password)">
          <FieldLabel for="login-password">
            Senha
          </FieldLabel>
          <Input
            id="login-password"
            v-model="draft.password"
            type="password"
            autocomplete="current-password"
            placeholder="••••••••"
            :disabled="submitting"
            @input="limparErro('password')"
          />
          <FieldError :errors="campoError('password')" />
        </Field>

        <Button
          type="submit"
          class="w-full"
          :disabled="submitting"
        >
          {{ submitting ? 'Entrando…' : 'Entrar' }}
        </Button>
      </form>
    </CardContent>
  </Card>
</template>
