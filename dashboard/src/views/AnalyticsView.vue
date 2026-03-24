<template>
  <div>
    <h1 class="text-2xl font-bold text-angaza-dark">Lead analysis</h1>
    <p class="mt-1 text-slate-500">Funnel and recent user responses.</p>

    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <div v-if="loading" class="mt-6 text-slate-500">Loading…</div>

    <template v-else>
      <div class="mt-8">
        <h2 class="text-lg font-semibold text-angaza-dark">Funnel</h2>
        <p class="mt-1 text-sm text-slate-500">Conversations per stage.</p>
        <div class="mt-4 flex flex-wrap gap-2">
          <div
            v-for="(count, stage) in funnel"
            :key="stage"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm"
          >
            <span class="text-sm font-medium capitalize text-slate-600">{{ stage }}</span>
            <span class="rounded-full bg-angaza-dark px-2.5 py-0.5 text-sm font-semibold text-white">{{ count }}</span>
          </div>
        </div>
      </div>

      <div class="mt-10">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-angaza-dark">Recent user responses</h2>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="leadsOnly" type="checkbox" class="rounded border-slate-300" @change="load" />
            Leads only
          </label>
        </div>
        <p class="mt-1 text-sm text-slate-500">Incoming messages with current conversation stage.</p>
        <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-angaza-dark">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Phone</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Stage</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Response</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">When</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 bg-white">
              <tr v-if="recentResponses.length === 0">
                <td colspan="4" class="px-4 py-8 text-center text-slate-500">No responses yet.</td>
              </tr>
              <tr
                v-for="(r, i) in recentResponses"
                :key="i"
                class="hover:bg-slate-50 transition-colors"
              >
                <td class="whitespace-nowrap px-4 py-3 text-sm">
                  <router-link :to="`/chats/${r.phone}`" class="font-medium text-angaza-dark hover:text-angaza-accent">
                    {{ r.phone }}
                  </router-link>
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                  <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                    {{ r.stage_label || r.stage }}
                  </span>
                </td>
                <td class="max-w-md truncate px-4 py-3 text-sm text-slate-600" :title="r.response_text || r.body">
                  {{ r.response_text || r.body || '—' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">{{ formatDate(r.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { analyticsOverview } from '../api'

const funnel = ref({})
const recentResponses = ref([])
const loading = ref(true)
const error = ref('')
const leadsOnly = ref(false)

function formatDate(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleString()
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const params = {}
    if (leadsOnly.value) params.leads_only = '1'
    const res = await analyticsOverview(params)
    const d = res.data ?? res ?? {}
    funnel.value = d.funnel ?? {}
    recentResponses.value = d.recent_responses ?? []
  } catch (e) {
    error.value = e.message || 'Failed to load analytics'
    funnel.value = {}
    recentResponses.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => load())
</script>
