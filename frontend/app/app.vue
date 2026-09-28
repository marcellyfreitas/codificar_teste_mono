<script setup lang="ts">
/**
 * Raiz única da árvore do app. O `Toaster` mora aqui porque toasts de mutação
 * são disparados de `useTicketActions`, longe de qualquer página: um toaster
 * por página perderia o aviso quando o usuário troca de rota.
 */
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>

  <Toaster
    position="top-right"
    rich-colors
    close-button
  />
</template>
