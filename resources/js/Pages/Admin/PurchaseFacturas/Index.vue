<script setup>
import { ref, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import pickBy from 'lodash/pickBy'
import debounce from 'lodash/debounce'
import axios from 'axios'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import TextInput from '@/Components/TextInput.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import UploadFacturaModal from './Components/UploadFacturaModal.vue'
import EditFacturaModal from './Components/EditFacturaModal.vue'
import DeleteFacturaModal from './Components/DeleteFacturaModal.vue'
import OverwriteFacturaModal from './Components/OverwriteFacturaModal.vue'
import DataTable from '@/Components/DataTable.vue'
import { PlusIcon, ArrowUpTrayIcon, ArrowDownTrayIcon, EyeIcon, TrashIcon, ExclamationTriangleIcon, ChevronUpIcon, ChevronDownIcon, PencilSquareIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const columns = [
  { key: 'number', label: 'Número', sortable: true },
  { key: 'provider_name', label: 'Proveedor', sortable: true },
  { key: 'date', label: 'Fecha', sortable: true },
  { key: 'net_amount', label: 'Base', sortable: true, align: 'right' },
  { key: 'tax_amount', label: 'IVA', sortable: true, align: 'right' },
  { key: 'total', label: 'Total', sortable: true, align: 'right' },
  { key: 'status', label: 'Estado', sortable: true },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const props = defineProps({
    facturas: Object,
    providers: Array,
    filters: Object,
    totals: Object,
    statuses: Array
})

const filterForm = ref({
    search: props.filters.search || '',
    provider: props.filters.provider || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    sort: props.filters.sort || 'date',
    direction: props.filters.direction || 'desc'
})

import { computed } from 'vue'
const providersOptions = computed(() => [
    { id: '', name: 'Todos los proveedores' },
    ...props.providers.map(provider => ({ id: provider, name: provider }))
])

watch(
    filterForm,
    debounce(function () {
        router.get(
            route('admin.purchase-facturas.index'),
            pickBy(filterForm.value, (value) => value !== '' && value !== null && value !== undefined),
            { preserveState: true, preserveScroll: true, replace: true }
        )
    }, 500),
    { deep: true }
)

watch(() => props.filters, (newFilters) => {
    filterForm.value.search = newFilters.search || ''
    filterForm.value.provider = newFilters.provider || ''
    filterForm.value.date_from = newFilters.date_from || ''
    filterForm.value.date_to = newFilters.date_to || ''
    filterForm.value.sort = newFilters.sort || 'date'
    filterForm.value.direction = newFilters.direction || 'desc'
}, { deep: true })

const sort = (column) => {
    if (filterForm.value.sort === column) {
        filterForm.value.direction = filterForm.value.direction === 'asc' ? 'desc' : 'asc'
    } else {
        filterForm.value.sort = column
        filterForm.value.direction = 'asc'
    }
}

const resetFilters = () => {
    router.get(route('admin.purchase-facturas.index'))
}

const showEditModal = ref(false)
const facturaToEdit = ref(null)

const editFactura = (factura) => {
    facturaToEdit.value = factura
    showEditModal.value = true
}

const handleFacturaSaved = () => {
    showEditModal.value = false
    facturaToEdit.value = null
    router.reload({ only: ['facturas'] })
}

const showUploadModal = ref(false)

const handleFacturasUploaded = () => {
    showUploadModal.value = false
    router.reload({ only: ['facturas'] })
}

const confirmingFacturaDeletion = ref(false)
const facturaIdBeingDeleted = ref(null)

const confirmFacturaDeletion = (id) => {
    facturaIdBeingDeleted.value = id
    confirmingFacturaDeletion.value = true
}

const handleFacturaDeleted = () => {
    confirmingFacturaDeletion.value = false
    facturaIdBeingDeleted.value = null
}

const confirmingOverwrite = ref(false)
const facturaToOverwrite = ref(null)

const confirmOverwrite = (factura) => {
    facturaToOverwrite.value = factura
    confirmingOverwrite.value = true
}

const handleFacturaOverwritten = () => {
    confirmingOverwrite.value = false
    facturaToOverwrite.value = null
}

const openUploadModal = () => {
    showUploadModal.value = true
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('es-ES')
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(amount)
}

const viewPdf = (item) => {
    router.get(route('admin.visor-pdf'), {
        url: route('admin.purchase-facturas.pdf', item.id),
        title: `Factura: ${item.number}`,
        backUrl: window.location.href
    });
}

const updateStatusInline = (id, e) => {
    router.patch(route('admin.purchase-facturas.update-status', id), { status: e.target.value }, {
        preserveScroll: true
    })
}

const setDateRange = (rangeType) => {
    const today = new Date();
    if (rangeType === 'thisYear') {
        filterForm.value.date_from = new Date(today.getFullYear(), 0, 2).toISOString().split('T')[0];
        filterForm.value.date_to = new Date(today.getFullYear(), 11, 31).toISOString().split('T')[0];
    } else if (rangeType === 'lastYear') {
        filterForm.value.date_from = new Date(today.getFullYear() - 1, 0, 2).toISOString().split('T')[0];
        filterForm.value.date_to = new Date(today.getFullYear() - 1, 11, 31).toISOString().split('T')[0];
    } else if (rangeType === 'last12Months') {
        const lastYear = new Date();
        lastYear.setFullYear(today.getFullYear() - 1);
        filterForm.value.date_from = lastYear.toISOString().split('T')[0];
        filterForm.value.date_to = today.toISOString().split('T')[0];
    }
}
</script>

<template>
  <Head title="Facturas compras" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Facturas compras">
        <template #actions>
          <button 
            @click="openUploadModal"
            class="flex items-center gap-x-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors text-sm font-medium"
          >
            <ArrowUpTrayIcon class="h-4 w-4" />
            Subir factura
          </button>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
      <Card class="p-4 sm:p-6 overflow-hidden">
                <!-- Unified Filters & Totals -->
        <div class="mb-6 flex flex-col lg:flex-row gap-6 lg:items-start justify-between">
          
          <!-- Left Side: General Filters -->
          <div class="w-full lg:flex-1 flex flex-col sm:flex-row flex-wrap gap-4 items-end">
            <!-- Search -->
            <div class="space-y-1 w-full sm:min-w-[200px] sm:flex-[2]">
              <InputLabel for="search" value="Buscar (Nº o Proveedor)" />
              <SearchInput
                id="search"
                v-model="filterForm.search"
                placeholder="Ej: 5477... o Google"
              />
            </div>

            <!-- Provider Filter -->
            <div class="space-y-1 w-full sm:min-w-[250px] sm:flex-[3]">
              <InputLabel for="filter_provider" value="Proveedor" />
              <SearchableSelect 
                id="filter_provider"
                v-model="filterForm.provider"
                :options="providersOptions"
                placeholder="Todos los proveedores"
                class="w-full z-50"
              />
            </div>
            
            <!-- Clear Filters Button -->
            <div class="w-full sm:w-auto flex justify-start sm:justify-end mt-2 sm:mt-0">
                <button 
                    @click="resetFilters" 
                    title="Limpiar filtros"
                    class="h-[42px] px-3 flex items-center justify-center bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 hover:text-emerald-600 transition-colors rounded-lg border border-gray-200 dark:border-zinc-700"
                >
                    <ArrowPathIcon class="w-5 h-5" />
                </button>
            </div>
          </div>

          <!-- Right Side: Dates & Totals Column -->
          <div class="w-full lg:w-max flex flex-col gap-4">
            
            <!-- Dates -->
            <div class="flex flex-col gap-2 w-full">
              <div class="flex gap-3">
                <!-- Date From -->
                <div class="space-y-1 flex-1">
                  <InputLabel for="date_from" value="Desde" />
                  <TextInput 
                    id="date_from"
                    v-model="filterForm.date_from"
                    type="date"
                    class="w-full text-sm"
                  />
                </div>

                <!-- Date To -->
                <div class="space-y-1 flex-1">
                  <InputLabel for="date_to" value="Hasta" />
                  <TextInput 
                    id="date_to"
                    v-model="filterForm.date_to"
                    type="date"
                    class="w-full text-sm"
                  />
                </div>
              </div>
              
              <!-- Quick Date Filters -->
              <div class="flex items-center justify-between gap-2 mt-1">
                 <button @click="setDateRange('thisYear')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Este año</button>
                 <button @click="setDateRange('lastYear')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Año pasado</button>
                 <button @click="setDateRange('last12Months')" class="text-[11px] text-gray-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-500 transition-colors">Últimos 12 meses</button>
              </div>
            </div>

            <!-- Totals Card -->
            <Card class="p-3 bg-gray-50 dark:bg-zinc-800/50 border border-gray-200 dark:border-zinc-700 w-full">
              <div class="flex items-center gap-x-6 justify-between lg:justify-end">
                <div>
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">Base</div>
                  <div class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(totals?.net_amount || 0) }}</div>
                </div>
                <div>
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">IVA</div>
                  <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ formatCurrency(totals?.tax_amount || 0) }}</div>
                </div>
                <div class="pl-4 border-l border-gray-200 dark:border-zinc-700">
                  <div class="text-[10px] text-gray-500 dark:text-zinc-400 font-bold uppercase tracking-wider mb-0.5">Total</div>
                  <div class="text-base font-bold text-gray-900 dark:text-zinc-100">{{ formatCurrency(totals?.total || 0) }}</div>
                </div>
              </div>
            </Card>
          </div>
        </div>
        
        <DataTable
            :columns="columns"
            :items="facturas.data"
            :sort-key="filterForm.sort"
            :sort-dir="filterForm.direction"
            @sort="sort"
            @row-click="viewPdf"
            :hoverable="true"
        >
            <template #cell-date="{ item }">
                {{ formatDate(item.date) }}
            </template>
            <template #cell-net_amount="{ item }">
                <span class="font-medium text-emerald-600 dark:text-emerald-400 whitespace-nowrap">{{ formatCurrency(item.net_amount) }}</span>
            </template>
            <template #cell-tax_amount="{ item }">
                <span class="font-medium text-blue-600 dark:text-blue-400 whitespace-nowrap">{{ formatCurrency(item.tax_amount) }}</span>
            </template>
            <template #cell-total="{ item }">
                <span class="font-medium text-gray-900 dark:text-zinc-100 whitespace-nowrap">{{ formatCurrency(item.total) }}</span>
            </template>
            <template #cell-status="{ item }">
                <select 
                    :value="item.status"
                    @change="updateStatusInline(item.id, $event)"
                    @click.stop
                    class="text-xs font-semibold rounded-full border border-transparent shadow-sm focus:ring-2 focus:ring-offset-1 focus:ring-emerald-500 cursor-pointer py-1 pl-3 pr-8 capitalize"
                    :class="{
                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300': item.status === 'pagado',
                        'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300': item.status === 'recibida' || item.status === 'duplicada' || item.status === 'error',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300': item.status === 'procesando',
                        'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300': item.status === 'error_ia'
                    }"
                >
                    <option v-for="s in statuses" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
            </template>
            <template #cell-actions="{ item }">
                <div class="flex justify-end items-center gap-x-1.5">
                  <!-- Sustituir si está duplicada -->
                  <button 
                    v-if="item.status === 'duplicada'"
                    @click.stop="confirmOverwrite(item)"
                    class="inline-flex items-center px-2 py-1 text-amber-500/70 hover:text-amber-600 dark:text-amber-400/70 dark:hover:text-amber-400 transition-colors text-xs font-bold uppercase whitespace-nowrap"
                    :title="'Sobreescribir factura ' + (item.raw_data?.intended_number || item.number)"
                  >
                    <ExclamationTriangleIcon class="h-4 w-4 mr-1" />
                    <span>Sustituir #{{ item.raw_data?.intended_number || item.number.replace('DUP-', '') }}</span>
                  </button>

                  <!-- 1. Ver PDF -->
                  <div class="w-7 h-7 flex items-center justify-center">
                    <button 
                      v-if="item.google_drive_file_id"
                      @click.stop="viewPdf(item)"
                      class="text-emerald-500/70 hover:text-emerald-600 dark:text-emerald-400/70 dark:hover:text-emerald-400 transition-colors"
                      title="Ver PDF"
                    >
                      <EyeIcon class="h-5 w-5" />
                    </button>
                  </div>

                  <!-- 2. Descargar PDF -->
                  <div class="w-7 h-7 flex items-center justify-center">
                    <a 
                      v-if="item.google_drive_file_id"
                      :href="route('admin.purchase-facturas.pdf', { purchaseFactura: item.id, download: 1 })"
                      @click.stop
                      class="text-sky-500/70 hover:text-sky-600 dark:text-sky-400/70 dark:hover:text-sky-400 transition-colors"
                      title="Descargar PDF"
                    >
                      <ArrowDownTrayIcon class="h-5 w-5" />
                    </a>
                  </div>

                  <!-- 3. Editar -->
                  <div class="w-7 h-7 flex items-center justify-center">
                    <button 
                      @click.stop="editFactura(item)"
                      class="text-blue-500/70 hover:text-blue-600 dark:text-blue-400/70 dark:hover:text-blue-400 transition-colors"
                      title="Editar / Revisión Manual"
                    >
                      <PencilSquareIcon class="h-5 w-5" />
                    </button>
                  </div>

                  <!-- 4. Eliminar -->
                  <div class="w-7 h-7 flex items-center justify-center">
                    <button 
                      @click.stop="confirmFacturaDeletion(item.id)"
                      class="text-red-500/70 hover:text-red-600 dark:text-red-400/70 dark:hover:text-red-400 transition-colors"
                      title="Eliminar factura"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </div>
            </template>
        </DataTable>
        <!-- Pagination -->
        <div v-if="facturas.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-zinc-800">
          <Pagination :links="facturas.links" />
        </div>
      </Card>
    </div>

    <!-- Modal de Subida -->
    <UploadFacturaModal
      :show="showUploadModal"
      @close="showUploadModal = false"
      @uploaded="handleFacturasUploaded"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteFacturaModal
      :show="confirmingFacturaDeletion"
      :facturaId="facturaIdBeingDeleted"
      @close="confirmingFacturaDeletion = false"
      @deleted="handleFacturaDeleted"
    />

    <!-- Overwrite Confirmation Modal -->
    <OverwriteFacturaModal
      :show="confirmingOverwrite"
      :factura="facturaToOverwrite"
      @close="confirmingOverwrite = false"
      @overwritten="handleFacturaOverwritten"
    />

    <!-- Edit / Manual Review Modal -->
    <EditFacturaModal
      :show="showEditModal"
      :factura="facturaToEdit"
      @close="showEditModal = false"
      @saved="handleFacturaSaved"
    />
  </AuthenticatedLayout>
</template>
