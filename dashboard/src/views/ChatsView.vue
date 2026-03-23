<template>
  <div class="flex min-h-0 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:border-0 sm:shadow-none h-[calc(100vh-7rem)] sm:h-[calc(100vh-8rem)]">
    <!-- Left: chat list (hidden on mobile when a chat is open) -->
    <aside
      :class="[
        selectedPhone ? 'hidden md:flex' : 'flex',
        'w-full flex-col border-r border-slate-200 bg-white md:w-80 md:shrink-0',
      ]"
    >
      <div class="border-b border-slate-100 p-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <h1 class="text-lg font-bold text-angaza-dark">Chats</h1>
          <template v-if="push.supported">
            <button
              v-if="push.permission !== 'granted'"
              type="button"
              class="rounded-lg border border-angaza-accent bg-white px-2.5 py-1.5 text-xs font-medium text-angaza-accent hover:bg-angaza-accent/10"
              :disabled="push.registering"
              @click="enablePush"
            >
              {{ push.registering ? 'Enabling…' : 'Enable push' }}
            </button>
            <span v-else class="text-xs text-slate-400">Push on</span>
          </template>
        </div>
        <p v-if="push.supported && push.permission !== 'granted'" class="mt-1 text-xs text-slate-500">
          Get notifications when a new message arrives.
        </p>
        <input
          v-model="search"
          type="search"
          placeholder="Search by phone..."
          class="mt-3 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-angaza-accent focus:ring-1 focus:ring-angaza-accent"
          @input="fetchConversations"
        />
      </div>
      <div class="flex-1 overflow-y-auto">
        <div v-if="loading" class="p-4 text-center text-sm text-slate-500">Loading…</div>
        <div v-else-if="listError" class="p-4 text-center text-sm text-red-600">{{ listError }}</div>
        <div v-else class="divide-y divide-slate-100">
          <router-link
            v-for="conv in conversations"
            :key="conv.phone"
            :to="'/chats/' + encodeURIComponent(conv.phone)"
            :class="[
              'flex cursor-pointer items-center gap-3 px-3 py-3 transition-colors',
              selectedPhone === conv.phone
                ? 'border-l-4 border-angaza-accent bg-angaza-accent/15'
                : 'hover:bg-slate-50',
              (conv.unread_count || 0) > 0 ? 'bg-angaza-accent/5' : '',
            ]"
          >
            <div class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-angaza-accent/20 text-angaza-dark font-medium">
              {{ conv.phone.slice(-2) }}
              <span
                v-if="(conv.unread_count || 0) > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-angaza-dark px-1 text-[10px] font-bold text-white"
              >
                {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
              </span>
            </div>
            <div class="min-w-0 flex-1">
              <p :class="['truncate font-medium', selectedPhone === conv.phone ? 'text-angaza-dark' : 'text-slate-800', (conv.unread_count || 0) > 0 ? 'font-semibold' : '']">
                {{ conv.phone }}
              </p>
              <p class="truncate text-xs text-slate-500">
                {{ conv.last_message_body || 'No messages yet' }}
              </p>
            </div>
            <div class="shrink-0 text-right">
              <p class="text-xs text-slate-400">{{ formatTime(conv.last_message_at) }}</p>
              <p class="text-xs text-slate-400">{{ conv.message_count }} msgs</p>
            </div>
          </router-link>
          <p v-if="conversations.length === 0" class="p-4 text-center text-sm text-slate-500">
            No conversations yet.
          </p>
        </div>
      </div>
    </aside>

    <!-- Right: active chat or placeholder (hidden on mobile when no chat selected) -->
    <main
      :class="[
        !selectedPhone ? 'hidden md:flex' : 'flex',
        'relative min-w-0 flex-1 flex-col bg-slate-50',
      ]"
    >
      <template v-if="!selectedPhone">
        <div class="flex flex-1 flex-col items-center justify-center p-8 text-center">
          <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-200 text-slate-400">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
          <p class="mt-4 font-medium text-slate-600">Select a conversation</p>
          <p class="mt-1 text-sm text-slate-500">Choose a chat from the list to view messages.</p>
        </div>
      </template>

      <template v-else>
        <!-- Chat header -->
        <div class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3">
          <button
            type="button"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-angaza-dark md:hidden touch-manipulation"
            aria-label="Back to list"
            @click="goToChats"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div class="min-w-0 flex-1">
            <h2 class="truncate font-semibold text-angaza-dark">{{ selectedPhone }}</h2>
            <p class="text-xs text-slate-500">Stage: {{ conversation?.stage ?? '—' }}</p>
          </div>
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50"
            :disabled="clearing"
            @click="clearMessages"
          >
            {{ clearing ? 'Clearing…' : 'Clear messages' }}
          </button>
        </div>

        <!-- Messages -->
        <div
          ref="messagesEl"
          class="flex-1 overflow-y-auto overflow-x-hidden p-3 sm:p-4"
        >
          <div v-if="msgLoading" class="text-center text-slate-500">Loading messages…</div>
          <div v-else-if="msgError" class="text-center text-red-600">{{ msgError }}</div>
          <div v-else class="space-y-3">
            <div
              v-for="msg in messages"
              :key="msg.id"
              :class="['flex', msg.direction === 'outgoing' ? 'justify-end' : 'justify-start']"
            >
              <div
                :class="[
                  'max-w-[85%] rounded-2xl px-4 py-2 text-sm',
                  msg.direction === 'outgoing'
                    ? 'bg-angaza-accent text-white'
                    : 'bg-white text-angaza-dark shadow-sm border border-slate-200',
                ]"
              >
                <p class="whitespace-pre-wrap break-words">{{ msg.display_body ?? msg.body }}</p>
                <p
                  :class="['mt-1 text-xs', msg.direction === 'outgoing' ? 'text-white/80' : 'text-slate-400']"
                >
                  {{ formatTime(msg.created_at) }}
                </p>
              </div>
            </div>
            <p v-if="messages.length === 0" class="text-center text-slate-500">No messages yet.</p>
          </div>
        </div>

        <!-- New message toast -->
        <Transition name="toast">
          <div
            v-if="newMessageToast"
            class="absolute left-1/2 top-20 z-10 -translate-x-1/2 rounded-lg bg-angaza-dark px-4 py-2 text-sm text-white shadow-lg"
          >
            New message received
          </div>
        </Transition>

        <!-- Send form -->
        <form class="flex gap-2 border-t border-slate-200 bg-white p-4" @submit.prevent="send">
          <input
            v-model="inputBody"
            type="text"
            placeholder="Type a message..."
            class="min-w-0 flex-1 rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:border-angaza-accent focus:ring-1 focus:ring-angaza-accent"
            :disabled="sending"
          />
          <button
            type="submit"
            class="rounded-xl bg-angaza-accent px-4 py-2.5 font-medium text-white hover:bg-angaza-accent/90 disabled:opacity-50"
            :disabled="sending || !inputBody.trim()"
          >
            Send
          </button>
        </form>
      </template>
    </main>

    <!-- List toast when new message in another chat -->
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
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  getApiBaseUrl,
  conversationsList,
  conversationMessages,
  clearConversationMessages,
  sendMessage as apiSendMessage,
  markConversationRead,
} from '../api'
import { usePushNotifications } from '../composables/usePushNotifications'

