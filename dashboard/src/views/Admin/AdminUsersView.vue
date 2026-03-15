<template>
  <div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-angaza-dark">Users</h1>
      <div class="flex gap-2">
        <button
          class="rounded-lg border border-angaza-dark bg-white px-4 py-2 text-sm font-medium text-angaza-dark hover:bg-slate-50"
          @click="showInvite = true"
        >
          Invite user
        </button>
        <button
          class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90"
          @click="showCreate = true"
        >
          Create user
        </button>
      </div>
    </div>

    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <div v-if="loading" class="mt-6 text-slate-500">Loading…</div>
    <AdminTable
      v-else
      :columns="userColumns"
      :items="users"
      :pagination="pagination"
      class="mt-6"
      @page="goToPage"
    >
      <template #body>
        <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50 transition-colors">
          <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-900">{{ u.name }}</td>
          <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ u.email }}</td>
          <td class="whitespace-nowrap px-4 py-3">
            <span
              class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="u.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            >
              {{ u.status }}
            </span>
          </td>
          <td class="px-4 py-3 text-sm text-slate-600">
            <span class="line-clamp-2">{{ (u.roles || []).map((r) => r.role_name).join(', ') || '—' }}</span>
          </td>
        </tr>
      </template>
      <template #empty>No users yet.</template>
    </AdminTable>

    <!-- Invite modal -->
    <div
      v-if="showInvite"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showInvite = false"
    >
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">Invite user</h2>
        <form class="mt-4 space-y-3" @submit.prevent="submitInvite">
          <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input
              v-model="inviteEmail"
              type="email"
              required
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Role</label>
            <select v-model="inviteRoleId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
            </select>
          </div>
          <p v-if="inviteError" class="text-sm text-red-600">{{ inviteError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" class="flex-1 rounded-lg border border-slate-300 py-2" @click="showInvite = false">
              Cancel
            </button>
            <button type="submit" class="flex-1 rounded-lg bg-angaza-dark py-2 text-white" :disabled="inviteLoading">
              {{ inviteLoading ? 'Sending…' : 'Send invite' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Create user modal -->
    <div
      v-if="showCreate"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">Create user</h2>
        <form class="mt-4 space-y-3" @submit.prevent="submitCreate">
          <div>
            <label class="block text-sm font-medium text-slate-700">Name</label>
            <input v-model="createName" type="text" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Email</label>
            <input v-model="createEmail" type="email" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Password</label>
            <input v-model="createPassword" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Confirm password</label>
            <input v-model="createPasswordConfirm" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Role</label>
            <select v-model="createRoleId" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
              <option :value="''">None</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.role_name }}</option>
            </select>
          </div>
          <p v-if="createError" class="text-sm text-red-600">{{ createError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" class="flex-1 rounded-lg border border-slate-300 py-2" @click="showCreate = false">
              Cancel
            </button>
            <button type="submit" class="flex-1 rounded-lg bg-angaza-dark py-2 text-white" :disabled="createLoading">
              {{ createLoading ? 'Creating…' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  adminUsersList,
  adminUsersInvite,
  adminUsersCreate,
  adminRolesList,
} from '../../api'
import AdminTable from '../../components/Admin/AdminTable.vue'

const userColumns = [
  { label: 'Name', class: '' },
  { label: 'Email', class: '' },
  { label: 'Status', class: '' },
  { label: 'Roles', class: '' },
]

const users = ref([])
const pagination = ref(null)
const roles = ref([])
const loading = ref(true)
const error = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const showInvite = ref(false)
const showCreate = ref(false)
const inviteEmail = ref('')
const inviteRoleId = ref('')
const inviteError = ref('')
const inviteLoading = ref(false)
const createName = ref('')
const createEmail = ref('')
const createPassword = ref('')
const createPasswordConfirm = ref('')
const createRoleId = ref('')
const createError = ref('')
const createLoading = ref(false)

async function loadRoles() {
  try {
    const rolesRes = await adminRolesList({ per_page: 100 })
    roles.value = rolesRes.data?.data ?? rolesRes.data ?? []
    if (roles.value.length && !inviteRoleId.value) inviteRoleId.value = roles.value[0].id
    if (roles.value.length && !createRoleId.value) createRoleId.value = roles.value[0].id
  } catch (_) {}
}

async function load(page = 1) {
  loading.value = true
  error.value = ''
  currentPage.value = page
  try {
    const usersRes = await adminUsersList({ per_page: perPage.value, page })
    const paginated = usersRes.data
    users.value = paginated?.data ?? usersRes.data ?? []
    pagination.value = paginated
      ? {
          current_page: paginated.current_page,
          last_page: paginated.last_page,
          per_page: paginated.per_page,
          total: paginated.total,
        }
      : null
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  load(page)
}

async function submitInvite() {
  inviteError.value = ''
  inviteLoading.value = true
  try {
    await adminUsersInvite(inviteEmail.value, inviteRoleId.value)
    showInvite.value = false
    inviteEmail.value = ''
    load(currentPage.value)
  } catch (e) {
    inviteError.value = e.message
  } finally {
    inviteLoading.value = false
  }
}

async function submitCreate() {
  if (createPassword.value !== createPasswordConfirm.value) {
    createError.value = 'Passwords do not match'
    return
  }
  createError.value = ''
  createLoading.value = true
  try {
    await adminUsersCreate({
      name: createName.value,
      email: createEmail.value,
      password: createPassword.value,
      password_confirmation: createPasswordConfirm.value,
      role_ids: createRoleId.value ? [Number(createRoleId.value)] : [],
    })
    showCreate.value = false
    createName.value = ''
    createEmail.value = ''
    createPassword.value = ''
    createPasswordConfirm.value = ''
    load(currentPage.value)
  } catch (e) {
    createError.value = e.message
  } finally {
    createLoading.value = false
  }
}

onMounted(() => {
  loadRoles()
  load(1)
})
</script>
