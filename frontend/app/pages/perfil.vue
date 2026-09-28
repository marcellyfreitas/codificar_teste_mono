<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'dashboard',
  middleware: 'auth',
})

useHead({ title: 'Perfil · Painel de Chamados' })

const { user } = useSession()

const roleLabel: Record<string, string> = {
  user: 'Usuário',
  gestor: 'Gestor',
  admin: 'Administrador',
}

const initials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(p => p[0]?.toUpperCase() ?? '')
    .join('')
})
</script>

<template>
  <div class="max-w-lg space-y-6">
    <h1 class="text-xl font-semibold">
      Perfil
    </h1>

    <Card>
      <CardContent class="pt-6">
        <div class="flex items-center gap-4 mb-6">
          <Avatar class="size-16">
            <AvatarFallback class="bg-primary text-primary-foreground text-xl font-bold">
              {{ initials }}
            </AvatarFallback>
          </Avatar>
          <div>
            <p class="text-lg font-semibold">
              {{ user?.name }}
            </p>
            <Badge variant="secondary" class="mt-1">
              {{ roleLabel[user?.role ?? ''] ?? user?.role }}
            </Badge>
          </div>
        </div>

        <div class="space-y-4">
          <div class="flex items-center gap-3">
            <Icon
              icon="lucide:mail"
              class="size-4 text-muted-foreground shrink-0"
            />
            <div>
              <p class="text-xs text-muted-foreground">
                E-mail
              </p>
              <p class="text-sm font-medium">
                {{ user?.email }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <Icon
              icon="lucide:shield"
              class="size-4 text-muted-foreground shrink-0"
            />
            <div>
              <p class="text-xs text-muted-foreground">
                Papel
              </p>
              <p class="text-sm font-medium">
                {{ roleLabel[user?.role ?? ''] ?? user?.role }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <Icon
              icon="lucide:calendar"
              class="size-4 text-muted-foreground shrink-0"
            />
            <div>
              <p class="text-xs text-muted-foreground">
                Membro desde
              </p>
              <p class="text-sm font-medium">
                {{ user?.created_at
                  ? new Date(user.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' })
                  : '—'
                }}
              </p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
