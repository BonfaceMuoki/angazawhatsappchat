<template>
  <div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-angaza-dark">Chatbot — Flows</h1>
        <p class="mt-1 text-slate-500">Manage conversation journeys (e.g. Admissions, Student Support).</p>
      </div>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-lg border border-angaza-dark bg-white px-4 py-2 text-sm font-medium text-angaza-dark hover:bg-slate-50"
          @click="showSurveyWizard = true"
        >
          Set up survey
        </button>
        <button
          type="button"
          class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90"
          @click="showCreate = true"
        >
          Create flow
        </button>
      </div>
    </div>

    <p v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
      <router-link v-if="isForbidden" to="/admin" class="ml-1 font-medium text-angaza-accent hover:underline">Back to Admin</router-link>
    </p>
    <div v-if="loading" class="mt-6 text-slate-500">Loading…</div>
    <div v-else class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-angaza-dark">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Name</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Entry node</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Router</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Order</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white">Active</th>
            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 bg-white">
          <tr v-for="f in flows" :key="f.id" class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <span class="font-medium text-angaza-dark">{{ f.name }}</span>
              <p v-if="f.description" class="text-xs text-slate-500 truncate max-w-[200px]">{{ f.description }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ f.entry_node?.node_key ?? '—' }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="f.show_in_router ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-600'">
                {{ f.show_in_router ? 'Yes' : 'No' }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ f.display_order }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="f.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'">
                {{ f.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <router-link :to="`/admin/chatbot/flows/${f.id}`" class="text-angaza-accent hover:underline text-sm mr-2">Build</router-link>
              <button type="button" class="text-angaza-accent hover:underline text-sm" @click="openEdit(f)">Edit</button>
            </td>
          </tr>
          <tr v-if="flows.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No flows yet. Create one to get started.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Survey wizard modal -->
    <SurveyWizardModal
      v-if="showSurveyWizard"
      :api="botApi"
      @close="showSurveyWizard = false"
      @created="onSurveyCreated"
    />

    <!-- Create / Edit modal -->
    <div
      v-if="showCreate || editing"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeModal"
    >
      <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">{{ editing ? 'Edit flow' : 'Create flow' }}</h2>
        <form class="mt-4 space-y-3" @submit.prevent="editing ? updateFlow() : createFlow()">
          <div>
            <label class="block text-sm font-medium text-slate-700">Name</label>
            <input v-model="form.name" type="text" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="form.description" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.show_in_router" type="checkbox" class="rounded border-slate-300" />
            <label class="text-sm text-slate-700">Show in router menu</label>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Display order</label>
            <input v-model.number="form.display_order" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" class="rounded border-slate-300" />
            <label class="text-sm text-slate-700">Active</label>
          </div>
          <p v-if="formError" class="text-sm text-red-600">{{ formError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" class="flex-1 rounded-lg border border-slate-300 py-2" @click="closeModal">Cancel</button>
            <button type="submit" class="flex-1 rounded-lg bg-angaza-dark py-2 text-white" :disabled="saving">{{ editing ? 'Update' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { botFlowsList, botFlowsCreate, botFlowsUpdate, botNodesCreate, botEdgesCreate } from '../../api'
import SurveyWizardModal from '../../components/SurveyWizardModal.vue'

const router = useRouter()
const flows = ref([])
const loading = ref(true)
const error = ref('')
const isForbidden = ref(false)
const showCreate = ref(false)
const showSurveyWizard = ref(false)
const editing = ref(null)

const botApi = {
  botFlowsCreate,
  botFlowsUpdate,
  botNodesCreate,
  botEdgesCreate,
}

function onSurveyCreated(flow) {
  showSurveyWizard.value = false
  load().then(() => {
    if (flow?.id) router.push(`/admin/chatbot/flows/${flow.id}`)
  })
}
const saving = ref(false)
const formError = ref('')

const form = ref({
  name: '',
  description: '',
  show_in_router: true,
  display_order: 0,
  is_active: true,
})

async function load() {
  loading.value = true
  error.value = ''
  isForbidden.value = false
  try {
    const res = await botFlowsList()
    flows.value = res.data ?? []
  } catch (e) {
    isForbidden.value = e.status === 403
    error.value = isForbidden.value
      ? "You don't have permission to manage the chatbot. Contact an administrator."
      : (e.message || 'Failed to load flows')
    flows.value = []
  } finally {
    loading.value = false
  }
}

function openEdit(f) {
  editing.value = f
  form.value = {
    name: f.name,
    description: f.description ?? '',
    show_in_router: f.show_in_router ?? true,
    display_order: f.display_order ?? 0,
    is_active: f.is_active ?? true,
  }
  formError.value = ''
}

function closeModal() {
  showCreate.value = false
  editing.value = null
  form.value = { name: '', description: '', show_in_router: true, display_order: 0, is_active: true }
  formError.value = ''
}

async function createFlow() {
  saving.value = true
  formError.value = ''
  try {
    await botFlowsCreate(form.value)
    closeModal()
    await load()
  } catch (e) {
    formError.value = e.message || 'Failed to create'
  } finally {
    saving.value = false
  }
}

async function updateFlow() {
  if (!editing.value) return
  saving.value = true
  formError.value = ''
  try {
    await botFlowsUpdate(editing.value.id, form.value)
    closeModal()
    await load()
  } catch (e) {
    formError.value = e.message || 'Failed to update'
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
