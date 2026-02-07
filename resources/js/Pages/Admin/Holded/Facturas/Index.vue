<script setup>
import { ref, watch, reactive } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import DataTable from '@/Components/DataTable.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import debounce from 'lodash/debounce'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import { EyeIcon } from '@heroicons/vue/24/outline'
import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useFormatters } from '@/Composables/useFormatters'

const props = defineProps({
  facturas: Object,
  filters: Object,
  errorMessage: String,
})

const { formatDate: baseFormatDate } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.holded.facturas.index', {
    start: props.filters.start,
    end: props.filters.end
})

const filters = reactive({
  start: props.filters.start,
  end: props.filters.end,
})

// Columnas para la tabla de facturas de Holded
const columns = [
  { key: 'num', label: 'Nº Factura' },
  { key: 'contact_name', label: 'Contacto' },
  { key: 'date', label: 'Fecha' },
  { key: 'total', label: 'Total', align: 'right' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value)
}

const formatDate = (timestamp) => {
  if (!timestamp) return '-'
  return new Date(timestamp * 1000).toLocaleDateString('es-ES')
}

// Holded Invoice Statuses
const getStatusLabel = (status) => {
  const labels = {
    0: 'Pagada',
    1: 'Pagada', // Sometimes 1 is also paid
    2: 'Parcial',
    3: 'Vencida',
    4: 'Anulada',
    // Fallbacks based on common API values, verify if possible
  }
  // If status is string or different, handle it?
  // For now assuming these integers. 
  // Holded API Docs (Invoicing v1): Status 0=Pending, 1=Paid, 2=Partial?
  // Let's stick to raw status or a generic map.
  return labels[status] || `Estado ${status}`
}

const getStatusClass = (status) => {
    switch (parseInt(status)) {
        case 1: return 'bg-green-100 text-green-800'; // Paid
        case 0: return 'bg-gray-100 text-gray-800'; // Pending
        case 2: return 'bg-yellow-100 text-yellow-800'; // Partial
        case 3: return 'bg-red-100 text-red-800'; // Overdue
        default: return 'bg-gray-100 text-gray-800';
    }
}

const updateResults = debounce(() => {
  router.get(route('admin.holded.facturas.index'), {
    start: filters.start,
    end: filters.end,
    search: search.value
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  })
}, 300)

watch(filters, () => {
  updateResults()
})

const currentUrl = typeof window !== 'undefined' ? window.location.href : ''

const viewPdf = (item) => {
    router.get(route('admin.visor-pdf'), {
        url: route('admin.holded.facturas.pdf', item.holded_id),
        title: `Factura: ${item.raw_data?.docNumber || item.holded_id}`,
        backUrl: currentUrl
    });
}
const isSyncing = ref(false)

const syncDrive = () => {
    isSyncing.value = true
    router.post(route('admin.holded.facturas.sync-drive'), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSyncing.value = false
        }
    })
}
</script>

<template>
  <Head title="Facturas de Holded" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <PageHeader title="Facturas de Holded" />
        <SecondaryButton 
          @click="syncDrive" 
          :disabled="isSyncing"
          class="flex items-center gap-2"
        >
          <svg v-if="isSyncing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="w-5 h-5 mr-1" viewBox="0 0 87.3 78" xmlns="http://www.w3.org/2000/svg"><path d="m6.6 66.85 3.85 6.65c.8 1.4 1.9 2.5 3.3 3.3l3.95-6.8H8.75c-.95 0-1.75-.35-2.15-1.05v-.05-.05-.05c-.3-.65-.45-1.4-.45-2.2V63l.45 3.85ZM23.25 40.15l-3.65-6.35-3.6-6.3-3.7-6.5-5.65 9.8c-.8 1.4-1.2 3-1.2 4.7v5.55l3.55 6.15 3.65 6.3 3.65 6.35.5 .9 1.15 2 .2.35.2.35 15.15-26.25-10.25 3zM22.5.5l-5.6 9.75 3.65 6.35 3.65 6.3 3.65 6.35 10.25-17.75.2-.35H55.8c.6-1.55.3-3.3-.85-4.65l-1.95-3.4L49.15.65h-.05c-.65-.4-1.45-.6-2.25-.6H22.55zm22.4 39.65-3.65 6.35-3.65 6.3-3.65 6.35-.45.85-8.4 14.55h32.1c1.6 0 3.15-.85 3.95-2.25l3.95-6.85 1.95-3.4 1.95-3.35H55.15l-10.25-17.75v-.05-.05-.05h-.05l.05-.05z" fill="currentColor"/></svg>
          <span v-if="isSyncing">Sincronizando...</span>
          <span v-else>Sincronizar Drive</span>
        </SecondaryButton>
      </div>
    </template>

    <div class="py-6 space-y-6">
      <Card class="p-4 sm:p-6">
        <!-- Error message -->
        <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-200">
          <p class="font-bold">Error de sincronización</p>
          <p>{{ errorMessage }}</p>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
          <div>
            <p class="text-sm text-gray-500 dark:text-zinc-400">Listado filtrado por fecha</p>
          </div>
          
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="w-full sm:w-40">
              <InputLabel for="start_date" value="Fecha inicio" />
              <TextInput
                id="start_date"
                type="date"
                class="mt-1 block w-full"
                v-model="filters.start"
              />
            </div>
            <div class="w-full sm:w-40">
              <InputLabel for="end_date" value="Fecha fin" />
              <TextInput
                id="end_date"
                type="date"
                class="mt-1 block w-full"
                v-model="filters.end"
              />
            </div>
            <div class="w-full sm:w-64">
              <InputLabel for="search-facturas" value="Buscar" />
              <SearchInput 
                id="search-facturas"
                name="search"
                placeholder="Buscar por contacto o nº..."
                class="mt-1 block w-full"
                v-model="search"
              />
            </div>
          </div>
        </div>
        
        <DataTable
          :columns="columns"
          :items="facturas.data"
          @row-click="viewPdf"
          class="cursor-pointer"
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
            {{ formatCurrency(item.total) }}
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

        <Pagination :links="facturas.links" class="mt-6" />

        <div v-if="facturas.data.length === 0 && !errorMessage" class="text-center py-8 text-gray-500">
          No se encontraron facturas en el rango seleccionado.
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
