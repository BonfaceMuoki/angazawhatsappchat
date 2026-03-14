<template>
  <div>
    <h1 class="text-2xl font-bold text-angaza-dark">Dashboard</h1>
    <p class="mt-1 text-slate-500">Overview of your WhatsApp chat activity.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <p class="text-sm font-medium text-slate-500">{{ stat.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-angaza-dark">{{ stat.value }}</p>
      </div>
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-angaza-dark">Recent activity</h2>
      <p class="mt-2 text-sm text-slate-500">Connect your backend to show recent chats and messages here.</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const stats = ref([
  { label: 'Total chats', value: '—' },
  { label: 'Messages today', value: '—' },
  { label: 'Active sessions', value: '—' },
  { label: 'Unread', value: '—' },
])
</script>
