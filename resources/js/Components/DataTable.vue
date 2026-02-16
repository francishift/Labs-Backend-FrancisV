<script setup>
defineProps({
  columns: {
    type: Array,
    required: true,
    // Expected format: [{ key: 'name', label: 'Nombre', sortable: true, align: 'left/right' }]
  },
  items: {
    type: Array,
    required: true,
  },
  hoverable: {
    type: Boolean,
    default: true,
  }
})

defineEmits(['row-click'])
</script>

<template>
  <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-800">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
      <thead class="bg-gray-50 dark:bg-zinc-900/50">
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 whitespace-nowrap',
              col.align === 'right' ? 'text-right' : 'text-left',
              col.class || ''
            ]"
          >
            <slot :name="`header-${col.key}`" :column="col">
              {{ col.label }}
            </slot>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 bg-white dark:bg-zinc-900/50">
        <tr
          v-for="(item, index) in items"
          :key="item.id || index"
          @click="$emit('row-click', item)"
          :class="[
            'transition-colors duration-150',
            hoverable ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/50' : ''
          ]"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-6 py-4 text-sm text-gray-900 dark:text-zinc-300',
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
      <tfoot v-if="$slots.footer" class="bg-gray-50 dark:bg-zinc-900/50 border-t border-gray-200 dark:border-zinc-800">
        <slot name="footer" />
      </tfoot>
    </table>
  </div>
</template>
