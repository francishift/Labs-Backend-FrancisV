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
  presupuestos: Object,
  filters: Object,
  errorMessage: String,
})

const { formatDate: baseFormatDate } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.holded.presupuestos.index', {
    start: props.filters.start,
    end: props.filters.end
})

const filters = reactive({
  start: props.filters.start,
  end: props.filters.end,
})

// Columnas para la tabla de presupuestos de Holded
const columns = [
  { key: 'num', label: 'Nº Presupuesto' },
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

const updateResults = debounce(() => {
  router.get(route('admin.holded.presupuestos.index'), {
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
        url: route('admin.holded.presupuestos.pdf', item.holded_id),
        title: `Presupuesto: ${item.raw_data?.docNumber || item.holded_id}`,
        backUrl: currentUrl
    });
}
</script>

<template>
  <Head title="Presupuestos" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Presupuestos" />
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
              <InputLabel for="search-budgets" value="Buscar" />
              <SearchInput 
                id="search-budgets"
                name="search"
                placeholder="Buscar por contacto o nº de presupuesto..."
                class="mt-1 block w-full"
                v-model="search"
              />
            </div>
          </div>
        </div>
        
        <DataTable
          :columns="columns"
          :items="presupuestos.data"
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
              :class="{
                'bg-gray-100 text-gray-800': item.status == 0,
                'bg-blue-100 text-blue-800': item.status == 1,
                'bg-green-100 text-green-800': item.status == 2,
                'bg-red-100 text-red-800': item.status == 3,
                'bg-purple-100 text-purple-800': item.status == 4,
              }"
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

        <Pagination :links="presupuestos.links" class="mt-6" />

        <div v-if="presupuestos.data.length === 0 && !errorMessage" class="text-center py-8 text-gray-500">
          No se encontraron presupuestos en el rango seleccionado.
        </div>
      </Card>
    </div>
  </AuthenticatedLayout>
</template>
