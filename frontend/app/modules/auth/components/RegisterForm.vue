<script setup lang="ts">
import { ApiError } from '~/modules/core/ports/http'
import { isValid, validateRegister } from '~/modules/auth/utils/credentials'
import { toast } from 'vue-sonner'

import type { RegisterDraft, RegisterErrors } from '~/modules/auth/utils/credentials'

const draft = reactive<RegisterDraft>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const errors = reactive<RegisterErrors>({})
const submitting = ref(false)

const { register } = useSession()
const { messageOf, errorsFor } = useApiError()
const router = useRouter()

function campoError(campo: keyof RegisterDraft): string[] | undefined {
  return errors[campo]
}

function limparErro(campo: keyof RegisterDraft): void {
  errors[campo] = undefined
}

function limparTodos(): void {
  for (const campo of Object.keys(errors) as Array<keyof RegisterDraft>) {
    errors[campo] = undefined
  }
}

async function enviar(): Promise<void> {
  limparTodos()

  const locais = validateRegister(draft)

  if (!isValid(locais)) {
    Object.assign(errors, locais)

    return
  }

  submitting.value = true

  try {
    const session = await register({ ...draft })

    toast.success(`Conta criada. Bem-vindo, ${session.name}.`)

    await router.push('/chamados')
  }
  catch (error) {
    if (error instanceof ApiError && error.isValidation) {
      Object.assign(errors, errorsFor(error))

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
      <CardTitle>Criar conta</CardTitle>
      <CardDescription>
        A conta nova começa como usuário comum.
      </CardDescription>
    </CardHeader>

    <CardContent>
      <form
        class="flex flex-col gap-4"
        novalidate
        @submit.prevent="enviar"
      >
        <Field :invalid="Boolean(errors.name)">
          <FieldLabel for="register-name">
            Nome
          </FieldLabel>
          <Input
            id="register-name"
            v-model="draft.name"
            autocomplete="name"
            placeholder="Maria Aparecida"
            :disabled="submitting"
            @input="limparErro('name')"
          />
          <FieldError :errors="campoError('name')" />
        </Field>

        <Field :invalid="Boolean(errors.email)">
          <FieldLabel for="register-email">
            E-mail
          </FieldLabel>
          <Input
            id="register-email"
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
          <FieldLabel for="register-password">
            Senha
          </FieldLabel>
          <Input
            id="register-password"
            v-model="draft.password"
            type="password"
            autocomplete="new-password"
            placeholder="mínimo 8 caracteres"
            :disabled="submitting"
            @input="limparErro('password')"
          />
          <FieldError :errors="campoError('password')" />
        </Field>

        <Field :invalid="Boolean(errors.password_confirmation)">
          <FieldLabel for="register-password-confirmation">
            Confirmar senha
          </FieldLabel>
          <Input
            id="register-password-confirmation"
            v-model="draft.password_confirmation"
            type="password"
            autocomplete="new-password"
            placeholder="••••••••"
            :disabled="submitting"
            @input="limparErro('password_confirmation')"
          />
          <FieldError :errors="campoError('password_confirmation')" />
        </Field>

        <Button
          type="submit"
          class="w-full"
          :disabled="submitting"
        >
          {{ submitting ? 'Criando conta…' : 'Criar conta' }}
        </Button>
      </form>
    </CardContent>
  </Card>
</template>
