<script setup lang="ts">
definePageMeta({
  layout: 'default',
  middleware: 'guest',
})

useHead({ title: 'Entrar · Painel de Chamados' })
</script>

<template>
  <div class="flex w-full max-w-md flex-col gap-6">
    <LoginForm />

    <p class="text-center text-sm text-muted-foreground">
      Ainda não tem conta?
      <NuxtLink
        to="/register"
        class="font-medium text-primary underline-offset-4 hover:underline"
      >
        Criar conta
      </NuxtLink>
    </p>
  </div>
</template>
