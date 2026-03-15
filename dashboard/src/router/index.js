import { createRouter, createWebHistory } from 'vue-router'
import { getStoredToken, hasStoredBotPermission } from '../api'
import LoginView from '../views/LoginView.vue'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('../views/DashboardView.vue'),
    meta: { title: 'Dashboard', auth: true },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { title: 'Log in', public: true },
  },
  {
    path: '/chats',
    name: 'chats',
    component: () => import('../views/ChatsView.vue'),
    meta: { title: 'Chats', auth: true },
  },
  {
    path: '/leads',
    name: 'leads',
    component: () => import('../views/LeadsView.vue'),
    meta: { title: 'Leads', auth: true },
  },
  {
    path: '/analytics',
    name: 'analytics',
    component: () => import('../views/AnalyticsView.vue'),
    meta: { title: 'Analysis', auth: true },
  },
  {
    path: '/chats/:phone',
    name: 'chat',
    component: () => import('../views/ChatsView.vue'),
    meta: { title: 'Chats', auth: true },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('../views/SettingsView.vue'),
    meta: { title: 'Settings', auth: true },
  },
  {
    path: '/admin',
    redirect: '/admin/users',
  },
  {
    path: '/admin/users',
    name: 'admin-users',
    component: () => import('../views/Admin/AdminUsersView.vue'),
    meta: { title: 'Users', auth: true },
  },
  {
    path: '/admin/roles',
    name: 'admin-roles',
    component: () => import('../views/Admin/AdminRolesView.vue'),
    meta: { title: 'Roles', auth: true },
  },
  {
    path: '/admin/permissions',
    name: 'admin-permissions',
    component: () => import('../views/Admin/AdminPermissionsView.vue'),
    meta: { title: 'Permissions', auth: true },
  },
  {
    path: '/admin/chatbot/flows',
    name: 'admin-chatbot-flows',
    component: () => import('../views/Admin/BotFlowsView.vue'),
    meta: { title: 'Chatbot Flows', auth: true, botPermission: true },
  },
  {
    path: '/admin/chatbot/flows/:id',
    name: 'admin-chatbot-flow-builder',
    component: () => import('../views/Admin/BotFlowBuilderView.vue'),
    meta: { title: 'Flow Builder', auth: true, botPermission: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} | Angaza Dashboard` : 'Angaza Dashboard'
  const path = (to.path || '').replace(/\/$/, '')
  const token = getStoredToken()
  if (to.meta.auth && !token) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  if (path === '/login' && token) {
    const redirect = to.query.redirect
    return { path: typeof redirect === 'string' && redirect.startsWith('/') ? redirect : '/' }
  }
  if (to.meta.botPermission && !hasStoredBotPermission()) {
    return { path: '/admin' }
  }
})

export default router
