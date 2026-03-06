<script setup>
import { ref, reactive } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Pagination from '@/Components/Pagination.vue'
import debounce from 'lodash/debounce'

// Partials
import PresupuestosFilters from './Partials/PresupuestosFilters.vue'
import PresupuestosTable from './Partials/PresupuestosTable.vue'

const props = defineProps({
  presupuestos: Object,
  filters: Object,
  errorMessage: String,
})

const search = ref(props.filters.search || '')
const filters = reactive({
  start: props.filters.start,
  end: props.filters.end,
  quickFilter: props.filters.quickFilter || '',
})

import { watch } from 'vue'
watch(() => props.filters, (newFilters) => {
    filters.start = newFilters.start
    filters.end = newFilters.end
    filters.quickFilter = newFilters.quickFilter || ''
    search.value = newFilters.search || ''
}, { deep: true })

const updateResults = debounce(() => {
  router.get(route('admin.holded.presupuestos.index'), {
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

const resetFilters = () => {
    router.get(route('admin.holded.presupuestos.index'))
}

const currentUrl = typeof window !== 'undefined' ? window.location.href : ''

const viewPdf = (item) => {
    router.get(route('admin.visor-pdf'), {
        url: route('admin.holded.presupuestos.pdf', item.holded_id),
        title: `Presupuesto: ${item.raw_data?.docNumber || item.holded_id}`,
        backUrl: currentUrl
    });
}
</script>

<template>
  <Head title="Presupuestos de Holded" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Presupuestos de Holded" />
    </template>

    <div class="py-6 space-y-6">
      <Card class="p-4 sm:p-6">
        <!-- Error message -->
        <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-200">
          <p class="font-bold">Error de sincronización</p>
          <p>{{ errorMessage }}</p>
        </div>

        <!-- Filters Partial -->
        <PresupuestosFilters 
            :model-value="filters"
            @update:model-value="Object.assign(filters, $event)"
            v-model:search="search"
            @change="onFilterChange"
            @reset="resetFilters"
        />
        
        <!-- Table Partial -->
        <PresupuestosTable 
            :items="presupuestos.data"
            :current-url="currentUrl"
            @row-click="viewPdf"
        />

        <Pagination :links="presupuestos.links" class="mt-6" />

        <div v-if="presupuestos.data.length === 0 && !errorMessage" class="text-center py-8 text-gray-500 italic">
          No se encontraron presupuestos en el rango seleccionado.
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
