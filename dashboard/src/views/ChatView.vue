<template>
  <div class="relative flex h-[calc(100vh-8rem)] flex-col">
    <div class="flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3">
      <router-link
        to="/chats"
        class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-angaza-dark"
        aria-label="Back to chats"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </router-link>
      <div class="min-w-0 flex-1">
        <h1 class="truncate font-semibold text-angaza-dark">{{ phone }}</h1>
        <p class="text-xs text-slate-500">Stage: {{ conversation?.stage ?? '—' }}</p>
      </div>
      <button
        v-if="push.supported && push.permission !== 'granted'"
        type="button"
        class="rounded-lg border border-angaza-accent bg-white px-2.5 py-1 text-xs font-medium text-angaza-accent hover:bg-angaza-accent/10"
        :disabled="push.registering"
        @click="enablePush"
      >
        {{ push.registering ? '…' : 'Enable push' }}
      </button>
      <span v-else-if="push.supported && push.permission === 'granted'" class="text-xs text-slate-400">Push on</span>
    </div>

    <div
      ref="messagesEl"
      class="flex-1 overflow-y-auto border-b border-slate-200 bg-slate-50 p-4"
    >
      <div v-if="loading" class="text-center text-slate-500">Loading messages…</div>
      <div v-else-if="error" class="text-center text-red-600">{{ error }}</div>
      <div v-else class="space-y-3">
        <div
          v-for="msg in messages"
          :key="msg.id"
          :class="[
            'flex',
            msg.direction === 'outgoing' ? 'justify-end' : 'justify-start',
          ]"
        >
          <div
            :class="[
              'max-w-[85%] rounded-2xl px-4 py-2 text-sm',
              msg.direction === 'outgoing'
                ? 'bg-angaza-accent text-white'
                : 'bg-white text-angaza-dark shadow-sm border border-slate-200',
            ]"
          >
            <p class="whitespace-pre-wrap break-words">{{ msg.body }}</p>
            <p
              :class="[
                'mt-1 text-xs',
                msg.direction === 'outgoing' ? 'text-white/80' : 'text-slate-400',
              ]"
            >
              {{ formatTime(msg.created_at) }}
            </p>
          </div>
        </div>
        <p v-if="messages.length === 0" class="text-center text-slate-500">No messages yet.</p>
      </div>
    </div>

    <!-- In-chat toast when new message arrives while viewing -->
    <Transition name="toast">
      <div
        v-if="newMessageToast"
        class="absolute left-1/2 top-24 z-10 -translate-x-1/2 rounded-lg bg-angaza-dark px-4 py-2 text-sm text-white shadow-lg"
      >
        New message received
      </div>
    </Transition>

    <form
      class="flex gap-2 border-t border-slate-200 bg-white p-4"
      @submit.prevent="send"
    >
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { conversationMessages, sendMessage as apiSendMessage, conversationsList, markConversationRead } from '../api'
import { usePushNotifications } from '../composables/usePushNotifications'

const route = useRoute()
const phone = computed(() => route.params.phone || '')

const push = usePushNotifications()
const messages = ref([])
const conversation = ref(null)
const loading = ref(false)
const error = ref('')
const sending = ref(false)
const inputBody = ref('')
const messagesEl = ref(null)
const newMessageToast = ref(false)
const pollInterval = ref(null)
const lastMessageCount = ref(0)

async function enablePush() {
  push.error = ''
  await push.registerAndSubscribe()
}

async function fetchMessages(silent = false) {
  if (!phone.value) return
  if (!silent) loading.value = true
  if (!silent) error.value = ''
  try {
    const res = await conversationMessages(phone.value)
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
    await markConversationRead(phone.value)
  } catch (e) {
    if (!silent) error.value = e.message || 'Failed to load messages'
    messages.value = []
  } finally {
    loading.value = false
  }
}

async function fetchConversation() {
  if (!phone.value) return
  try {
    const res = await conversationsList()
    const list = res.data || []
    conversation.value = list.find((c) => c.phone === phone.value) || null
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
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

async function send() {
  const body = inputBody.value.trim()
  if (!body || !phone.value || sending.value) return
  sending.value = true
  try {
    const res = await apiSendMessage(phone.value, body)
    if (res.data) {
      messages.value = [...messages.value, res.data]
      inputBody.value = ''
      scrollToBottom()
    }
  } catch (e) {
    error.value = e.message || 'Failed to send'
  } finally {
    sending.value = false
  }
}

function startPolling() {
  if (pollInterval.value) return
  pollInterval.value = setInterval(() => {
    if (document.visibilityState === 'visible' && phone.value) fetchMessages(true)
  }, 8000)
}

function stopPolling() {
  if (pollInterval.value) {
    clearInterval(pollInterval.value)
    pollInterval.value = null
  }
}

onMounted(() => {
  fetchMessages()
  fetchConversation()
  startPolling()
})

onUnmounted(stopPolling)

watch(phone, () => {
  lastMessageCount.value = 0
  fetchMessages()
  fetchConversation()
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
  transform: translate(-50%, -8px);
}
</style>
