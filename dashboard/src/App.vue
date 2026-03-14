<template>
  <div class="min-h-screen bg-slate-50">
    <div
      v-if="showSidebar"
      class="fixed inset-0 z-30 bg-angaza-dark/50 lg:hidden"
      aria-hidden="true"
      @click="showSidebar = false"
    />
    <AppSidebar :visible="showSidebar" />
    <div :class="['transition-all', showSidebar ? 'lg:ml-64' : 'ml-0']">
      <AppHeader @toggle-sidebar="showSidebar = !showSidebar" />
      <main class="p-4 lg:p-6">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import AppSidebar from './components/AppSidebar.vue'
import AppHeader from './components/AppHeader.vue'

const showSidebar = ref(true)
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
