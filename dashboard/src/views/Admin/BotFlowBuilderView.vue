<template>
  <div>
    <nav class="mb-4 flex items-center gap-2 text-sm text-slate-600">
      <router-link to="/admin/chatbot/flows" class="text-angaza-accent hover:underline">Chatbot Flows</router-link>
      <span>/</span>
      <span class="font-medium text-angaza-dark">{{ flow?.name ?? '…' }}</span>
    </nav>

    <div class="flex flex-col gap-6">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-angaza-dark">Flow: {{ flow?.name ?? '…' }}</h1>
          <p v-if="flow?.description" class="mt-1 text-slate-500">{{ flow.description }}</p>
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm font-medium text-slate-700">Entry node</label>
          <select
            v-model="entryNodeId"
            class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
            :disabled="loading || !nodes.length"
          >
            <option :value="null">— Select —</option>
            <option v-for="n in nodes" :key="n.id" :value="n.id">{{ n.node_key }}</option>
          </select>
          <button
            type="button"
            class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90 disabled:opacity-50"
            :disabled="savingEntry || entryNodeId === (flow?.entry_node_id ?? null)"
            @click="saveEntryNode"
          >
            {{ savingEntry ? 'Saving…' : 'Save' }}
          </button>
        </div>
      </div>

      <p v-if="error" class="text-sm text-red-600">
        {{ error }}
        <router-link v-if="isForbidden" to="/admin" class="ml-1 font-medium text-angaza-accent hover:underline">Back to Admin</router-link>
      </p>
      <p v-if="loading" class="text-slate-500">Loading…</p>

      <template v-else>
        <!-- Nodes -->
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="text-lg font-semibold text-angaza-dark">Nodes</h2>
            <button
              type="button"
              class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90"
              @click="openNodeModal()"
            >
              Add node
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-angaza-dark">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Key</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Type</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Message</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Entry</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Active</th>
                  <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-white">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="n in nodes" :key="n.id" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-sm">{{ n.node_key }}</td>
                  <td class="px-4 py-2">
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">{{ n.type }}</span>
                  </td>
                  <td class="max-w-xs px-4 py-2 text-sm text-slate-600 truncate" :title="n.message">{{ n.message }}</td>
                  <td class="px-4 py-2">
                    <span v-if="n.is_entry" class="rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800">Yes</span>
                    <span v-else class="text-slate-400">—</span>
                  </td>
                  <td class="px-4 py-2">
                    <span :class="n.is_active ? 'text-green-600' : 'text-slate-400'">{{ n.is_active ? 'Yes' : 'No' }}</span>
                  </td>
                  <td class="px-4 py-2 text-right">
                    <button type="button" class="text-angaza-accent hover:underline text-sm mr-2" @click="openNodeModal(n)">Edit</button>
                    <button type="button" class="text-red-600 hover:underline text-sm" @click="deleteNode(n)">Delete</button>
                  </td>
                </tr>
                <tr v-if="nodes.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-slate-500">No nodes. Add one to start the journey.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Edges -->
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
            <h2 class="text-lg font-semibold text-angaza-dark">Edges (transitions)</h2>
            <button
              type="button"
              class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90"
              :disabled="nodes.length < 2"
              @click="openEdgeModal()"
            >
              Add edge
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
              <thead class="bg-angaza-dark">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">From</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">To</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Option label</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Option value</th>
                  <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-white">Order</th>
                  <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-white">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="e in edges" :key="e.id" class="hover:bg-slate-50">
                  <td class="px-4 py-2 font-mono text-sm">{{ e.source_node?.node_key ?? e.source_node_id }}</td>
                  <td class="px-4 py-2 font-mono text-sm">{{ e.target_node?.node_key ?? e.target_node_id }}</td>
                  <td class="px-4 py-2 text-sm">{{ e.option_label }}</td>
                  <td class="px-4 py-2 font-mono text-sm text-slate-600">{{ e.option_value }}</td>
                  <td class="px-4 py-2 text-sm">{{ e.order }}</td>
                  <td class="px-4 py-2 text-right">
                    <button type="button" class="text-angaza-accent hover:underline text-sm mr-2" @click="openEdgeModal(e)">Edit</button>
                    <button type="button" class="text-red-600 hover:underline text-sm" @click="deleteEdge(e)">Delete</button>
                  </td>
                </tr>
                <tr v-if="edges.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-slate-500">No edges. Add nodes first, then connect them with edges.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </template>
    </div>

    <!-- Node modal -->
    <div
      v-if="nodeModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="nodeModalOpen = false"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">{{ editingNode ? 'Edit node' : 'Add node' }}</h2>
        <form class="mt-4 space-y-3" @submit.prevent="editingNode ? updateNode() : createNode()">
          <div>
            <label class="block text-sm font-medium text-slate-700">Node key (optional)</label>
            <div class="mt-1 flex gap-2">
              <input
                v-model="nodeForm.node_key"
                type="text"
                maxlength="100"
                class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 font-mono"
                :required="!!editingNode"
                :readonly="!!editingNode"
                placeholder="Leave blank — system will assign n_…"
              />
              <button
                v-if="!editingNode"
                type="button"
                class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"
                title="Fill with a random unique key"
                @click="suggestNodeKey"
              >
                Suggest
              </button>
            </div>
            <p v-if="editingNode" class="mt-1 text-xs text-slate-500">Key cannot be changed when editing.</p>
            <p v-else class="mt-1 text-xs text-slate-500">
              Leave empty or click <strong>Suggest</strong> to auto-generate. Or type your own (e.g. <code class="text-xs">entry</code>) — must be unique in this flow. When adding edges, you pick nodes from the list by this key.
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Type</label>
            <select v-model="nodeForm.type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="text">text</option>
              <option value="buttons">buttons</option>
              <option value="list">list</option>
            </select>
            <p class="mt-1 text-xs text-slate-500">
              <strong>text</strong> = plain message only.
              <strong>buttons</strong> = up to 3 reply buttons (WhatsApp limit); more than 3 outgoing edges are sent as a list automatically.
              <strong>list</strong> = interactive list menu (use for 4+ options, or anytime you prefer a menu).
              Add one edge per option.
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Message</label>
            <textarea v-model="nodeForm.message" rows="4" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></textarea>
          </div>
          <div class="flex items-center gap-4">
            <label class="flex items-center gap-2">
              <input v-model="nodeForm.is_entry" type="checkbox" class="rounded border-slate-300" />
              <span class="text-sm text-slate-700">Entry node</span>
            </label>
            <label class="flex items-center gap-2">
              <input v-model="nodeForm.is_active" type="checkbox" class="rounded border-slate-300" />
              <span class="text-sm text-slate-700">Active</span>
            </label>
          </div>
          <p v-if="nodeFormError" class="text-sm text-red-600">{{ nodeFormError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" class="flex-1 rounded-lg border border-slate-300 py-2" @click="nodeModalOpen = false">Cancel</button>
            <button type="submit" class="flex-1 rounded-lg bg-angaza-dark py-2 text-white" :disabled="nodeSaving">{{ editingNode ? 'Update' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edge modal -->
    <div
      v-if="edgeModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="edgeModalOpen = false"
    >
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-angaza-dark">{{ editingEdge ? 'Edit edge' : 'Add edge' }}</h2>
        <form class="mt-4 space-y-3" @submit.prevent="editingEdge ? updateEdge() : createEdge()">
          <div>
            <label class="block text-sm font-medium text-slate-700">From node</label>
            <select v-model.number="edgeForm.source_node_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
              <option :value="null">— Select —</option>
              <option v-for="n in nodes" :key="n.id" :value="n.id">{{ n.node_key }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">To node</label>
            <select v-model.number="edgeForm.target_node_id" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
              <option :value="null">— Select —</option>
              <option v-for="n in nodes" :key="n.id" :value="n.id">{{ n.node_key }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Option label (shown to user)</label>
            <input v-model="edgeForm.option_label" type="text" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Option value (internal)</label>
            <input v-model="edgeForm.option_value" type="text" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Order</label>
            <input v-model.number="edgeForm.order" type="number" min="0" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" />
          </div>
          <p v-if="edgeFormError" class="text-sm text-red-600">{{ edgeFormError }}</p>
          <div class="flex gap-2 pt-2">
            <button type="button" class="flex-1 rounded-lg border border-slate-300 py-2" @click="edgeModalOpen = false">Cancel</button>
            <button type="submit" class="flex-1 rounded-lg bg-angaza-dark py-2 text-white" :disabled="edgeSaving">{{ editingEdge ? 'Update' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  botFlowGet,
  botFlowsUpdate,
  botNodesList,
  botNodesCreate,
  botNodesUpdate,
  botNodesDelete,
  botEdgesList,
  botEdgesCreate,
  botEdgesUpdate,
  botEdgesDelete,
} from '../../api'

const route = useRoute()
const flowId = computed(() => route.params.id)

const flow = ref(null)
const nodes = ref([])
const edges = ref([])
const loading = ref(true)
const error = ref('')
const isForbidden = ref(false)
const entryNodeId = ref(null)
const savingEntry = ref(false)

function setError(e) {
  isForbidden.value = e?.status === 403
  error.value = isForbidden.value
    ? "You don't have permission to manage the chatbot."
    : (e?.message || 'Something went wrong.')
}

async function load() {
  if (!flowId.value) return
  loading.value = true
  error.value = ''
  isForbidden.value = false
  try {
    const [flowRes, nodesRes, edgesRes] = await Promise.all([
      botFlowGet(flowId.value),
      botNodesList({ flow_id: flowId.value }),
      botEdgesList({ flow_id: flowId.value }),
    ])
    flow.value = flowRes.data
    nodes.value = nodesRes.data ?? []
    edges.value = edgesRes.data ?? []
    entryNodeId.value = flow.value?.entry_node_id ?? null
  } catch (e) {
    setError(e)
    flow.value = null
    nodes.value = []
    edges.value = []
  } finally {
    loading.value = false
  }
}

watch(flowId, load, { immediate: true })

async function saveEntryNode() {
  if (!flowId.value || entryNodeId.value === (flow.value?.entry_node_id ?? null)) return
  savingEntry.value = true
  error.value = ''
  try {
    const res = await botFlowsUpdate(flowId.value, { entry_node_id: entryNodeId.value })
    flow.value = res.data
  } catch (e) {
    setError(e)
  } finally {
    savingEntry.value = false
  }
}

// Node modal
const nodeModalOpen = ref(false)
const editingNode = ref(null)
const nodeSaving = ref(false)
const nodeFormError = ref('')
const nodeForm = ref({
  node_key: '',
  type: 'text',
  message: '',
  is_entry: false,
  is_active: true,
})

function suggestNodeKey() {
  const bytes = new Uint8Array(8)
  crypto.getRandomValues(bytes)
  nodeForm.value.node_key = 'n_' + Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('')
}

function openNodeModal(node = null) {
  editingNode.value = node
  if (node) {
    nodeForm.value = {
      node_key: node.node_key,
      type: node.type,
      message: node.message ?? '',
      is_entry: node.is_entry ?? false,
      is_active: node.is_active !== false,
    }
  } else {
    nodeForm.value = { node_key: '', type: 'text', message: '', is_entry: false, is_active: true }
  }
  nodeFormError.value = ''
  nodeModalOpen.value = true
}

function closeNodeModal() {
  nodeModalOpen.value = false
  editingNode.value = null
}

async function createNode() {
  nodeFormError.value = ''
  nodeSaving.value = true
  try {
    const trimmedKey = nodeForm.value.node_key?.trim() ?? ''
    const payload = {
      flow_id: Number(flowId.value),
      type: nodeForm.value.type,
      message: nodeForm.value.message,
      is_entry: nodeForm.value.is_entry,
      is_active: nodeForm.value.is_active,
    }
    if (trimmedKey) {
      payload.node_key = trimmedKey
    }
    await botNodesCreate(payload)
    closeNodeModal()
    await load()
  } catch (e) {
    nodeFormError.value = e?.message || 'Failed to create node'
  } finally {
    nodeSaving.value = false
  }
}

async function updateNode() {
  if (!editingNode.value) return
  nodeFormError.value = ''
  nodeSaving.value = true
  try {
    await botNodesUpdate(editingNode.value.id, {
      type: nodeForm.value.type,
      message: nodeForm.value.message,
      is_entry: nodeForm.value.is_entry,
      is_active: nodeForm.value.is_active,
    })
    closeNodeModal()
    await load()
  } catch (e) {
    nodeFormError.value = e?.message || 'Failed to update node'
  } finally {
    nodeSaving.value = false
  }
}

async function deleteNode(node) {
  if (!confirm(`Delete node "${node.node_key}"? Edges using it will break.`)) return
  try {
    await botNodesDelete(node.id)
    await load()
  } catch (e) {
    setError(e)
  }
}

// Edge modal
const edgeModalOpen = ref(false)
const editingEdge = ref(null)
const edgeSaving = ref(false)
const edgeFormError = ref('')
const edgeForm = ref({
  source_node_id: null,
  target_node_id: null,
  option_label: '',
  option_value: '',
  order: 0,
})

function openEdgeModal(edge = null) {
  editingEdge.value = edge
  if (edge) {
    edgeForm.value = {
      source_node_id: edge.source_node_id,
      target_node_id: edge.target_node_id,
      option_label: edge.option_label ?? '',
      option_value: edge.option_value ?? '',
      order: edge.order ?? 0,
    }
  } else {
    edgeForm.value = {
      source_node_id: nodes.value[0]?.id ?? null,
      target_node_id: nodes.value[1]?.id ?? nodes.value[0]?.id ?? null,
      option_label: '',
      option_value: '',
      order: edges.value.length,
    }
  }
  edgeFormError.value = ''
  edgeModalOpen.value = true
}

function closeEdgeModal() {
  edgeModalOpen.value = false
  editingEdge.value = null
}

async function createEdge() {
  edgeFormError.value = ''
  edgeSaving.value = true
  try {
    await botEdgesCreate({
      source_node_id: edgeForm.value.source_node_id,
      target_node_id: edgeForm.value.target_node_id,
      option_label: edgeForm.value.option_label,
      option_value: edgeForm.value.option_value,
      order: edgeForm.value.order,
    })
    closeEdgeModal()
    await load()
  } catch (e) {
    edgeFormError.value = e?.message || 'Failed to create edge'
  } finally {
    edgeSaving.value = false
  }
}

async function updateEdge() {
  if (!editingEdge.value) return
  edgeFormError.value = ''
  edgeSaving.value = true
  try {
    await botEdgesUpdate(editingEdge.value.id, {
      source_node_id: edgeForm.value.source_node_id,
      target_node_id: edgeForm.value.target_node_id,
      option_label: edgeForm.value.option_label,
      option_value: edgeForm.value.option_value,
      order: edgeForm.value.order,
    })
    closeEdgeModal()
    await load()
  } catch (e) {
    edgeFormError.value = e?.message || 'Failed to update edge'
  } finally {
    edgeSaving.value = false
  }
}

async function deleteEdge(edge) {
  if (!confirm('Delete this edge?')) return
  try {
    await botEdgesDelete(edge.id)
    await load()
  } catch (e) {
    setError(e)
  }
}
</script>
