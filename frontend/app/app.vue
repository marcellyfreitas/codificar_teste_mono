<script setup lang="ts">
</script>

<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>

  <Toaster
    position="top-right"
    rich-colors
    close-button
    :toast-options="{
      classes: {
        toast: 'rounded-2xl',
        icon: 'hidden',
      },
    }"
  />
</template>
