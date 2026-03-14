<template>
  <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
    <button
      type="button"
      class="rounded-lg p-2 text-angaza-dark hover:bg-angaza-dark/5 hover:text-angaza-dark lg:hidden"
      aria-label="Toggle sidebar"
      @click="$emit('toggle-sidebar')"
    >
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>
    <div class="flex flex-1 items-center justify-end gap-2">
      <span class="text-sm text-slate-600">{{ currentDate }}</span>
      <div class="h-8 w-8 rounded-full bg-angaza-accent/15 flex items-center justify-center text-angaza-dark font-medium text-sm">
        U
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'

defineEmits(['toggle-sidebar'])

const currentDate = computed(() =>
  new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
)
</script>
