<script setup lang="ts">
/**
 * Raiz da aplicação: `/` não tem tela própria, é só uma porta de entrada para
 * `/chamados`. O `replace` evita que o usuário dê "voltar" e caia aqui de
 * novo, num segundo redirect para a mesma rota.
 */
const { isAuthenticated } = useSession()

if (isAuthenticated.value) {
  await navigateTo('/chamados', { replace: true })
}
else {
  await navigateTo('/login', { replace: true })
}
</script>

<template>
  <div />
</template>
