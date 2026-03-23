<template>
  <div>
    <h1 class="text-2xl font-bold text-angaza-dark">Settings</h1>
    <p class="mt-1 text-slate-500">Configure your dashboard and integrations.</p>

    <div class="mt-8 space-y-6">
      <section v-if="hasBotPermission" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-angaza-dark">AI (Chatbot)</h2>
        <p class="mt-1 text-sm text-slate-500">Control how the bot uses AI to interpret user messages when they don’t match an option exactly.</p>
        <div class="mt-4 space-y-4">
          <label class="flex cursor-pointer items-center gap-3">
            <input
              v-model="aiEnabled"
              type="checkbox"
              class="h-4 w-4 rounded border-slate-300 text-angaza-accent focus:ring-angaza-accent"
            />
            <span class="text-sm font-medium text-slate-700">Enable AI</span>
          </label>
          <p class="text-xs text-slate-500">When on, the bot can use AI to guess the user’s intent (e.g. map “yeah” to “Yes”) when the reply doesn’t match a button or list option.</p>
          <div v-if="aiEnabled">
            <label class="block text-sm font-medium text-slate-700">AI mode</label>
            <select
              v-model="aiMode"
              class="mt-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-angaza-accent focus:ring-1 focus:ring-angaza-accent"
            >
              <option value="off">Off (no AI)</option>
              <option value="intent_detection">Intent detection</option>
              <option value="response_interpretation">Response interpretation</option>
              <option value="full">Full</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">Intent detection: match free text to the current step’s options. Other modes may add more behavior later.</p>
          </div>
          <p v-if="aiError" class="text-sm text-red-600">{{ aiError }}</p>
          <button
            type="button"
            class="rounded-lg bg-angaza-accent px-4 py-2 text-sm font-medium text-white hover:bg-angaza-accent/90 disabled:opacity-50"
            :disabled="aiSaving"
            @click="saveAi"
          >
            {{ aiSaving ? 'Saving…' : 'Save AI settings' }}
          </button>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { botSettingsGet, botSettingsUpdate } from '../api'
import { useAuth } from '../composables/useAuth'

const { hasBotPermission } = useAuth()

const aiEnabled = ref(false)
const aiMode = ref('intent_detection')
const aiSaving = ref(false)
const aiError = ref('')

onMounted(() => {
  loadAiSettings()
})

async function loadAiSettings() {
  if (!hasBotPermission.value) return
  try {
    const res = await botSettingsGet()
    const data = res.data || {}
    aiEnabled.value = data.ai_enabled === '1' || data.ai_enabled === true
    aiMode.value = data.ai_mode || 'intent_detection'
  } catch {
    aiError.value = ''
  }
}

async function saveAi() {
  aiError.value = ''
  aiSaving.value = true
  try {
    await botSettingsUpdate({
      ai_enabled: aiEnabled.value,
      ai_mode: aiMode.value,
    })
  } catch (e) {
    aiError.value = e?.message || 'Failed to save AI settings'
  } finally {
    aiSaving.value = false
  }
}
</script>
