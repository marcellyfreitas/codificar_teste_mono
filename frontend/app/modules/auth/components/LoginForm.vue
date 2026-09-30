<script setup lang="ts">
import { ApiError } from '~/modules/core/ports/http'
import { isValid, validateLogin } from '~/modules/auth/utils/credentials'
import { toast } from 'vue-sonner'

import type { LoginDraft, LoginErrors } from '~/modules/auth/utils/credentials'

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

    toast.success(`Bem-vindo, ${session.name}.`)

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

      toast.error(messageOf(error))

      return
    }

    if (error instanceof ApiError && error.isUnauthenticated) {
      Object.assign(errors, { email: error.message })

      toast.error(error.message)

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
