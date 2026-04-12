<script setup>
import { ref, reactive } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Pagination from '@/Components/Pagination.vue'
import { CloudArrowUpIcon } from '@heroicons/vue/24/outline'
import debounce from 'lodash/debounce'

// Partials
import FacturasFilters from './Partials/FacturasFilters.vue'
import FacturasTable from './Partials/FacturasTable.vue'

const props = defineProps({
  facturas: Object,
  filters: Object,
  totals: Object,
  clients: Array,
  errorMessage: String,
})

const search = ref(props.filters.search || '')
const filters = reactive({
  start: props.filters.start,
  end: props.filters.end,
  status: props.filters.status,
  client: props.filters.client || '',
  sort: props.filters.sort || 'date',
  direction: props.filters.direction || 'desc',
})

import { watch } from 'vue'
watch(() => props.filters, (newFilters) => {
    filters.start = newFilters.start
    filters.end = newFilters.end
    filters.status = newFilters.status
    filters.client = newFilters.client || ''
    filters.sort = newFilters.sort || 'date'
    filters.direction = newFilters.direction || 'desc'
    search.value = newFilters.search || ''
}, { deep: true })

const updateResults = debounce(() => {
  router.get(route('admin.holded.facturas.index'), {
    ...filters,
    search: search.value
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  })
}, 300)

const onFilterChange = () => {
    updateResults()
}

const onSort = (column) => {
    if (filters.sort === column) {
        filters.direction = filters.direction === 'asc' ? 'desc' : 'asc'
    } else {
        filters.sort = column
        filters.direction = 'asc'
    }
    updateResults()
}

const resetFilters = () => {
    router.get(route('admin.holded.facturas.index'))
}

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

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(amount)
}
</script>

<template>
  <Head title="Facturas ventas" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 sm:gap-0">
        <PageHeader title="Facturas ventas" />
        <SecondaryButton 
          @click="syncDrive" 
          :disabled="isSyncing"
          class="flex items-center gap-2"
        >
          <svg v-if="isSyncing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <CloudArrowUpIcon v-else class="w-5 h-5 mr-1" />
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

        <!-- Filters Partial -->
        <FacturasFilters 
            :model-value="filters"
            @update:model-value="Object.assign(filters, $event)"
            v-model:search="search"
            :clients="clients"
            :totals="totals"
            @change="onFilterChange"
            @reset="resetFilters"
        />

        <!-- Table Partial -->
        <FacturasTable 
            :items="facturas.data"
            :current-url="currentUrl"
            :sort-key="filters.sort"
            :sort-dir="filters.direction"
            @sort="onSort"
            @row-click="viewPdf"
        />

        <Pagination :links="facturas.links" class="mt-6" />

        <div v-if="facturas.data.length === 0 && !errorMessage" class="text-center py-8 text-gray-500 italic">
          No se encontraron facturas en el rango seleccionado.
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
