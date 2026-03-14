import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { title: 'Dashboard' },
  },
  {
    path: '/chats',
    name: 'chats',
    component: () => import('../views/ChatsView.vue'),
    meta: { title: 'Chats' },
  },
  {
    path: '/chats/:phone',
    name: 'chat',
    component: () => import('../views/ChatView.vue'),
    meta: { title: 'Chat' },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('../views/SettingsView.vue'),
    meta: { title: 'Settings' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | Angaza Dashboard` : 'Angaza Dashboard'
})

export default router
