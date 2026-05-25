<script setup>
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

defineProps({
  columns: {
    type: Array,
    required: true,
    // Format: [{ key: 'name', label: 'Nombre', sortable: true, align: 'left/right' }]
  },
  items: {
    type: Array,
    required: true,
  },
  hoverable: {
    type: Boolean,
    default: true,
  },
  sortKey: {
    type: String,
    default: ''
  },
  sortDir: {
    type: String,
    default: 'desc'
  },
  rowClass: {
    type: [Function, String, Array, Object],
    default: ''
  }
})

const emit = defineEmits(['row-click', 'sort'])

const handleSort = (key, sortable) => {
    if (sortable) emit('sort', key)
}
</script>

<template>
  <div class="rounded-xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
    
    <!-- Desktop View -->
    <div class="overflow-x-auto hidden md:block">
      <table class="w-full text-sm text-left border-collapse">
        <thead class="bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              @click="handleSort(col.key, col.sortable)"
              :class="[
                'px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 whitespace-nowrap transition-colors',
                col.sortable ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50' : '',
                col.align === 'right' ? 'text-right' : 'text-left',
                col.center === true ? '!text-center' : '',
                col.class || ''
              ]"
            >
              <div class="flex items-center gap-x-1" :class="col.align === 'right' ? 'justify-end' : (col.center ? 'justify-center' : '')">
                <slot :name="`header-${col.key}`" :column="col">
                  {{ col.label }}
                </slot>
                <template v-if="col.sortable">
                  <ChevronUpIcon v-if="sortKey === col.key && sortDir === 'asc'" class="h-4 w-4" />
                  <ChevronDownIcon v-if="sortKey === col.key && sortDir === 'desc'" class="h-4 w-4" />
                </template>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-700">
          <tr
            v-for="(item, index) in items"
            :key="item.id || index"
            @click="$emit('row-click', item)"
            :class="[
              'transition-colors duration-150',
              (typeof rowClass === 'function' ? rowClass(item) : rowClass) || (hoverable ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-900/50' : '')
            ]"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              :class="[
                'px-6 py-4 text-gray-900 dark:text-zinc-300',
                col.align === 'right' ? 'text-right' : 'text-left',
                col.class || ''
              ]"
            >
              <slot :name="`cell-${col.key}`" :item="item" :value="item[col.key]">
                {{ item[col.key] }}
              </slot>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td :colspan="columns.length" class="px-6 py-10 text-center text-sm text-gray-500 dark:text-zinc-400">
              No se encontraron registros.
            </td>
          </tr>
        </tbody>
        <tfoot v-if="$slots.footer" class="bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-200 dark:border-zinc-700">
          <slot name="footer" />
        </tfoot>
      </table>
    </div>

    <!-- Mobile View -->
    <div class="md:hidden flex flex-col divide-y divide-gray-200 dark:divide-zinc-700">
      <div
        v-for="(item, index) in items"
        :key="item.id || index"
        @click="$emit('row-click', item)"
        :class="[
          'p-4 transition-colors duration-150',
          (typeof rowClass === 'function' ? rowClass(item) : rowClass) || (hoverable ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-900/50' : '')
        ]"
      >
        <div class="flex flex-col gap-3">
          <div
            v-for="col in columns"
            :key="col.key"
            class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1"
          >
            <span v-if="col.key !== 'actions' && col.label !== 'Acciones'" class="text-xs font-semibold text-gray-500 dark:text-zinc-400">
              <slot :name="`header-${col.key}`" :column="col">
                {{ col.label }}
              </slot>
            </span>
            <div class="text-sm text-gray-900 dark:text-zinc-300 w-full sm:w-auto break-words" :class="col.align === 'right' ? 'sm:text-right' : 'sm:text-left'">
              <slot :name="`cell-${col.key}`" :item="item" :value="item[col.key]">
                {{ item[col.key] }}
              </slot>
            </div>
          </div>
        </div>
      </div>
      <div v-if="items.length === 0" class="p-6 text-center text-sm text-gray-500 dark:text-zinc-400">
        No se encontraron registros.
      </div>
      <div v-if="$slots.footer" class="bg-gray-50 dark:bg-zinc-800/50 border-t border-gray-200 dark:border-zinc-700">
        <slot name="footer" />
      </div>
    </div>

  </div>
</template>
