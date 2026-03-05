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
import { PlusIcon, ArrowUpTrayIcon, EyeIcon, TrashIcon, ExclamationTriangleIcon, ChevronUpIcon, ChevronDownIcon, PencilSquareIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    facturas: Object,
    providers: Array,
    filters: Object
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
            pickBy(filterForm.value),
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <!-- Search -->
          <div class="space-y-1">
            <InputLabel for="search" value="Buscar (Nº o Proveedor)" />
            <SearchInput
              id="search"
              v-model="filterForm.search"
              placeholder="Ej: 5477... o Google"
            />
          </div>

          <!-- Provider Filter -->
          <div class="space-y-1">
            <InputLabel for="filter_provider" value="Proveedor" />
            <SearchableSelect 
              id="filter_provider"
              v-model="filterForm.provider"
              :options="providersOptions"
              placeholder="Todos los proveedores"
              class="w-full z-50"
            />
          </div>

          <!-- Date From -->
          <div class="space-y-1">
            <InputLabel for="date_from" value="Desde" />
            <TextInput 
              id="date_from"
              v-model="filterForm.date_from"
              type="date"
              class="w-full"
            />
          </div>

          <!-- Date To -->
          <div class="space-y-1">
            <div class="flex justify-between items-center">
                <InputLabel for="date_to" value="Hasta" />
                <button @click="resetFilters" class="text-xs text-gray-400 hover:text-emerald-500 transition-colors">Limpiar todos</button>
            </div>
            <TextInput 
              id="date_to"
              v-model="filterForm.date_to"
              type="date"
              class="w-full"
            />
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 dark:bg-zinc-900/50 border-b border-gray-200 dark:border-zinc-800">
                <th @click="sort('number')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    Número
                    <ChevronUpIcon v-if="filterForm.sort === 'number' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'number' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th @click="sort('provider_name')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    Proveedor
                    <ChevronUpIcon v-if="filterForm.sort === 'provider_name' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'provider_name' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th @click="sort('date')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    Fecha
                    <ChevronUpIcon v-if="filterForm.sort === 'date' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'date' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th @click="sort('net_amount')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    Base
                    <ChevronUpIcon v-if="filterForm.sort === 'net_amount' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'net_amount' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th @click="sort('tax_amount')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    IVA
                    <ChevronUpIcon v-if="filterForm.sort === 'tax_amount' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'tax_amount' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th @click="sort('total')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    Total
                    <ChevronUpIcon v-if="filterForm.sort === 'total' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'total' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th @click="sort('status')" class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition-colors">
                  <div class="flex items-center gap-x-1">
                    Estado
                    <ChevronUpIcon v-if="filterForm.sort === 'status' && filterForm.direction === 'asc'" class="h-4 w-4" />
                    <ChevronDownIcon v-if="filterForm.sort === 'status' && filterForm.direction === 'desc'" class="h-4 w-4" />
                  </div>
                </th>
                <th class="px-6 py-4 font-semibold text-gray-700 dark:text-zinc-300 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-800">
              <tr v-for="factura in facturas.data" :key="factura.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
                <td class="px-6 py-4 text-gray-900 dark:text-zinc-300">{{ factura.number }}</td>
                <td class="px-6 py-4 text-gray-900 dark:text-zinc-300">{{ factura.provider_name }}</td>
                <td class="px-6 py-4 text-gray-900 dark:text-zinc-300">{{ formatDate(factura.date) }}</td>
                <td class="px-6 py-4 text-emerald-600 dark:text-emerald-400 font-medium whitespace-nowrap">{{ formatCurrency(factura.net_amount) }}</td>
                <td class="px-6 py-4 text-blue-600 dark:text-blue-400 font-medium whitespace-nowrap">{{ formatCurrency(factura.tax_amount) }}</td>
                <td class="px-6 py-4 text-gray-900 dark:text-zinc-100 font-medium whitespace-nowrap">{{ formatCurrency(factura.total) }}</td>
                <td class="px-6 py-4">
                  <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize border border-transparent dark:border-zinc-800/50" 
                    :class="{
                      'bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-500': factura.status === 'pagado' || factura.status === 'recibida',
                      'bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-500': factura.status === 'procesando',
                      'bg-red-100 dark:bg-red-500/10 text-red-700 dark:text-red-500': factura.status === 'duplicada',
                      'bg-purple-100 dark:bg-purple-500/10 text-purple-700 dark:text-purple-500': factura.status === 'error_ia'
                    }">
                    {{ factura.status === 'error_ia' ? 'Error IA' : factura.status }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right flex justify-end gap-x-2">
                  <button 
                    v-if="factura.google_drive_file_id"
                    @click="viewPdf(factura)"
                    class="inline-flex items-center p-2 text-gray-400 dark:text-zinc-400 hover:text-emerald-500 transition-colors"
                    title="Ver PDF"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </button>
                  <button 
                    v-if="factura.status === 'duplicada'"
                    @click="confirmOverwrite(factura)"
                    class="inline-flex items-center p-2 text-amber-600 dark:text-amber-500 hover:text-amber-400 transition-colors"
                    :title="'Sobreescribir factura ' + (factura.raw_data?.intended_number || factura.number)"
                  >
                    <ExclamationTriangleIcon class="h-5 w-5" />
                    <span class="ml-1 text-xs font-bold uppercase whitespace-nowrap">Sustituir #{{ factura.raw_data?.intended_number || factura.number.replace('DUP-', '') }}</span>
                  </button>
                  <button 
                    @click="editFactura(factura)"
                    class="inline-flex items-center p-2 text-gray-400 dark:text-zinc-400 hover:text-blue-500 transition-colors"
                    title="Editar / Revisión Manual"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </button>
                  <button 
                    @click="confirmFacturaDeletion(factura.id)"
                    class="inline-flex items-center p-2 text-gray-400 dark:text-zinc-400 hover:text-red-500 transition-colors"
                    title="Eliminar factura"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </td>
              </tr>
              <tr v-if="facturas.data.length === 0">
                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-zinc-500 italic">
                  No hay facturas de compra registradas.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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
