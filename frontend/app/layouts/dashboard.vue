<script setup lang="ts">
import { useStorage, usePreferredDark } from '@vueuse/core'
import { Icon } from '@iconify/vue'
import { isAdmin as checkAdmin } from '~/modules/tickets/utils/permissions'

const { user, logout } = useSession()

const sidebarOpen = useStorage<boolean>('chamados:sidebar', true)

const themePreference = useStorage<'light' | 'dark' | 'auto'>('chamados:theme', 'auto')
const prefersDark = usePreferredDark()

const isDark = computed(() => {
  if (themePreference.value === 'dark') return true
  if (themePreference.value === 'light') return false
  return prefersDark.value
})

watchEffect(() => {
  document.documentElement.classList.toggle('dark', isDark.value)
})

const themeOptions = [
  { value: 'light' as const, label: 'Claro', icon: 'lucide:sun' },
  { value: 'dark' as const, label: 'Escuro', icon: 'lucide:moon' },
  { value: 'auto' as const, label: 'Sistema', icon: 'lucide:monitor' },
]

const currentThemeIcon = computed(() =>
  themeOptions.find(o => o.value === themePreference.value)?.icon ?? 'lucide:monitor',
)

function setTheme(value: 'light' | 'dark' | 'auto') {
  themePreference.value = value
}

const userIsAdmin = computed(() => user.value ? checkAdmin(user.value) : false)

const userInitials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(p => p[0]?.toUpperCase() ?? '')
    .join('')
})

const navItems = computed(() => [
  { label: 'Chamados', to: '/chamados', icon: 'lucide:ticket', show: true },
  { label: 'Fila', to: '/fila', icon: 'lucide:list-ordered', show: userIsAdmin.value },
])
</script>

<template>
  <SidebarProvider v-model:open="sidebarOpen">
    <Sidebar collapsible="icon">
      <SidebarHeader>
        <div class="flex items-center gap-2 px-2 py-1">
          <Icon
            icon="lucide:ticket-check"
            class="size-5 shrink-0"
          />
          <span class="truncate font-semibold text-sm sidebar-text">
            Painel de Chamados
          </span>
        </div>
      </SidebarHeader>

      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem
                v-for="item in navItems.filter(i => i.show)"
                :key="item.to"
              >
                <SidebarMenuButton
                  as-child
                  :tooltip="item.label"
                >
                  <NuxtLink
                    :to="item.to"
                    active-class="bg-sidebar-accent text-sidebar-accent-foreground"
                    class="flex items-center gap-2"
                  >
                    <Icon
                      :icon="item.icon"
                      class="size-4 shrink-0"
                    />
                    <span>{{ item.label }}</span>
                  </NuxtLink>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      <SidebarFooter />
    </Sidebar>

    <SidebarInset>
      <header class="flex h-14 items-center gap-2 border-b px-4">
        <SidebarTrigger class="-ml-1" />
        <Separator
          orientation="vertical"
          class="h-4"
        />

        <div class="ml-auto flex items-center gap-2">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button
                variant="ghost"
                size="icon"
                class="size-8"
                aria-label="Alternar tema"
              >
                <Icon
                  :icon="currentThemeIcon"
                  class="size-4"
                />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Tema</DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                v-for="opt in themeOptions"
                :key="opt.value"
                class="gap-2"
                @click="setTheme(opt.value)"
              >
                <Icon
                  :icon="opt.icon"
                  class="size-4"
                />
                {{ opt.label }}
                <Icon
                  v-if="themePreference === opt.value"
                  icon="lucide:check"
                  class="ml-auto size-4"
                />
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>

          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button
                variant="ghost"
                class="relative size-9 rounded-full p-0 focus-visible:ring-0"
                aria-label="Menu do usuário"
              >
                <Avatar class="size-9">
                  <AvatarFallback class="bg-primary text-primary-foreground text-xs font-semibold">
                    {{ userInitials }}
                  </AvatarFallback>
                </Avatar>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
              align="end"
              class="w-56"
            >
              <div class="px-2 py-1.5">
                <p class="text-sm font-medium leading-none">
                  {{ user?.name }}
                </p>
                <p class="text-xs text-muted-foreground mt-0.5 truncate">
                  {{ user?.email }}
                </p>
              </div>
              <DropdownMenuSeparator />

              <DropdownMenuItem
                class="gap-2 cursor-pointer"
                @click="navigateTo('/perfil')"
              >
                <Icon
                  icon="lucide:user"
                  class="size-4"
                />
                Visualizar perfil
              </DropdownMenuItem>

              <DropdownMenuSeparator />

              <DropdownMenuItem
                class="gap-2 cursor-pointer text-destructive focus:text-destructive"
                @click="logout"
              >
                <Icon
                  icon="lucide:log-out"
                  class="size-4"
                />
                Sair
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </header>

      <main class="flex flex-1 flex-col gap-4 p-4">
        <slot />
      </main>
    </SidebarInset>
  </SidebarProvider>
</template>
