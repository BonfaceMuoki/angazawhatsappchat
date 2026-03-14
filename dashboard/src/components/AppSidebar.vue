<template>
  <aside
    class="fixed left-0 top-0 z-40 h-screen w-64 border-r border-angaza-dark/20 bg-angaza-dark shadow-lg transition-transform lg:translate-x-0"
    :class="{ '-translate-x-full': !visible }"
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
    </nav>
    <div class="absolute bottom-0 left-0 right-0 border-t border-white/10 p-3">
      <p class="text-xs text-white/60">Dashboard v0.1</p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

defineProps({
  visible: { type: Boolean, default: true },
})

const route = useRoute()

const navItems = [
  { path: '/', label: 'Dashboard', icon: '📊' },
  { path: '/chats', label: 'Chats', icon: '💬' },
  { path: '/settings', label: 'Settings', icon: '⚙️' },
]

const isActive = (path) => {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>
