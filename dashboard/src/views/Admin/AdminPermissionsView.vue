<template>
  <div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-angaza-dark">Permissions</h1>
      <button
        class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90"
        @click="showCreate = true"
      >
        Create permission
      </button>
    </div>

    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    <div v-if="loading" class="mt-6 text-slate-500">Loading…</div>
    <AdminTable
      v-else
      :columns="permissionColumns"
      :items="permissions"
      :pagination="pagination"
      class="mt-6"
      @page="goToPage"
    >
      <template #body>
        <tr v-for="p in permissions" :key="p.id" class="hover:bg-slate-50 transition-colors">
          <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ p.permission_name }}</td>
          <td class="px-4 py-3 text-sm text-slate-600">{{ p.description || '—' }}</td>
          <td class="whitespace-nowrap px-4 py-3">
            <span v-if="p.is_admin_permission" class="text-angaza-dark">Yes</span>
            <span v-else class="text-slate-400">No</span>
          </td>
        </tr>
      </template>
      <template #empty>No permissions yet.</template>
    </AdminTable>

    <!-- Create permission modal -->
    <div
      v-if="showCreate"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="showCreate = false"
    >
      <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">Create permission</h2>
        <form class="mt-4 space-y-3" @submit.prevent="submitCreate">
          <div>
            <label class="block text-sm font-medium text-slate-700">Permission name</label>
            <input
              v-model="createName"
              type="text"
              required
              placeholder="e.g. reports.view"
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
            <input v-model="createAdminOnly" type="checkbox" class="rounded border-slate-300" />
            <span class="text-sm text-slate-700">Admin permission</span>
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
import { adminPermissionsList, adminPermissionsCreate } from '../../api'
import AdminTable from '../../components/Admin/AdminTable.vue'

const permissionColumns = [
  { label: 'Permission', class: '' },
  { label: 'Description', class: '' },
  { label: 'Admin only', class: '' },
]

const permissions = ref([])
const pagination = ref(null)
const loading = ref(true)
const error = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const showCreate = ref(false)
const createName = ref('')
const createDescription = ref('')
const createAdminOnly = ref(false)
const createError = ref('')
const createLoading = ref(false)

async function load(page = 1) {
  loading.value = true
  error.value = ''
  currentPage.value = page
  try {
    const res = await adminPermissionsList({ per_page: perPage.value, page })
    const paginated = res.data
    permissions.value = paginated?.data ?? res.data ?? []
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
    await adminPermissionsCreate({
      permission_name: createName.value,
      description: createDescription.value || null,
      is_admin_permission: createAdminOnly.value,
    })
    showCreate.value = false
    createName.value = ''
    createDescription.value = ''
    createAdminOnly.value = false
    load(currentPage.value)
  } catch (e) {
    createError.value = e.message
  } finally {
    createLoading.value = false
  }
}

onMounted(() => load(1))
</script>
