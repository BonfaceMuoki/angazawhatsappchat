const API_BASE_KEY = 'angaza_api_base_url'
const AUTH_TOKEN_KEY = 'angaza_auth_token'
const AUTH_USER_KEY = 'angaza_auth_user'

/** Default when running in Docker: dashboard on 5174, Laravel on 8000 (same host). */
function getDefaultApiBaseUrl() {
  if (typeof window === 'undefined') return ''
  const { hostname, port } = window.location
  // Dashboard on 5174 or localhost → assume Laravel on 8000
  if (port === '5174' || hostname === 'localhost') {
    return `http://${hostname}:8000`
  }
  return ''
}

export function getApiBaseUrl() {
  return localStorage.getItem(API_BASE_KEY) || getDefaultApiBaseUrl()
}

export function setApiBaseUrl(url) {
  if (url) localStorage.setItem(API_BASE_KEY, url)
  else localStorage.removeItem(API_BASE_KEY)
}

export function getStoredToken() {
  try {
    return localStorage.getItem(AUTH_TOKEN_KEY) || ''
  } catch {
    return ''
  }
}

export function setStoredAuth(token, user) {
  try {
    if (token) localStorage.setItem(AUTH_TOKEN_KEY, token)
    else localStorage.removeItem(AUTH_TOKEN_KEY)
    if (user) localStorage.setItem(AUTH_USER_KEY, JSON.stringify(user))
    else localStorage.removeItem(AUTH_USER_KEY)
  } catch (_) {}
}

export function getStoredUser() {
  try {
    const raw = localStorage.getItem(AUTH_USER_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

const BOT_PERMISSIONS = ['bot.manage', 'bot.flows', 'bot.nodes', 'bot.edges', 'bot.settings']

export function hasStoredBotPermission() {
  const user = getStoredUser()
  if (!user || !Array.isArray(user.permissions)) return false
  return BOT_PERMISSIONS.some((p) => user.permissions.includes(p))
}

export async function api(path, options = {}) {
  const base = getApiBaseUrl().replace(/\/$/, '')
  const pathNorm = path.startsWith('/') ? path : '/' + path
  const url = base ? `${base}/api${pathNorm}` : `/api${pathNorm}`
  const token = getStoredToken()
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...options.headers,
  }
  if (token) headers['Authorization'] = `Bearer ${token}`
  const res = await fetch(url, { ...options, headers })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) {
    const err = new Error(data.message || res.statusText || 'Request failed')
    err.status = res.status
    throw err
  }
  return data
}

export function dashboardStats() {
  return api('/dashboard/stats')
}

export function leadsList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/leads' + (q ? '?' + q : ''))
}

export function analyticsOverview(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/analytics/overview' + (q ? '?' + q : ''))
}

export function conversationsList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/conversations' + (q ? '?' + q : ''))
}

export function conversationMessages(phone) {
  return api(`/conversations/${encodeURIComponent(phone)}/messages`)
}

export function sendMessage(phone, body) {
  return api(`/conversations/${encodeURIComponent(phone)}/messages`, {
    method: 'POST',
    body: JSON.stringify({ body }),
  })
}

export function clearConversationMessages(phone, resetSession = true) {
  return api(`/conversations/${encodeURIComponent(phone)}/clear-messages`, {
    method: 'POST',
    body: JSON.stringify({ reset_session: resetSession }),
  })
}

export function markConversationRead(phone) {
  return api(`/conversations/${encodeURIComponent(phone)}/read`, {
    method: 'POST',
    body: JSON.stringify({}),
  })
}

export function getVapidPublic() {
  return api('/push-vapid-public')
}

export function subscribePush(subscription) {
  return api('/push-subscriptions', {
    method: 'POST',
    body: JSON.stringify(subscription),
  })
}

export function unsubscribePush(endpoint) {
  return api('/push-subscriptions', {
    method: 'DELETE',
    body: JSON.stringify({ endpoint }),
  })
}

// --- Auth (User Management API) ---
export function authLogin(email, password) {
  return api('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function authVerifyOtp(email, code) {
  return api('/auth/verify-otp', {
    method: 'POST',
    body: JSON.stringify({ email, code }),
  })
}

export function authLogout() {
  setStoredAuth(null, null)
}

// --- Admin API (require Bearer token) ---
export function adminUsersList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/admin/users' + (q ? '?' + q : ''))
}

export function adminUsersCreate(payload) {
  return api('/admin/users', { method: 'POST', body: JSON.stringify(payload) })
}

export function adminUsersInvite(email, roleId) {
  return api('/admin/users/invite', {
    method: 'POST',
    body: JSON.stringify({ email, role_id: roleId }),
  })
}

export function adminUsersAssignRoles(userId, roleIds) {
  return api(`/admin/users/${userId}/roles`, {
    method: 'POST',
    body: JSON.stringify({ role_ids: roleIds }),
  })
}

export function adminUsersBlock(userId) {
  return api(`/admin/users/${userId}/block`, { method: 'POST' })
}

export function adminRolesList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/admin/roles' + (q ? '?' + q : ''))
}

export function adminRolesCreate(payload) {
  return api('/admin/roles', { method: 'POST', body: JSON.stringify(payload) })
}

export function adminRolesAssignPermissions(roleId, permissionIds) {
  return api(`/admin/roles/${roleId}/permissions`, {
    method: 'POST',
    body: JSON.stringify({ permission_ids: permissionIds }),
  })
}

export function adminPermissionsList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/admin/permissions' + (q ? '?' + q : ''))
}

// --- Bot / Chatbot Management (Admin) ---
export function botFlowsList() {
  return api('/admin/bot/flows')
}

export function botFlowGet(id) {
  return api(`/admin/bot/flows/${id}`)
}

export function botFlowsCreate(payload) {
  return api('/admin/bot/flows', { method: 'POST', body: JSON.stringify(payload) })
}

export function botFlowsUpdate(id, payload) {
  return api(`/admin/bot/flows/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
}

export function botFlowsDelete(id) {
  return api(`/admin/bot/flows/${id}`, { method: 'DELETE' })
}

export function botNodesList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/admin/bot/nodes' + (q ? '?' + q : ''))
}

export function botNodesCreate(payload) {
  return api('/admin/bot/nodes', { method: 'POST', body: JSON.stringify(payload) })
}

export function botNodesUpdate(id, payload) {
  return api(`/admin/bot/nodes/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
}

export function botNodesDelete(id) {
  return api(`/admin/bot/nodes/${id}`, { method: 'DELETE' })
}

export function botEdgesList(params = {}) {
  const q = new URLSearchParams(params).toString()
  return api('/admin/bot/edges' + (q ? '?' + q : ''))
}

export function botEdgesCreate(payload) {
  return api('/admin/bot/edges', { method: 'POST', body: JSON.stringify(payload) })
}

export function botEdgesUpdate(id, payload) {
  return api(`/admin/bot/edges/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
}

export function botEdgesDelete(id) {
  return api(`/admin/bot/edges/${id}`, { method: 'DELETE' })
}

export function botSettingsGet() {
  return api('/admin/bot/settings')
}

export function botSettingsUpdate(payload) {
  return api('/admin/bot/settings', { method: 'PUT', body: JSON.stringify(payload) })
}

export function adminPermissionsCreate(payload) {
  return api('/admin/permissions', { method: 'POST', body: JSON.stringify(payload) })
}
