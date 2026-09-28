<script setup lang="ts">
/**
 * Layout das telas de autenticação. É o `default` porque é o único que existe
 * nesta spec; a spec 04 troca o nome para `auth` e dá `dashboard` ao app
 * autenticado, para que os dois não compartilhem estrutura.
 */
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-muted/40 p-6">
    <div class="w-full max-w-md">
      <div class="mb-8 text-center">
        <h1 class="text-2xl font-semibold tracking-tight">
          Painel de Chamados
        </h1>
        <p class="text-muted-foreground">
          Acompanhamento e distribuição de chamados.
        </p>
      </div>

      <slot />
    </div>
  </div>
</template>
