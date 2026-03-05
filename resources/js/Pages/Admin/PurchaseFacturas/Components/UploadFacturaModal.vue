<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import { DocumentTextIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: Boolean,
})

const emit = defineEmits(['close', 'uploaded'])

// Control del área de arrastrar y soltar
const fileInput = ref(null)
const isDragging = ref(false)

// Estado de los archivos seleccionados y validación
const selectedFiles = ref([])
const form = useForm({})

// Control del progreso de subida concurrente
const uploadProgress = ref([])
const totalFiles = ref(0)
const processedFiles = ref(0)
const isUploading = ref(false)

/**
 * Cierra el modal de subida de archivos si no hay una subida en curso.
 * Limpia el estado interno antes de cerrar.
 */
const close = () => {
    if (isUploading.value) return
    selectedFiles.value = []
    form.clearErrors()
    emit('close')
}

/**
 * Maneja la selección manual de archivos a través del input de tipo file.
 * Filtra únicamente los archivos en formato PDF.
 */
const handleFileSelect = (e) => {
    const files = Array.from(e.target.files).filter(file => file.type === 'application/pdf')
    selectedFiles.value = [...selectedFiles.value, ...files]
}

/**
 * Maneja el evento drop cuando los archivos son arrastrados al área.
 * Filtra únicamente los archivos en formato PDF.
 */
const handleDrop = (e) => {
    isDragging.value = false
    const droppedFiles = Array.from(e.dataTransfer.files).filter(file => file.type === 'application/pdf')
    selectedFiles.value = [...selectedFiles.value, ...droppedFiles]
}

/**
 * Elimina un archivo de la lista de pendientes antes de comenzar la subida.
 */
const removeFile = (index) => {
    selectedFiles.value.splice(index, 1)
}

/**
 * Procesa la subida de los archivos seleccionados de manera asíncrona y concurrente.
 * Utiliza FormData de Axios para poder reportar el progreso individual.
 * Previene el cierre del modal durante la ejecución.
 */
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
    emit('uploaded')
}
</script>

<template>
    <DialogModal :show="show" @close="close">
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
        <SecondaryButton @click="close" v-if="!isUploading">
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
</template>
