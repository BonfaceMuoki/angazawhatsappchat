<template>
  <div>
    <h1 class="text-2xl font-bold text-angaza-dark">Dashboard</h1>
    <p class="mt-1 text-slate-500">Overview of your WhatsApp chat activity.</p>

    <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
    <div v-if="loading" class="mt-8 flex items-center gap-3 text-slate-500">
      <svg class="h-5 w-5 animate-spin text-angaza-accent" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      Loading…
    </div>

    <template v-else>
      <!-- Stat cards with icons -->
      <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="stat in stats"
          :key="stat.label"
          class="group flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
        >
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl transition-colors"
            :class="stat.iconBg"
          >
            <component :is="stat.icon" class="h-6 w-6" :class="stat.iconColor" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-500">{{ stat.label }}</p>
            <p class="mt-0.5 text-2xl font-bold tracking-tight text-angaza-dark">{{ stat.value }}</p>
          </div>
        </div>
      </div>

      <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <!-- Recent leads -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 text-angaza-dark">
              <IconTarget class="h-5 w-5" />
            </div>
            <h2 class="text-lg font-semibold text-angaza-dark">Recent leads</h2>
          </div>
          <div class="p-6">
            <div
              v-if="recentLeads.length === 0"
              class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-12 text-center"
            >
              <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200/80 text-slate-400">
                <IconTarget class="h-7 w-7" />
              </div>
              <p class="mt-3 text-sm font-medium text-slate-600">No potential leads yet</p>
              <p class="mt-1 text-xs text-slate-500">Leads appear when conversations reach pricing or beyond.</p>
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="lead in recentLeads"
                :key="lead.phone"
                class="flex items-center gap-3 rounded-lg border border-slate-100 py-3 px-4 transition-colors hover:border-angaza-accent/30 hover:bg-teal-50/30"
              >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                  <IconUser class="h-4 w-4" />
                </div>
                <div class="min-w-0 flex-1">
                  <router-link
                    :to="`/chats/${lead.phone}`"
                    class="font-medium text-angaza-dark hover:text-angaza-accent hover:underline"
                  >
                    {{ lead.phone }}
                  </router-link>
                  <span class="ml-2 shrink-0 text-xs text-slate-400">{{ formatDate(lead.last_message_at) }}</span>
                </div>
                <span
                  class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                  :class="lead.lead_tier === 'hot' ? 'bg-teal-100 text-angaza-dark' : 'bg-sky-100 text-sky-700'"
                >
                  {{ lead.lead_tier }}
                </span>
              </li>
            </ul>
            <router-link
              to="/leads"
              class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-angaza-accent hover:text-angaza-dark hover:underline"
            >
              View all leads
              <IconArrow class="h-4 w-4" />
            </router-link>
          </div>
        </div>

        <!-- Recent activity -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/50 px-6 py-4">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-100 text-angaza-dark">
              <IconChat class="h-5 w-5" />
            </div>
            <h2 class="text-lg font-semibold text-angaza-dark">Recent activity</h2>
          </div>
          <div class="p-6">
            <div
              v-if="recent.length === 0"
              class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-12 text-center"
            >
              <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-200/80 text-slate-400">
                <IconChat class="h-7 w-7" />
              </div>
              <p class="mt-3 text-sm font-medium text-slate-600">No conversations yet</p>
              <p class="mt-1 text-xs text-slate-500">Chats will appear here once users message in.</p>
            </div>
            <ul v-else class="space-y-2">
              <li
                v-for="conv in recent"
                :key="conv.phone"
                class="flex items-center gap-3 rounded-lg border border-slate-100 py-3 px-4 transition-colors hover:border-angaza-accent/30 hover:bg-teal-50/30"
              >
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                  <IconUser class="h-4 w-4" />
                </div>
                <div class="min-w-0 flex-1">
                  <router-link
                    :to="`/chats/${conv.phone}`"
                    class="font-medium text-angaza-dark hover:text-angaza-accent hover:underline"
                  >
                    {{ conv.phone }}
                  </router-link>
                  <p class="truncate text-sm text-slate-500" :title="conv.last_message_body">
                    {{ conv.last_message_body || '—' }}
                  </p>
                </div>
                <span v-if="conv.unread_count > 0" class="shrink-0 rounded-full bg-angaza-accent/20 px-2.5 py-0.5 text-xs font-semibold text-angaza-dark">
                  {{ conv.unread_count }}
                </span>
                <span class="shrink-0 text-xs text-slate-400">{{ formatDate(conv.last_message_at) }}</span>
              </li>
            </ul>
            <router-link
              to="/chats"
              class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-angaza-accent hover:text-angaza-dark hover:underline"
            >
              View all chats
              <IconArrow class="h-4 w-4" />
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { dashboardStats } from '../api'

// Inline icon components (no extra deps)
const IconChat = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
}
const IconMessage = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12l-4-4H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M9 9h6"/><path d="M9 13h4"/></svg>`,
}
const IconTarget = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>`,
}
const IconBell = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13 21a1 1 0 0 1-2 0h2z"/></svg>`,
}
const IconUser = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
}
const IconArrow = {
  template: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>`,
}

const loading = ref(true)
const error = ref('')
const totalChats = ref(0)
const totalUnread = ref(0)
const messagesToday = ref(0)
const totalLeads = ref(0)
const recent = ref([])
const recentLeads = ref([])

const statConfig = [
  { label: 'Total chats', icon: IconChat, iconBg: 'bg-teal-100', iconColor: 'text-angaza-dark' },
  { label: 'Messages today', icon: IconMessage, iconBg: 'bg-sky-100', iconColor: 'text-sky-600' },
  { label: 'Potential leads', icon: IconTarget, iconBg: 'bg-teal-100', iconColor: 'text-angaza-dark' },
  { label: 'Unread', icon: IconBell, iconBg: 'bg-rose-100', iconColor: 'text-rose-600' },
]

const stats = ref(statConfig.map((c) => ({ ...c, value: '—' })))

function formatDate(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  const now = new Date()
  const isToday = d.toDateString() === now.toDateString()
  if (isToday) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  return d.toLocaleDateString()
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await dashboardStats()
    const d = res.data?.data ?? res.data ?? {}
    totalChats.value = d.total_chats ?? 0
    totalUnread.value = d.total_unread ?? 0
    messagesToday.value = d.messages_today ?? 0
    totalLeads.value = d.total_leads ?? 0
    recent.value = d.recent_conversations ?? []
    recentLeads.value = d.recent_leads ?? []
    const values = [
      String(totalChats.value),
      String(messagesToday.value),
      String(totalLeads.value),
      String(totalUnread.value),
    ]
    stats.value = statConfig.map((c, i) => ({ ...c, value: values[i] ?? '—' }))
  } catch (e) {
    error.value = e.message || 'Failed to load dashboard'
    stats.value = statConfig.map((c) => ({ ...c, value: '—' }))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