const route = useRoute()
const router = useRouter()
const selectedPhone = computed(() => route.params.phone || '')

const push = usePushNotifications()

// List
const apiBaseUrl = computed(() => getApiBaseUrl())
const search = ref('')
const conversations = ref([])
const loading = ref(false)
const listError = ref('')
const toastMessage = ref('')
const pollInterval = ref(null)
const previousUnread = ref({})

// Active chat
const messages = ref([])
const conversation = ref(null)
const msgLoading = ref(false)
const msgError = ref('')
const sending = ref(false)
const clearing = ref(false)
const inputBody = ref('')
const messagesEl = ref(null)
const newMessageToast = ref(false)
const lastMessageCount = ref(0)

async function enablePush() {
  push.error = ''
  await push.registerAndSubscribe()
}

async function fetchConversations() {
  loading.value = true
  listError.value = ''
  try {
    const res = await conversationsList({ search: search.value })
    const list = res.data || []
    const current = selectedPhone.value
    list.forEach((c) => {
      const prev = previousUnread.value[c.phone] ?? 0
      const now = c.unread_count ?? 0
      if (now > prev && c.phone !== current && now > 0) {
        toastMessage.value = `New message from ${c.phone}`
        setTimeout(() => { toastMessage.value = '' }, 4000)
      }
      previousUnread.value[c.phone] = now
    })
    conversations.value = list
  } catch (e) {
    listError.value = e.message || 'Failed to load conversations'
    conversations.value = []
  } finally {
    loading.value = false
  }
}

