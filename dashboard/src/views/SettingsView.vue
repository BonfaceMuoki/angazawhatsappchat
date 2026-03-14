<template>
  <div>
    <h1 class="text-2xl font-bold text-angaza-dark">Settings</h1>
    <p class="mt-1 text-slate-500">Configure your dashboard and integrations.</p>

    <div class="mt-8 space-y-6">
      <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-angaza-dark">API</h2>
        <p class="mt-1 text-sm text-slate-500">Point this dashboard to your Laravel API (e.g. http://localhost:8000).</p>
        <div class="mt-4">
          <label class="block text-sm font-medium text-slate-700">API base URL</label>
          <div class="mt-1 flex gap-2">
            <input
              v-model="apiBaseUrl"
              type="url"
              placeholder="http://localhost:8000"
              class="w-full max-w-md rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-angaza-accent focus:ring-1 focus:ring-angaza-accent"
            />
            <button
              type="button"
              class="rounded-lg bg-angaza-accent px-4 py-2 text-sm font-medium text-white hover:bg-angaza-accent/90"
              @click="save"
            >
              Save
            </button>
          </div>
          <p class="mt-1 text-xs text-slate-500">Used by Chats to load conversations and messages.</p>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-angaza-dark">About</h2>
        <p class="mt-1 text-sm text-slate-500">Angaza Dashboard — Vue 3 + Vite. Run with <code class="rounded bg-angaza-accent/10 px-1 text-angaza-dark">npm run dev</code>.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { getApiBaseUrl, setApiBaseUrl } from '../api'

const apiBaseUrl = ref('')

onMounted(() => {
  apiBaseUrl.value = getApiBaseUrl()
})

function save() {
  setApiBaseUrl(apiBaseUrl.value.trim())
  apiBaseUrl.value = getApiBaseUrl()
}
</script>
