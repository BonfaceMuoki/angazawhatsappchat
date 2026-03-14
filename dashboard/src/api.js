const API_BASE_KEY = 'angaza_api_base_url'

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

export async function api(path, options = {}) {
  const base = getApiBaseUrl().replace(/\/$/, '')
  const pathNorm = path.startsWith('/') ? path : '/' + path
  // When base is set, use it; otherwise use relative /api/* (works with Vite proxy in dev)
  const url = base ? `${base}/api${pathNorm}` : `/api${pathNorm}`
  const res = await fetch(url, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
  })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.message || res.statusText || 'Request failed')
  return data
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
