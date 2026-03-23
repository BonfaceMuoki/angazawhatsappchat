import { ref, computed } from 'vue'
import {
  getStoredToken,
  getStoredUser,
  setStoredAuth,
  authLogin as apiLogin,
  authVerifyOtp as apiVerifyOtp,
  authLogout as apiLogout,
} from '../api'

const BOT_PERMISSIONS = ['bot.manage', 'bot.flows', 'bot.nodes', 'bot.edges', 'bot.settings']

function safeToken() {
  try {
    return getStoredToken()
  } catch {
    return ''
  }
}
function safeUser() {
  try {
    return getStoredUser()
  } catch {
    return null
  }
}
const token = ref(safeToken())
const user = ref(safeUser())

export function useAuth() {
  const isLoggedIn = computed(() => !!token.value)

  const permissions = computed(() => {
    const u = user.value
    if (!u || !Array.isArray(u.permissions)) return []
    return u.permissions
  })

  const hasBotPermission = computed(() => {
    const perms = permissions.value
    return BOT_PERMISSIONS.some((p) => perms.includes(p))
  })

  function setAuth(newToken, newUser) {
    token.value = newToken || ''
    user.value = newUser || null
    setStoredAuth(newToken, newUser)
  }

  async function login(email, password) {
    const res = await apiLogin(email, password)
    return res
  }

  async function verifyOtp(email, code) {
    const res = await apiVerifyOtp(email, code)
    if (res.data?.token && res.data?.user) {
      setAuth(res.data.token, res.data.user)
      return res.data
    }
    if (res.token && res.user) {
      setAuth(res.token, res.user)
      return { token: res.token, user: res.user }
    }
    throw new Error('Invalid response')
  }

  function logout() {
    apiLogout()
    token.value = ''
    user.value = null
  }

  return {
    token,
    user,
    isLoggedIn,
    permissions,
    hasBotPermission,
    login,
    verifyOtp,
    logout,
    setAuth,
  }
}
