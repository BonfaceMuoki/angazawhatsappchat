<template>
  <div class="fixed inset-0 z-50 flex min-h-screen items-center justify-center bg-angaza-geyser p-4">
    <div class="w-full max-w-md rounded-xl border border-angaza-geyser bg-white p-8 shadow-xl">
      <div class="mb-6 flex items-center gap-3">
        <div
          class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-angaza-dark p-1.5 shadow-sm ring-1 ring-angaza-dark/20"
          aria-hidden="true"
        >
          <img
            src="/angaza-logo.png"
            alt=""
            class="h-full w-full object-contain"
            width="40"
            height="40"
          />
        </div>
        <h1 class="text-xl font-semibold text-angaza-dark">Angaza Admin</h1>
      </div>

      <!-- Step 1: Email + Password -->
      <form v-if="step === 1" class="space-y-4" @submit.prevent="submitLogin">
        <div>
          <label class="block text-sm font-medium text-angaza-paleSky">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="mt-1 w-full rounded-lg border border-angaza-geyser bg-angaza-geyser/50 px-3 py-2 text-angaza-dark placeholder:text-angaza-paleSky/70 focus:border-angaza-accent focus:outline-none focus:ring-1 focus:ring-angaza-accent"
            placeholder="admin@example.com"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-angaza-paleSky">Password</label>
          <input
            v-model="password"
            type="password"
            required
            class="mt-1 w-full rounded-lg border border-angaza-geyser bg-angaza-geyser/50 px-3 py-2 text-angaza-dark focus:border-angaza-accent focus:outline-none focus:ring-1 focus:ring-angaza-accent"
          />
        </div>
        <p v-if="loginError" class="text-sm text-red-600">{{ loginError }}</p>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-angaza-dark px-4 py-2.5 font-medium text-white hover:bg-angaza-dark/90 disabled:opacity-50"
        >
          {{ loading ? 'Sending OTP…' : 'Log in' }}
        </button>
      </form>

      <!-- Step 2: OTP -->
      <form v-else class="space-y-4" @submit.prevent="submitOtp">
        <p class="text-sm text-angaza-paleSky">
          We sent a 6-digit code to <strong class="text-angaza-dark">{{ email }}</strong>. Enter it below.
        </p>
        <div>
          <label class="block text-sm font-medium text-angaza-paleSky">Verification code</label>
          <input
            v-model="otpCode"
            type="text"
            inputmode="numeric"
            maxlength="6"
            required
            class="mt-1 w-full rounded-lg border border-angaza-geyser bg-angaza-geyser/50 px-3 py-2 text-center text-lg tracking-widest text-angaza-dark placeholder:text-angaza-paleSky/70 focus:border-angaza-accent focus:outline-none focus:ring-1 focus:ring-angaza-accent"
            placeholder="123456"
          />
        </div>
        <p v-if="otpError" class="text-sm text-red-600">{{ otpError }}</p>
        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-angaza-dark px-4 py-2.5 font-medium text-white hover:bg-angaza-dark/90 disabled:opacity-50"
        >
          {{ loading ? 'Verifying…' : 'Verify' }}
        </button>
        <button
          type="button"
          class="w-full text-sm text-angaza-paleSky hover:text-angaza-dark"
          @click="step = 1; otpError = ''"
        >
          ← Use a different email
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const route = useRoute()
const { login, verifyOtp } = useAuth()

const step = ref(1)
const email = ref('')
const password = ref('')
const otpCode = ref('')
const loading = ref(false)
const loginError = ref('')
const otpError = ref('')

async function submitLogin() {
  loginError.value = ''
  loading.value = true
  try {
    await login(email.value, password.value)
    step.value = 2
    otpCode.value = ''
  } catch (e) {
    loginError.value = e.message || 'Login failed'
  } finally {
    loading.value = false
  }
}

async function submitOtp() {
  otpError.value = ''
  loading.value = true
  try {
    await verifyOtp(email.value, otpCode.value.trim())
    const redirect = route.query.redirect
    const path = typeof redirect === 'string' && redirect.startsWith('/') ? redirect : '/'
    router.replace(path)
  } catch (e) {
    otpError.value = e.message || 'Invalid code'
  } finally {
    loading.value = false
  }
}
</script>
