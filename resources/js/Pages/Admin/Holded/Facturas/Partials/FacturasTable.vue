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
  { key: 'num', label: 'Nº Factura' },
  { key: 'contact_name', label: 'Contacto' },
  { key: 'date', label: 'Fecha' },
  { key: 'subtotal', label: 'Base', align: 'right' },
  { key: 'tax_amount', label: 'IVA', align: 'right' },
  { key: 'irpf_amount', label: 'IRPF', align: 'right' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const formatDate = (timestamp) => {
  if (!timestamp) return '-'
  return new Date(timestamp * 1000).toLocaleDateString('es-ES')
}

const getStatusLabel = (item) => {
  const pending = parseFloat(item.raw_data?.paymentsPending || 0)
  const paid = parseFloat(item.raw_data?.paymentsTotal || 0)

  if (pending === 0) return 'Pagada'
  if (paid === 0) return 'Pendiente'
  return 'Parcial'
}

const getStatusClass = (item) => {
  const label = getStatusLabel(item)
  switch (label) {
      case 'Pagada': return 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'
      case 'Pendiente': return 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
      case 'Parcial': return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'
      default: return 'bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-zinc-300'
  }
}
</script>

<template>
    <DataTable
        :columns="columns"
        :items="items"
        @row-click="(item) => emit('row-click', item)"
        class="cursor-pointer"
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

        <template #cell-subtotal="{ item }">
            <span class="whitespace-nowrap text-nowrap text-zinc-500 dark:text-zinc-400">
            {{ formatCurrency(item.subtotal || 0) }}
            </span>
        </template>

        <template #cell-tax_amount="{ item }">
            <span class="whitespace-nowrap text-nowrap text-zinc-500 dark:text-zinc-400">
            {{ formatCurrency(item.tax_amount || 0) }}
            </span>
        </template>

        <template #cell-irpf_amount="{ item }">
            <span class="whitespace-nowrap text-nowrap text-red-500/80 dark:text-red-400/80" v-if="item.irpf_amount > 0">
            -{{ formatCurrency(item.irpf_amount) }}
            </span>
            <span class="whitespace-nowrap text-nowrap text-zinc-300 dark:text-zinc-600" v-else>
            -
            </span>
        </template>

        <template #cell-total="{ item }">
            <span class="whitespace-nowrap text-nowrap font-medium text-emerald-600 dark:text-emerald-400">
            {{ formatCurrency(item.total || 0) }}
            </span>
        </template>

        <template #cell-status="{ item }">
            <span 
                class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="getStatusClass(item)"
            >
                {{ getStatusLabel(item) }}
            </span>
        </template>

        <template #cell-actions="{ item }">
            <div class="flex justify-end">
                <Link :href="route('admin.visor-pdf', { 
                    url: route('admin.holded.facturas.pdf', item.holded_id),
                    title: `Factura: ${item.raw_data?.docNumber || item.holded_id}`,
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
