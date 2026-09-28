<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

useHead({ title: 'Chamados · Painel de Chamados' })
</script>

<template>
  <div class="space-y-4">
    <h1 class="text-2xl font-semibold">
      Chamados
    </h1>

    <p class="text-muted-foreground">
      A listagem entra na spec 05. Esta página existe para o guard ter o que
      proteger.
    </p>
  </div>
</template>
