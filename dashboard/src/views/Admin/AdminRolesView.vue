<template>
  <div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-angaza-dark">Roles</h1>
      <button
        class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90"
        @click="showCreate = true"
      >
        Create role
      </button>
    </div>

    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <div v-if="loading" class="mt-6 text-slate-500">Loading…</div>
    <AdminTable
      v-else
      :columns="roleColumns"
      :items="roles"
      :pagination="pagination"
      class="mt-6"
      @page="goToPage"
    >
      <template #body>
        <tr v-for="r in roles" :key="r.id" class="hover:bg-slate-50 transition-colors">
          <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ r.role_name }}</td>
          <td class="px-4 py-3 text-sm text-slate-600">{{ r.description || '—' }}</td>
          <td class="whitespace-nowrap px-4 py-3">
            <span v-if="r.is_superadmin" class="text-angaza-dark">Yes</span>
            <span v-else class="text-slate-400">No</span>
          </td>
          <td class="px-4 py-3 text-sm text-slate-600">
            <span class="line-clamp-2">{{ (r.permissions || []).map((p) => p.permission_name).join(', ') || '—' }}</span>
          </td>
        </tr>
      </template>
      <template #empty>No roles yet.</template>
    </AdminTable>

    <!-- Create role modal -->
    <div
      v-if="showCreate"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">Create role</h2>
        <form class="mt-4 space-y-3" @submit.prevent="submitCreate">
          <div>
            <label class="block text-sm font-medium text-slate-700">Role name</label>
            <input
              v-model="createName"
              type="text"
              required
              placeholder="e.g. editor"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Description</label>
            <input
              v-model="createDescription"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"
            />
          </div>
          <label class="flex items-center gap-2">
            <input v-model="createSuperadmin" type="checkbox" class="rounded border-slate-300" />
            <span class="text-sm text-slate-700">Super admin</span>
          </label>
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
import { adminRolesList, adminRolesCreate } from '../../api'
import AdminTable from '../../components/Admin/AdminTable.vue'

const roleColumns = [
  { label: 'Role', class: '' },
  { label: 'Description', class: '' },
  { label: 'Super admin', class: '' },
  { label: 'Permissions', class: '' },
]

const roles = ref([])
const pagination = ref(null)
const loading = ref(true)
const error = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const showCreate = ref(false)
const createName = ref('')
const createDescription = ref('')
const createSuperadmin = ref(false)
const createError = ref('')
const createLoading = ref(false)

async function load(page = 1) {
  loading.value = true
  error.value = ''
  currentPage.value = page
  try {
    const res = await adminRolesList({ per_page: perPage.value, page })
    const paginated = res.data
    roles.value = paginated?.data ?? res.data ?? []
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

async function submitCreate() {
  createError.value = ''
  createLoading.value = true
  try {
    await adminRolesCreate({
      role_name: createName.value,
      description: createDescription.value || null,
      is_superadmin: createSuperadmin.value,
    })
    showCreate.value = false
    createName.value = ''
    createDescription.value = ''
    createSuperadmin.value = false
    load(currentPage.value)
  } catch (e) {
    createError.value = e.message
  } finally {
    createLoading.value = false
  }
}

onMounted(() => load(1))
</script>
