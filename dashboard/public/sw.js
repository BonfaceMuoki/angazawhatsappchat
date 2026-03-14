/* Push notification service worker */
self.addEventListener('push', function (event) {
  let data = { title: 'New message', body: '', url: '/' }
  if (event.data) {
    try {
      data = event.data.json()
    } catch (_) {}
  }
  const options = {
    body: data.body || 'New message received',
    icon: '/vite.svg',
    data: { url: data.url || '/' },
    tag: 'angaza-chat',
    renotify: true,
    requireInteraction: true,
    silent: false,
  }
  event.waitUntil(self.registration.showNotification(data.title || 'New message', options))
})

self.addEventListener('notificationclick', function (event) {
  event.notification.close()
  const url = event.notification.data?.url || '/chats'
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (const client of clientList) {
        if (client.url.indexOf(self.location.origin) === 0 && 'focus' in client) {
          client.navigate(url)
          return client.focus()
        }
      }
      if (self.clients.openWindow) return self.clients.openWindow(self.location.origin + (url.startsWith('/') ? url : '/' + url))
    })
  )
})