async function fetchMessages(silent = false) {
  if (!selectedPhone.value) return
  if (!silent) msgLoading.value = true
  if (!silent) msgError.value = ''
  try {
    const res = await conversationMessages(selectedPhone.value)
    const list = res.data || []
    const prevCount = lastMessageCount.value
    lastMessageCount.value = list.length
    if (silent && list.length > prevCount && prevCount > 0) {
      newMessageToast.value = true
      setTimeout(() => { newMessageToast.value = false }, 2500)
      scrollToBottom()
    }
    messages.value = list
    scrollToBottom()
    await markConversationRead(selectedPhone.value)
  } catch (e) {
    if (!silent) msgError.value = e.message || 'Failed to load messages'
    messages.value = []
  } finally {
    msgLoading.value = false
  }
}

async function fetchConversation() {
  if (!selectedPhone.value) return
  try {
    const res = await conversationsList()
    const list = res.data || []
    conversation.value = list.find((c) => c.phone === selectedPhone.value) || null
  } catch {
    conversation.value = null
  }
}

function scrollToBottom() {
  if (messagesEl.value) {
    messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  }
}

function formatTime(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  const now = new Date()
  const sameDay = d.toDateString() === now.toDateString()
  return sameDay ? d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : d.toLocaleDateString()
}

function goToChats() {
  router.push('/chats')
}

async function clearMessages() {
  if (!selectedPhone.value || clearing.value) return
  if (!confirm('Clear all messages for this conversation and reset the bot to the start? This cannot be undone.')) return
  clearing.value = true
  try {
    await clearConversationMessages(selectedPhone.value, true)
    messages.value = []
    await fetchConversations()
    await fetchConversation()
  } catch (e) {
    msgError.value = e.message || 'Failed to clear messages'
  } finally {
    clearing.value = false
  }
}

async function send() {
  const body = inputBody.value.trim()
  if (!body || !selectedPhone.value || sending.value) return
  sending.value = true
  try {
    const res = await apiSendMessage(selectedPhone.value, body)
    if (res.data) {
      messages.value = [...messages.value, res.data]
      inputBody.value = ''
      scrollToBottom()
    }
  } catch (e) {
    msgError.value = e.message || 'Failed to send'
  } finally {
    sending.value = false
  }
}

function startPolling() {
  if (pollInterval.value) return
  pollInterval.value = setInterval(() => {
    if (document.visibilityState === 'visible') {
      fetchConversations()
      if (selectedPhone.value) fetchMessages(true)
    }
  }, 8000)
}

function stopPolling() {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

onMounted(() => {
  fetchConversations()
  if (selectedPhone.value) {
    fetchMessages()
    fetchConversation()
  }
  startPolling()
})

onUnmounted(stopPolling)

watch(selectedPhone, (phone) => {
  if (phone) {
    lastMessageCount.value = 0
    fetchMessages()
    fetchConversation()
  } else {
    messages.value = []
    conversation.value = null
  }
})
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
