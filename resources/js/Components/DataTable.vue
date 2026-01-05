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
  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
      <thead class="bg-gray-50 dark:bg-zinc-900/50">
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-4 py-3 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-zinc-400 whitespace-nowrap',
              col.align === 'right' ? 'text-right' : 'text-left'
            ]"
          >
            {{ col.label }}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900/50">
        <tr
          v-for="(item, index) in items"
          :key="item.id || index"
          @click="$emit('row-click', item)"
          :class="[
            'transition-colors duration-150',
            hoverable ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700/50' : ''
          ]"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-4 py-3 text-sm text-gray-900 dark:text-zinc-300',
              col.align === 'right' ? 'text-right' : 'text-left'
            ]"
          >
            <slot :name="`cell-${col.key}`" :item="item" :value="item[col.key]">
              {{ item[col.key] }}
            </slot>
          </td>
        </tr>
        <tr v-if="items.length === 0">
          <td :colspan="columns.length" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-zinc-400">
            No se encontraron registros.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
