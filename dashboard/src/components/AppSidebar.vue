<template>
  <aside
    class="fixed left-0 top-0 z-40 h-screen w-64 border-r border-angaza-dark/20 bg-angaza-dark shadow-lg transition-transform duration-200 ease-out"
    :class="visible ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-16 items-center gap-2 border-b border-white/10 px-4">
      <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-angaza-accent text-white font-semibold">
        A
      </div>
      <span class="text-lg font-semibold text-white">Angaza</span>
    </div>
    <nav class="space-y-0.5 p-3">
      <router-link
        v-for="item in navItems"
        :key="item.path"
        :to="item.path"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
        :class="isActive(item.path)
          ? 'bg-angaza-accent/20 text-white'
          : 'text-white/85 hover:bg-white/10 hover:text-white'"
      >
        <span class="text-lg opacity-90">{{ item.icon }}</span>
        {{ item.label }}
      </router-link>
      <template v-if="isLoggedIn">
        <div class="my-2 border-t border-white/10 pt-2">
          <p class="px-3 text-xs font-medium uppercase tracking-wider text-white/50">Admin</p>
        </div>
        <router-link
          v-for="item in adminNavItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
          :class="isActive(item.path)
            ? 'bg-angaza-accent/20 text-white'
            : 'text-white/85 hover:bg-white/10 hover:text-white'"
        >
          <span class="text-lg opacity-90">{{ item.icon }}</span>
          {{ item.label }}
        </router-link>
        <button
          type="button"
          class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium text-white/85 hover:bg-white/10 hover:text-white"
          @click="logout"
        >
          <span class="text-lg opacity-90">🚪</span>
          Log out
        </button>
      </template>
      <router-link
        v-else
        to="/login"
        class="mt-2 flex items-center gap-3 rounded-lg border border-white/20 px-3 py-2.5 text-sm font-medium text-white hover:bg-white/10"
      >
        <span class="text-lg opacity-90">🔐</span>
        Log in (Admin)
      </router-link>
    </nav>
    <div class="absolute bottom-0 left-0 right-0 border-t border-white/10 p-3">
      <p class="text-xs text-white/60">Dashboard v0.1</p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'

defineProps({
  visible: { type: Boolean, default: true },
})

const route = useRoute()
const { isLoggedIn, logout, hasBotPermission } = useAuth()

const navItems = [
  { path: '/', label: 'Dashboard', icon: '📊' },
  { path: '/chats', label: 'Chats', icon: '💬' },
  { path: '/leads', label: 'Leads', icon: '🎯' },
  { path: '/analytics', label: 'Analysis', icon: '📈' },
  { path: '/settings', label: 'Settings', icon: '⚙️' },
]

const adminNavItemsBase = [
  { path: '/admin/users', label: 'Users', icon: '👥' },
  { path: '/admin/roles', label: 'Roles', icon: '🔐' },
  { path: '/admin/permissions', label: 'Permissions', icon: '✓' },
  { path: '/admin/chatbot/flows', label: 'Chatbot', icon: '🤖', requiresBot: true },
]

const adminNavItems = computed(() => {
  return adminNavItemsBase.filter((item) => {
    if (item.requiresBot) return hasBotPermission.value
    return true
  })
})

const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>
