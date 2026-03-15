<template>
  <div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-angaza-dark">Potential leads</h1>
      <div class="flex items-center gap-2">
        <label class="text-sm text-slate-600">Stage</label>
        <select
          v-model="stageFilter"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
          @change="load"
        >
          <option value="">All leads</option>
          <option value="pricing">Pricing</option>
          <option value="education">Education</option>
          <option value="conversion">Conversion</option>
          <option value="complete">Complete</option>
        </select>
      </div>
    </div>
    <p class="mt-1 text-slate-500">Conversations that reached pricing, education, conversion, or complete.</p>

    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <div v-if="loading" class="mt-6 text-slate-500">Loading…</div>
    <AdminTable
      v-else
      :columns="columns"
      :items="leads"
      :pagination="pagination"
      class="mt-6"
      @page="goToPage"
    >
      <template #body>
        <tr v-for="lead in leads" :key="lead.phone" class="hover:bg-slate-50 transition-colors">
          <td class="whitespace-nowrap px-4 py-3 text-sm">
            <router-link :to="`/chats/${lead.phone}`" class="font-medium text-angaza-dark hover:text-angaza-accent">
              {{ lead.phone }}
            </router-link>
          </td>
          <td class="whitespace-nowrap px-4 py-3">
            <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
              {{ lead.stage }}
            </span>
          </td>
          <td class="whitespace-nowrap px-4 py-3">
            <span
              class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="lead.lead_tier === 'hot' ? 'bg-teal-100 text-angaza-dark' : 'bg-sky-100 text-sky-800'"
            >
              {{ lead.lead_tier }}
            </span>
          </td>
          <td class="max-w-[200px] truncate px-4 py-3 text-sm text-slate-600" :title="lead.last_message_body">
            {{ lead.last_message_body || '—' }}
          </td>
          <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">
            {{ formatDate(lead.last_message_at) }}
          </td>
          <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
            {{ lead.message_count }}
          </td>
          <td class="whitespace-nowrap px-4 py-3">
            <span v-if="lead.unread_count > 0" class="rounded-full bg-angaza-accent/20 px-2 py-0.5 text-xs font-medium text-angaza-dark">
              {{ lead.unread_count }}
            </span>
            <span v-else class="text-slate-400">—</span>
          </td>
        </tr>
      </template>
      <template #empty>No leads yet. Leads are conversations that reached pricing, education, conversion, or complete.</template>
    </AdminTable>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AdminTable from '../components/Admin/AdminTable.vue'
import { leadsList } from '../api'

const columns = [
  { label: 'Phone', class: '' },
  { label: 'Stage', class: '' },
  { label: 'Tier', class: '' },
  { label: 'Last message', class: '' },
  { label: 'Last activity', class: '' },
  { label: 'Messages', class: '' },
  { label: 'Unread', class: '' },
]

const leads = ref([])
const pagination = ref(null)
const loading = ref(true)
const error = ref('')
const stageFilter = ref('')

function formatDate(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleString()
}

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 15 }
    if (stageFilter.value) params.stage = stageFilter.value
    const res = await leadsList(params)
    leads.value = res.data ?? []
    pagination.value = res.pagination ?? null
  } catch (e) {
    error.value = e.message || 'Failed to load leads'
    leads.value = []
    pagination.value = null
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  load(page)
}

onMounted(() => load())
</script>
