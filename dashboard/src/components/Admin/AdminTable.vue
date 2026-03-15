<template>
  <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
    <template v-if="empty">
      <div class="px-4 py-12 text-center text-slate-500">
        <slot name="empty">No data.</slot>
      </div>
    </template>
    <template v-else>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-angaza-dark">
            <tr>
              <th
                v-for="(col, i) in columns"
                :key="i"
                class="whitespace-nowrap px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-white"
                :class="col.class"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white">
            <slot name="body" />
          </tbody>
        </table>
      </div>
      <div
        v-if="pagination"
        class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <p class="text-sm text-slate-600">
          Showing {{ from }}–{{ to }} of {{ pagination.total }}
        </p>
        <div class="flex items-center gap-2">
          <button
            :disabled="pagination.current_page <= 1"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="$emit('page', pagination.current_page - 1)"
          >
            Previous
          </button>
          <span class="text-sm text-slate-600">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            @click="$emit('page', pagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  columns: { type: Array, required: true },
  items: { type: Array, default: () => [] },
  pagination: { type: Object, default: null },
})

defineEmits(['page'])

const empty = computed(() => !props.items || props.items.length === 0)

const from = computed(() => {
  if (!props.pagination || props.items.length === 0) return 0
  return (props.pagination.current_page - 1) * props.pagination.per_page + 1
})

const to = computed(() => {
  if (!props.pagination) return 0
  return Math.min(props.pagination.current_page * props.pagination.per_page, props.pagination.total)
})
</script>
