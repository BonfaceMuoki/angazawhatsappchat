import { ref, onMounted } from 'vue'
import { getVapidPublic, subscribePush } from '../api'

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
  const rawData = atob(base64)
  const output = new Uint8Array(rawData.length)
  for (let i = 0; i < rawData.length; i++) output[i] = rawData.charCodeAt(i)
  return output
}

export function usePushNotifications() {
  const supported = ref(
    'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
  )
  const permission = ref(Notification?.permission || 'default')
  const registering = ref(false)
  const error = ref('')

  async function registerAndSubscribe() {
    if (!supported.value) {
      error.value = 'Push not supported in this browser'
      return false
    }
    registering.value = true
    error.value = ''
    try {
      const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/' })
      await reg.update()
      if (Notification.permission === 'default') {
        const result = await Notification.requestPermission()
        permission.value = result
        if (result !== 'granted') {
          error.value = 'Permission denied'
          return false
        }
      }
      const { public_key: vapidPublic } = await getVapidPublic()
      if (!vapidPublic) {
        error.value = 'Server has no VAPID key configured'
        return false
      }
      const subscription = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublic),
      })
      await subscribePush(subscription.toJSON())
      permission.value = 'granted'
      return true
    } catch (e) {
      error.value = e.message || 'Failed to enable notifications'
      return false
    } finally {
      registering.value = false
    }
  }

  onMounted(() => {
    if (typeof Notification !== 'undefined') permission.value = Notification.permission
  })

  return { supported, permission, registering, error, registerAndSubscribe }
}
