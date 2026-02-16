<script setup>
import DataTable from '@/Components/DataTable.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { EyeIcon } from '@heroicons/vue/24/outline'
import { Link } from '@inertiajs/vue3'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
    items: Array,
    currentUrl: String
})

const emit = defineEmits(['row-click'])

const { formatCurrency } = useFormatters()

const columns = [
  { key: 'num', label: 'Nº Presupuesto' },
  { key: 'contact_name', label: 'Contacto' },
  { key: 'date', label: 'Fecha' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const formatDate = (timestamp) => {
  if (!timestamp) return '-'
  return new Date(timestamp * 1000).toLocaleDateString('es-ES')
}

const getStatusLabel = (status) => {
  const labels = {
    0: 'Borrador',
    1: 'Enviado',
    2: 'Aceptado',
    3: 'Rechazado',
    4: 'Facturado',
  }
  return labels[status] || 'Desconocido'
}

const getStatusClass = (status) => {
  switch (parseInt(status)) {
    case 0: return 'bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-zinc-300'
    case 1: return 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300'
    case 2: return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'
    case 3: return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
    case 4: return 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300'
    default: return 'bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-zinc-300'
  }
}
</script>

<template>
    <DataTable
        :columns="columns"
        :items="items"
        @row-click="(item) => emit('row-click', item)"
        hoverable
    >
        <template #cell-num="{ item }">
            <span class="font-medium text-zinc-900 dark:text-white">
                {{ item.raw_data?.docNumber || item.holded_id }}
            </span>
        </template>

        <template #cell-contact_name="{ item }">
            {{ item.contact_name }}
        </template>
        
        <template #cell-date="{ item }">
            {{ formatDate(item.date) }}
        </template>

        <template #cell-total="{ item }">
            <span class="whitespace-nowrap text-nowrap">
            {{ formatCurrency(item.total) }}
            </span>
        </template>

        <template #cell-status="{ item }">
            <span 
                class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="getStatusClass(item.status)"
            >
                {{ getStatusLabel(item.status) }}
            </span>
        </template>

        <template #cell-actions="{ item }">
            <div class="flex justify-end">
                <Link :href="route('admin.visor-pdf', { 
                    url: route('admin.holded.presupuestos.pdf', item.holded_id),
                    title: `Presupuesto: ${item.raw_data?.docNumber || item.holded_id}`,
                    backUrl: currentUrl
                })" @click.stop>
                    <SecondaryButton title="Ver PDF">
                        <EyeIcon class="h-4 w-4" />
                    </SecondaryButton>
                </Link>
            </div>
        </template>
    </DataTable>
</template>
