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
import { PlusIcon, ArrowUpTrayIcon, DocumentTextIcon, EyeIcon, XMarkIcon, TrashIcon, ExclamationTriangleIcon, ChevronUpIcon, ChevronDownIcon, PencilSquareIcon } from '@heroicons/vue/24/outline'

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
const editForm = useForm({
    id: null,
    number: '',
    provider_name: '',
    date: '',
    total: 0,
    net_amount: 0,
    tax_amount: 0,
    status: '',
    notes: ''
})

const editFactura = (factura) => {
    editForm.id = factura.id
    editForm.number = factura.number
    editForm.provider_name = factura.provider_name
    editForm.date = factura.date ? new Date(factura.date).toISOString().split('T')[0] : ''
    editForm.total = factura.total
    editForm.net_amount = factura.net_amount
    editForm.tax_amount = factura.tax_amount
    editForm.status = factura.status
    editForm.notes = factura.notes || ''
    showEditModal.value = true
}

const submitEdit = () => {
    editForm.put(route('admin.purchase-facturas.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false
            editForm.reset()
        }
    })
}

const showUploadModal = ref(false)
const fileInput = ref(null)
const isDragging = ref(false)

const selectedFiles = ref([])
const form = useForm({
    // cualquier otro campo si es necesario
})

const confirmingFacturaDeletion = ref(false)
const facturaIdBeingDeleted = ref(null)
const deleteForm = useForm({})

const confirmFacturaDeletion = (id) => {
    facturaIdBeingDeleted.value = id
    confirmingFacturaDeletion.value = true
}

const confirmingOverwrite = ref(false)
const facturaToOverwrite = ref(null)
const overwriteForm = useForm({})

const confirmOverwrite = (factura) => {
    facturaToOverwrite.value = factura
    confirmingOverwrite.value = true
}

const submitOverwrite = () => {
    overwriteForm.post(route('admin.purchase-facturas.overwrite', facturaToOverwrite.value.id), {
        onSuccess: () => {
            confirmingOverwrite.value = false
            facturaToOverwrite.value = null
        }
    })
}

const deleteFactura = () => {
    deleteForm.delete(route('admin.purchase-facturas.destroy', facturaIdBeingDeleted.value), {
        onSuccess: () => {
             confirmingFacturaDeletion.value = false
             facturaIdBeingDeleted.value = null
        },
        onError: () => {
             confirmingFacturaDeletion.value = false
             facturaIdBeingDeleted.value = null
        }
    })
}

const openUploadModal = () => {
    showUploadModal.value = true
}

const closeUploadModal = () => {
    showUploadModal.value = false
    selectedFiles.value = []
    form.clearErrors()
}

const handleFileSelect = (e) => {
    const files = Array.from(e.target.files).filter(file => file.type === 'application/pdf')
    selectedFiles.value = [...selectedFiles.value, ...files]
}

const handleDrop = (e) => {
    isDragging.value = false
    const droppedFiles = Array.from(e.dataTransfer.files).filter(file => file.type === 'application/pdf')
    selectedFiles.value = [...selectedFiles.value, ...droppedFiles]
}

const removeFile = (index) => {
    selectedFiles.value.splice(index, 1)
}

const uploadProgress = ref([])
const totalFiles = ref(0)
const processedFiles = ref(0)
const isUploading = ref(false)

