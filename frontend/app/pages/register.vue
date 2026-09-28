<script setup lang="ts">
definePageMeta({
  layout: 'default',
  middleware: 'guest',
})

useHead({ title: 'Criar conta · Painel de Chamados' })
</script>

<template>
  <div class="flex w-full max-w-md flex-col gap-6">
    <RegisterForm />

    <p class="text-center text-sm text-muted-foreground">
      Já tem conta?
      <NuxtLink
        to="/login"
        class="font-medium text-primary underline-offset-4 hover:underline"
      >
        Entrar
      </NuxtLink>
    </p>
  </div>
</template>
