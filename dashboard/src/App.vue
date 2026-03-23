<template>
  <div class="min-h-screen min-w-0 overflow-x-hidden bg-slate-50">
    <!-- Login: only router-view (no sidebar/header) -->
    <template v-if="isLoginPage">
      <router-view />
    </template>
    <!-- Rest of app: sidebar + header + router-view -->
    <template v-else>
      <!-- Overlay: only on mobile when sidebar open -->
      <div
        v-if="showSidebar"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        aria-hidden="true"
        @click="showSidebar = false"
      />
      <AppSidebar :visible="showSidebar" />
      <!-- Main: full width on mobile; margin on lg when sidebar visible -->
      <div class="min-w-0 transition-[margin] duration-200" :class="showSidebar ? 'lg:ml-64' : 'ml-0'">
        <AppHeader @toggle-sidebar="showSidebar = !showSidebar" />
        <main class="min-w-0 p-3 sm:p-4 lg:p-6">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
        </main>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppSidebar from './components/AppSidebar.vue'
import AppHeader from './components/AppHeader.vue'

const route = useRoute()
const isLoginPage = computed(() => {
  const path = (route.path || '').replace(/\/$/, '')
  return route.name === 'login' || path === '/login'
})

// Sidebar: hidden on mobile by default, visible on desktop (avoids flash of visible sidebar on mobile)
const showSidebar = ref(false)
function setSidebarFromViewport() {
  showSidebar.value = window.matchMedia('(min-width: 1024px)').matches
}
onMounted(() => {
  setSidebarFromViewport()
  window.addEventListener('resize', setSidebarFromViewport)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', setSidebarFromViewport)
})
watch(
  () => route.path,
  () => {
    if (window.innerWidth < 1024) showSidebar.value = false
  }
)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
