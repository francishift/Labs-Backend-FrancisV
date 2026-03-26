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
  }
})

const emit = defineEmits(['row-click', 'sort'])

const handleSort = (key, sortable) => {
    if (sortable) emit('sort', key)
}
</script>

<template>
  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
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
              col.class || ''
            ]"
          >
            <div class="flex items-center gap-x-1" :class="col.align === 'right' ? 'justify-end' : ''">
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
      <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
        <tr
          v-for="(item, index) in items"
          :key="item.id || index"
          @click="$emit('row-click', item)"
          :class="[
            'transition-colors duration-150',
            hoverable ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/30' : ''
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
</template>
