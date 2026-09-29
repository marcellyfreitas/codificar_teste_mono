<script setup lang="ts">
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