const submitUpload = async () => {
    if (selectedFiles.value.length === 0) return
    
    isUploading.value = true
    totalFiles.value = selectedFiles.value.length
    processedFiles.value = 0
    uploadProgress.value = selectedFiles.value.map(f => ({ name: f.name, status: 'pending', percentage: 0 }))

    for (let i = 0; i < selectedFiles.value.length; i++) {
        const file = selectedFiles.value[i]
        uploadProgress.value[i].status = 'uploading'
        
        const formData = new FormData()
        formData.append('file', file)

        try {
            const response = await axios.post(route('admin.purchase-facturas.store'), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                },
                onUploadProgress: (progressEvent) => {
                    const percentage = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                    uploadProgress.value[i].percentage = percentage
                }
            })

            if (response.data.success) {
                uploadProgress.value[i].status = 'done'
                uploadProgress.value[i].percentage = 100
                processedFiles.value++
            } else {
                uploadProgress.value[i].status = 'error'
                uploadProgress.value[i].message = response.data.message || 'Error desconocido'
            }
        } catch (error) {
            console.error('Upload error:', error)
            uploadProgress.value[i].status = 'error'
            uploadProgress.value[i].message = error.response?.data?.message || 'Error de servidor'
        }
    }

    isUploading.value = false
    selectedFiles.value = [] // Limpiar lista al finalizar
    router.reload({ only: ['facturas'] })
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
    <DialogModal :show="showUploadModal" @close="closeUploadModal">
      <template #title>
        Subir factura de compra
      </template>

      <template #content>
        <div class="mt-4">
          <p class="text-sm text-gray-500 dark:text-zinc-400 mb-4">
            Arrastra una o varias facturas en formato PDF para que el sistema las procese y las guarde en Google Drive.
          </p>
          
          <div class="mt-4 space-y-4">
            <div>
              <InputLabel for="file" value="Facturas (PDF)" />
              <div 
                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                :class="[
                  isDragging ? 'border-emerald-500 bg-emerald-500/5' : 'border-gray-300 dark:border-zinc-700 hover:border-emerald-500',
                  form.errors.files ? 'border-red-500' : ''
                ]"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <div class="space-y-1 text-center">
                  <DocumentTextIcon class="mx-auto h-12 w-12" :class="isDragging ? 'text-emerald-500' : 'text-gray-400 dark:text-zinc-500'" />
                  <div class="flex text-sm text-gray-500 dark:text-zinc-400">
                    <span class="relative bg-transparent rounded-md font-medium text-emerald-600 dark:text-emerald-500 hover:text-emerald-500 dark:hover:text-emerald-400 focus-within:outline-none">
                      Selecciona archivos o arrástralos aquí
                    </span>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-zinc-500">Solo archivos PDF hasta 10MB c/u</p>
                </div>
              </div>
              <input 
                ref="fileInput"
                type="file"
                class="hidden"
                accept="application/pdf"
                multiple
                @change="handleFileSelect"
              />
              <InputError :message="form.errors.files" class="mt-2" />
            </div>

            <!-- Lista de archivos seleccionados -->
            <div v-if="selectedFiles.length > 0" class="mt-4 border border-gray-200 dark:border-zinc-800 rounded-lg overflow-hidden">
              <div class="bg-gray-50 dark:bg-zinc-800/50 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-zinc-400 uppercase tracking-wider flex justify-between items-center border-b border-gray-200 dark:border-zinc-800">
                <span>Archivos seleccionados ({{ selectedFiles.length }})</span>
                <span v-if="isUploading" class="text-emerald-600 dark:text-emerald-400">{{ processedFiles }} / {{ totalFiles }} completados</span>
              </div>
              <ul class="divide-y divide-gray-200 dark:divide-zinc-800 max-h-64 overflow-y-auto custom-scrollbar">
                <li v-for="(file, index) in selectedFiles" :key="index" class="px-4 py-3 flex flex-col space-y-2 text-sm">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center text-gray-700 dark:text-zinc-300 truncate mr-4">
                      <DocumentTextIcon class="h-4 w-4 mr-2 text-gray-400 dark:text-zinc-500 flex-shrink-0" />
                      <span class="truncate" :title="file.name">{{ file.name }}</span>
                      <span v-if="!isUploading" class="ml-2 text-xs text-gray-400 dark:text-zinc-500 flex-shrink-0">({{ (file.size / 1024 / 1024).toFixed(2) }} MB)</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Status indicators -->
                        <span v-if="uploadProgress[index]?.status === 'done'" class="text-emerald-600 dark:text-emerald-500 text-xs font-bold uppercase">Listo</span>
                        <span v-if="uploadProgress[index]?.status === 'error'" class="text-red-500 text-xs font-bold uppercase" :title="uploadProgress[index].message">Error</span>
                        <span v-if="uploadProgress[index]?.status === 'uploading'" class="text-emerald-600 dark:text-emerald-400 text-xs animate-pulse">Procesando...</span>

                        <button 
                         v-if="!isUploading"
                         @click.stop="removeFile(index)"
                         class="text-gray-400 dark:text-zinc-500 hover:text-red-500 transition-colors"
                       >
                         <XMarkIcon class="h-4 w-4" />
                       </button>
                    </div>
                  </div>

                  <!-- Individual Progress Bar -->
                  <div v-if="isUploading && uploadProgress[index]?.status === 'uploading'" class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
                    <div 
                      class="bg-emerald-600 dark:bg-emerald-500 h-full transition-all duration-300"
                      :style="{ width: uploadProgress[index].percentage + '%' }"
                    ></div>
                  </div>
                </li>
              </ul>
            </div>

            <!-- Global Feedback -->
            <div v-if="isUploading" class="mt-4 p-4 border border-emerald-100 dark:border-zinc-800 rounded-xl bg-emerald-50/30 dark:bg-zinc-900/50">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-600 dark:text-zinc-400">Progreso total: {{ Math.round((processedFiles / totalFiles) * 100) }}%</span>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold animate-pulse uppercase tracking-tighter">No cierres esta ventana</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                  <div 
                    class="bg-emerald-600 dark:bg-emerald-500 h-full transition-all duration-500"
                    :style="{ width: (processedFiles / totalFiles) * 100 + '%' }"
                  ></div>
                </div>
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="closeUploadModal" v-if="!isUploading">
          Cerrar
        </SecondaryButton>

        <PrimaryButton 
          class="ml-3" 
          :class="{ 'opacity-25': isUploading }" 
          :disabled="isUploading || selectedFiles.length === 0"
          @click="submitUpload"
        >
          {{ isUploading ? 'Procesando cola...' : 'Subir facturas' }}
        </PrimaryButton>
      </template>
    </DialogModal>
    <!-- Delete Confirmation Modal -->
    <DialogModal :show="confirmingFacturaDeletion" @close="confirmingFacturaDeletion = false">
      <template #title>
        Eliminar Factura
      </template>

      <template #content>
        <div class="text-gray-700 dark:text-zinc-300">
          ¿Estás seguro de que quieres eliminar esta factura? Esta acción eliminará el registro de la base de datos y el archivo correspondiente en Google Drive de forma permanente.
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="confirmingFacturaDeletion = false">
          Cancelar
        </SecondaryButton>

        <DangerButton
          class="ml-3"
          :class="{ 'opacity-25': deleteForm.processing }"
          :disabled="deleteForm.processing"
          @click="deleteFactura"
        >
          {{ deleteForm.processing ? 'Eliminando...' : 'Eliminar' }}
        </DangerButton>
      </template>
    </DialogModal>

    <!-- Overwrite Confirmation Modal -->
    <DialogModal :show="confirmingOverwrite" @close="confirmingOverwrite = false">
      <template #title>
        Sustituir Factura Existente
      </template>
      <template #content>
        <div class="flex items-start gap-4">
          <div class="p-2 bg-amber-500/10 rounded-full">
            <ExclamationTriangleIcon class="h-6 w-6 text-amber-500" />
          </div>
          <div>
            <p class="text-sm text-gray-800 dark:text-zinc-300">
              La factura <strong>{{ facturaToOverwrite?.raw_data?.intended_number || facturaToOverwrite?.number.replace('DUP-', '') }}</strong> ya está registrada en el sistema.
            </p>
            <p class="mt-2 text-sm text-gray-500 dark:text-zinc-400">
              ¿Quieres sobreescribirla? Esta acción eliminará la versión anterior y conservará esta nueva subida.
            </p>
          </div>
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="confirmingOverwrite = false">
          Cancelar
        </SecondaryButton>
        <PrimaryButton
          class="ml-3 !bg-amber-600 hover:!bg-amber-700"
          :class="{ 'opacity-25': overwriteForm.processing }"
          :disabled="overwriteForm.processing"
          @click="submitOverwrite"
        >
          {{ overwriteForm.processing ? 'Sustituyendo...' : 'Sí, sobreescribir' }}
        </PrimaryButton>
      </template>
    </DialogModal>

    <!-- Edit / Manual Review Modal -->
    <DialogModal :show="showEditModal" @close="showEditModal = false">
      <template #title>
        Editar Factura / Revisión Manual
      </template>

      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
          <div class="md:col-span-2">
            <InputLabel for="edit_provider" value="Proveedor" />
            <input 
              id="edit_provider"
              v-model="editForm.provider_name"
              type="text"
              list="providers_list"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            />
            <InputError :message="editForm.errors.provider_name" class="mt-2" />
          </div>

          <div>
            <InputLabel for="edit_number" value="Número de Factura" />
            <input 
              id="edit_number"
              v-model="editForm.number"
              type="text"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            />
            <InputError :message="editForm.errors.number" class="mt-2" />
          </div>

          <div>
            <InputLabel for="edit_date" value="Fecha" />
            <input 
              id="edit_date"
              v-model="editForm.date"
              type="date"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm dark:[color-scheme:dark]"
            />
            <InputError :message="editForm.errors.date" class="mt-2" />
          </div>

          <div>
            <InputLabel for="edit_net" value="Base Imponible (€)" />
            <input 
              id="edit_net"
              v-model="editForm.net_amount"
              type="number"
              step="0.01"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            />
            <InputError :message="editForm.errors.net_amount" class="mt-2" />
          </div>

          <div>
            <InputLabel for="edit_tax" value="IVA (€)" />
            <input 
              id="edit_tax"
              v-model="editForm.tax_amount"
              type="number"
              step="0.01"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            />
            <InputError :message="editForm.errors.tax_amount" class="mt-2" />
          </div>

          <div>
            <InputLabel for="edit_total" value="Total Factura (€)" />
            <input 
              id="edit_total"
              v-model="editForm.total"
              type="number"
              step="0.01"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            />
            <InputError :message="editForm.errors.total" class="mt-2" />
          </div>

          <div>
            <InputLabel for="edit_status" value="Estado" />
            <select 
              id="edit_status"
              v-model="editForm.status"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            >
              <option value="recibida">Recibida</option>
              <option value="pagado">Pagado</option>
              <option value="procesando">Procesando</option>
              <option value="duplicada">Duplicada</option>
              <option value="error_ia">Error IA</option>
            </select>
            <InputError :message="editForm.errors.status" class="mt-2" />
          </div>

          <div class="md:col-span-2">
            <InputLabel for="edit_notes" value="Notas / Observaciones" />
            <textarea 
              id="edit_notes"
              v-model="editForm.notes"
              rows="2"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            ></textarea>
            <InputError :message="editForm.errors.notes" class="mt-2" />
          </div>
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="showEditModal = false">
          Cancelar
        </SecondaryButton>

        <PrimaryButton
          class="ml-3"
          :class="{ 'opacity-25': editForm.processing }"
          :disabled="editForm.processing"
          @click="submitEdit"
        >
          {{ editForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
        </PrimaryButton>
      </template>
    </DialogModal>
  </AuthenticatedLayout>
</template>
