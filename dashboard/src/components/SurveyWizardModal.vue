<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="$emit('close')">
    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-xl">
      <div class="sticky top-0 z-10 border-b border-slate-200 bg-white px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-angaza-dark">Set up complete survey</h2>
          <button type="button" class="text-slate-400 hover:text-slate-600" @click="$emit('close')">✕</button>
        </div>
        <div class="mt-2 flex gap-2">
          <button
            type="button"
            class="rounded px-2 py-1 text-sm font-medium"
            :class="wizardStep === 1 ? 'bg-angaza-dark text-white' : 'bg-slate-100 text-slate-600'"
            @click="wizardStep = 1"
          >
            1. Flow details
          </button>
          <button
            type="button"
            class="rounded px-2 py-1 text-sm font-medium"
            :class="wizardStep === 2 ? 'bg-angaza-dark text-white' : 'bg-slate-100 text-slate-600'"
            @click="wizardStep = 2"
          >
            2. Survey steps
          </button>
        </div>
      </div>

      <form class="p-6 space-y-6" @submit.prevent="wizardStep === 1 ? nextStep() : createSurvey()">
        <!-- Step 1: Flow details -->
        <div v-show="wizardStep === 1" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Survey / Flow name</label>
            <input v-model="flowName" type="text" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. Admissions, Feedback" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Description</label>
            <textarea v-model="flowDescription" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Short description of this survey"></textarea>
          </div>
          <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90">Next: Add steps</button>
          </div>
        </div>

        <!-- Step 2: Survey steps -->
        <div v-show="wizardStep === 2" class="space-y-6">
          <p class="text-sm text-slate-600">Add each question or message as a step. For buttons/list, add options and choose where each option leads (next step or end).</p>

          <div v-for="(step, index) in steps" :key="step.id" class="rounded-lg border border-slate-200 bg-slate-50/50 p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-medium text-angaza-dark">Step {{ index + 1 }}</span>
              <button v-if="steps.length > 1" type="button" class="text-sm text-red-600 hover:underline" @click="removeStep(index)">Remove</button>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Message (what the user sees)</label>
              <textarea v-model="step.message" rows="3" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="e.g. How committed are you to learning?"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700">Type</label>
              <select v-model="step.type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="text">Text only (no choices)</option>
                <option value="buttons">Buttons (quick replies)</option>
                <option value="list">List (menu)</option>
              </select>
            </div>

            <template v-if="step.type !== 'text'">
              <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-slate-700">Options</label>
                <button type="button" class="text-sm text-angaza-accent hover:underline" @click="addOption(step)">+ Add option</button>
              </div>
              <div v-for="(opt, oi) in step.options" :key="oi" class="flex flex-wrap items-center gap-2 mt-1">
                <input v-model="opt.label" type="text" placeholder="Label (shown)" class="rounded border border-slate-300 px-2 py-1.5 text-sm flex-1 min-w-[100px]" />
                <input v-model="opt.value" type="text" placeholder="Value (internal)" class="rounded border border-slate-300 px-2 py-1.5 text-sm w-28 font-mono" />
                <select v-model="opt.goTo" class="rounded border border-slate-300 px-2 py-1.5 text-sm w-36">
                  <option value="next">Next step</option>
                  <option value="end">End survey</option>
                  <option v-for="j in stepTargets(index)" :key="j" :value="'step_' + (j + 1)">Step {{ j + 1 }}</option>
                </select>
                <button type="button" class="text-red-600 hover:underline text-sm" @click="step.options.splice(oi, 1)">✕</button>
              </div>
              <p v-if="step.type !== 'text' && (!step.options || !step.options.length)" class="text-xs text-amber-600 mt-1">Add at least one option for buttons/list.</p>
            </template>
          </div>

          <button type="button" class="w-full rounded-lg border-2 border-dashed border-slate-300 py-3 text-sm font-medium text-slate-600 hover:border-angaza-accent hover:text-angaza-dark" @click="addStep">
            + Add step
          </button>

          <div class="flex gap-2 pt-4">
            <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="wizardStep = 1">Back</button>
            <button type="submit" class="rounded-lg bg-angaza-dark px-4 py-2 text-sm font-medium text-white hover:bg-angaza-dark/90" :disabled="saving || !canCreate">
              {{ saving ? 'Creating…' : 'Create survey' }}
            </button>
          </div>
        </div>
      </form>

      <p v-if="wizardError" class="px-6 pb-4 text-sm text-red-600">{{ wizardError }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const emit = defineEmits(['close', 'created'])

const wizardStep = ref(1)
const flowName = ref('')
const flowDescription = ref('')
const steps = ref([
  { id: 1, message: '', type: 'buttons', options: [{ label: '', value: '', goTo: 'next' }] },
])
const saving = ref(false)
const wizardError = ref('')

let nextId = 2
function addStep() {
  steps.value.push({
    id: ++nextId,
    message: '',
    type: 'buttons',
    options: [{ label: '', value: '', goTo: 'next' }],
  })
}
function removeStep(index) {
  steps.value.splice(index, 1)
}
function addOption(step) {
  if (!step.options) step.options = []
  step.options.push({ label: '', value: '', goTo: 'next' })
}

/** Step indices we can jump to (for "Go to Step K") */
function stepTargets(currentIndex) {
  const n = steps.value.length
  const out = []
  for (let j = 0; j < n; j++) {
    if (j !== currentIndex) out.push(j)
  }
  return out
}

const canCreate = computed(() => {
  if (!flowName.value.trim() || !steps.value.length) return false
  for (const step of steps.value) {
    if (!step.message.trim()) return false
    if (step.type !== 'text' && (!step.options || step.options.length === 0)) return false
    if (step.type !== 'text') {
      for (const o of step.options) {
        if (!o.label.trim() || !o.value.trim()) return false
      }
    }
  }
  return true
})

function nextStep() {
  wizardStep.value = 2
  wizardError.value = ''
}

const props = defineProps({
  api: { type: Object, required: true },
})

async function createSurvey() {
  if (!canCreate.value || saving.value) return
  saving.value = true
  wizardError.value = ''
  try {
    const flowRes = await props.api.botFlowsCreate({
      name: flowName.value.trim(),
      description: flowDescription.value.trim() || null,
      show_in_router: true,
      display_order: 0,
      is_active: true,
    })
    const flow = flowRes.data
    const flowId = flow.id

    const nodeKeys = []
    const nodesCreated = []
    for (let i = 0; i < steps.value.length; i++) {
      const step = steps.value[i]
      const nodeKey = 'step_' + (i + 1)
      nodeKeys.push(nodeKey)
      const nodeRes = await props.api.botNodesCreate({
        flow_id: flowId,
        node_key: nodeKey,
        type: step.type,
        message: step.message.trim(),
        position_x: 0,
        position_y: i * 150,
        is_entry: i === 0,
        is_active: true,
      })
      nodesCreated.push(nodeRes.data)
    }
    const endNodeRes = await props.api.botNodesCreate({
      flow_id: flowId,
      node_key: 'end',
      type: 'text',
      message: 'Thank you for completing the survey.',
      position_x: 0,
      position_y: steps.value.length * 150,
      is_entry: false,
      is_active: true,
    })
    const endNode = endNodeRes.data
    nodesCreated.push(endNode)

    for (let i = 0; i < steps.value.length; i++) {
      const step = steps.value[i]
      if (step.type === 'text') continue
      const sourceNode = nodesCreated[i]
      for (let oi = 0; oi < (step.options || []).length; oi++) {
        const opt = step.options[oi]
        let targetNode
        if (opt.goTo === 'end') {
          targetNode = endNode
        } else if (opt.goTo === 'next') {
          targetNode = i + 1 < nodesCreated.length - 1 ? nodesCreated[i + 1] : endNode
        } else {
          const match = String(opt.goTo).match(/^step_(\d+)$/)
          const idx = match ? parseInt(match[1], 10) - 1 : i + 1
          targetNode = idx >= 0 && idx < nodesCreated.length - 1 ? nodesCreated[idx] : endNode
        }
        await props.api.botEdgesCreate({
          source_node_id: sourceNode.id,
          target_node_id: targetNode.id,
          option_label: opt.label.trim(),
          option_value: opt.value.trim(),
          order: oi,
        })
      }
    }

    await props.api.botFlowsUpdate(flowId, { entry_node_id: nodesCreated[0].id })
    emit('created', flow)
    emit('close')
  } catch (e) {
    wizardError.value = e?.message || 'Failed to create survey'
  } finally {
    saving.value = false
  }
}

watch(wizardStep, () => { wizardError.value = '' })
</script>
