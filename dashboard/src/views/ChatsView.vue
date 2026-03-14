<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-angaza-dark">Chats</h1>
        <p class="mt-1 text-slate-500">Conversations and messages.</p>
      </div>
      <template v-if="push.supported">
        <button
          v-if="push.permission !== 'granted'"
          type="button"
          class="rounded-lg border border-angaza-accent bg-white px-3 py-1.5 text-sm font-medium text-angaza-accent hover:bg-angaza-accent/10"
          :disabled="push.registering"
          @click="enablePush"
        >
          {{ push.registering ? 'Enabling…' : 'Enable push notifications' }}
        </button>
        <p v-else class="text-xs text-slate-500">Push notifications enabled</p>
      </template>
      <p v-if="push.error" class="text-sm text-red-600">{{ push.error }}</p>
    </div>
    <p v-if="push.supported && push.permission !== 'granted'" class="mt-2 text-xs text-slate-500">
      Enable push to get browser notifications when a new message arrives (even if the tab is in the background).
    </p>

    <p
      v-if="!apiBaseUrl && !loading && !error"
      class="mt-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
    >
      Using relative <code class="rounded bg-slate-200 px-1">/api</code> (proxied to Laravel in dev). Or set API base URL in <router-link to="/settings" class="font-medium text-angaza-accent underline">Settings</router-link>.
    </p>

    <div class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <input
          v-model="search"
          type="search"
          placeholder="Search by phone..."
          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-angaza-accent focus:ring-1 focus:ring-angaza-accent"
          @input="fetchConversations"
        />
      </div>
      <div v-if="loading" class="px-4 py-8 text-center text-slate-500">Loading…</div>
      <div v-else-if="error" class="px-4 py-8 text-center text-red-600">{{ error }}</div>
      <div v-else class="divide-y divide-slate-100">
        <router-link
          v-for="conv in conversations"
          :key="conv.phone"
          :to="'/chats/' + encodeURIComponent(conv.phone)"
          :class="[
            'flex cursor-pointer items-center gap-4 px-4 py-3 hover:bg-angaza-dark/5',
            (conv.unread_count || 0) > 0 ? 'bg-angaza-accent/5' : '',
          ]"
        >
          <div class="relative flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-angaza-accent/20 text-angaza-dark font-medium">
            {{ conv.phone.slice(-2) }}
            <span
              v-if="(conv.unread_count || 0) > 0"
              class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
            >
              {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
            </span>
          </div>
          <div class="min-w-0 flex-1">
            <p :class="['font-medium text-angaza-dark', (conv.unread_count || 0) > 0 ? 'font-semibold' : '']">
              {{ conv.phone }}
            </p>
            <p class="truncate text-sm text-slate-500">
              {{ conv.last_message_body || 'No messages yet' }}
            </p>
          </div>
          <div class="flex-shrink-0 text-right">
            <p class="text-xs text-slate-400">{{ formatTime(conv.last_message_at) }}</p>
            <p class="text-xs text-slate-400">{{ conv.message_count }} messages</p>
          </div>
        </router-link>
        <p v-if="conversations.length === 0" class="px-4 py-8 text-center text-slate-500">
          No conversations yet.
        </p>
      </div>
    </div>

    <!-- In-app toast when new message detected while polling -->
    <Transition name="toast">
      <div
        v-if="toastMessage"
        class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-angaza-dark px-4 py-2 text-sm text-white shadow-lg"
      >
        {{ toastMessage }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { getApiBaseUrl, conversationsList } from '../api'
import { usePushNotifications } from '../composables/usePushNotifications'

const route = useRoute()
const push = usePushNotifications()

async function enablePush() {
  push.error = ''
  await push.registerAndSubscribe()
}
const apiBaseUrl = computed(() => getApiBaseUrl())
const search = ref('')
const conversations = ref([])
const loading = ref(false)
const error = ref('')
const toastMessage = ref('')
const pollInterval = ref(null)
const previousUnread = ref({}) // phone -> unread_count

async function fetchConversations() {
  loading.value = true
  error.value = ''
  try {
    const res = await conversationsList({ search: search.value })
    const list = res.data || []
    const currentPhone = route.params.phone
    list.forEach((c) => {
      const prev = previousUnread.value[c.phone] ?? 0
      const now = c.unread_count ?? 0
      if (now > prev && c.phone !== currentPhone && now > 0) {
        toastMessage.value = `New message from ${c.phone}`
        setTimeout(() => { toastMessage.value = '' }, 4000)
      }
      previousUnread.value[c.phone] = now
    })
    conversations.value = list
  } catch (e) {
    error.value = e.message || 'Failed to load conversations'
    conversations.value = []
  } finally {
    loading.value = false
  }
}

function formatTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  const now = new Date()
  const sameDay = d.toDateString() === now.toDateString()
  return sameDay ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : d.toLocaleDateString()
}

function startPolling() {
  if (pollInterval.value) return
  pollInterval.value = setInterval(() => {
    if (document.visibilityState === 'visible') fetchConversations()
  }, 30000)
}

function stopPolling() {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

onMounted(() => {
  fetchConversations()
  startPolling()
})

onUnmounted(stopPolling)
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translate(-50%, 8px);
}
</style>
