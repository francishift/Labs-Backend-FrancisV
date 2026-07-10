<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { ref } from 'vue'
import { ArrowDownTrayIcon, PaperAirplaneIcon, PencilIcon, ArrowLeftIcon, PaperClipIcon, XMarkIcon, DocumentArrowUpIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  presupuesto: Object,
})

const emailModal = ref(false)
const showConvertModal = ref(false)

// Estados en los que NO se permite convertir: cancelado (2), rechazado (3), ya facturado (4)
const puedeConvertirse = [0, 1].includes(props.presupuesto.status?.value ?? props.presupuesto.status)

const executeConvert = () => {
    router.post(route('admin.presupuestos.convertir-a-factura', props.presupuesto.id), {}, {
        onSuccess: () => { showConvertModal.value = false }
    })
}
const emailForm = useForm({
    email: props.presupuesto.cliente?.email || '',
    cc_emails: '',
    send_copy_to_me: false,
    message: '',
    attachments: []
})

const processFiles = (files) => {
    if (emailForm.attachments.length + files.length > 5) {
        alert('Puedes adjuntar un máximo de 5 archivos en total.')
        return
    }
    
    const validFiles = files.filter(file => {
        if (file.size > 10485760) {
            alert(`El archivo ${file.name} supera los 10MB y no será añadido.`)
            return false
        }
        return true
    })

    emailForm.attachments = [...emailForm.attachments, ...validFiles]
}

const handleFileChange = (e) => {
    const files = Array.from(e.target.files)
    processFiles(files)
    e.target.value = ''
}

const handleDrop = (e) => {
    const files = Array.from(e.dataTransfer.files)
    processFiles(files)
}

const removeAttachment = (index) => {
    emailForm.attachments.splice(index, 1)
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const sendEmail = () => {
    emailForm.post(route('admin.presupuestos.send-pdf', props.presupuesto.id), {
        preserveScroll: true,
        onSuccess: () => {
            emailModal.value = false;
            emailForm.reset();
            emailForm.attachments = [];
        }
    })
}

const formatCurrency = (val) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(val || 0)


</script>

<template>
  <Head :title="'Presupuesto ' + presupuesto.number" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader :title="'Presupuesto: ' + (presupuesto.number || 'Sin Número')">
        <template #actions>
          <div class="flex items-center gap-4">
            <span v-if="presupuesto.status == 2" class="px-3 py-1 font-bold text-white bg-red-600 rounded-md">
              ANULADO
            </span>
          <div class="flex flex-wrap gap-2">
              <Link :href="route('admin.presupuestos.index')">
                  <SecondaryButton>
                      <ArrowLeftIcon class="w-4 h-4 mr-2 inline" />
                      Todos los presupuestos
                  </SecondaryButton>
              </Link>
              <a :href="route('admin.presupuestos.pdf', { presupuesto: presupuesto.id, download: 1 })">
                <SecondaryButton>
                  <ArrowDownTrayIcon class="w-4 h-4 mr-2 inline" />
                  Descargar PDF
                </SecondaryButton>
              </a>
              <PrimaryButton @click="emailModal = true">
                <PaperAirplaneIcon class="w-4 h-4 mr-2 inline" />
                Enviar por Correo
              </PrimaryButton>
              <!-- Convertir a factura: solo si no está cancelado, rechazado ni ya facturado -->
              <PrimaryButton v-if="puedeConvertirse" @click="showConvertModal = true" class="bg-indigo-600 hover:bg-indigo-700">
                <DocumentArrowUpIcon class="w-4 h-4 mr-2 inline" />
                Convertir a Factura
              </PrimaryButton>
              <Link :href="route('admin.presupuestos.edit', presupuesto.id)">
                <SecondaryButton>
                  <PencilIcon class="w-4 h-4 mr-2 inline" />
                  Editar
                </SecondaryButton>
              </Link>
          </div>
          </div>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6 max-w-7xl mx-auto">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card class="p-6 md:col-span-2">
               <h3 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Información del Cliente</h3>
               <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Razón Social:</strong> {{ presupuesto.contact_name }}</p>
               <p v-if="presupuesto.cliente?.cif_nif" class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">CIF/NIF:</strong> {{ presupuesto.cliente.cif_nif }}</p>
               <p v-if="presupuesto.cliente?.email" class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Email:</strong> {{ presupuesto.cliente.email }}</p>
          </Card>
          
          <Card class="p-6 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/20">
               <h3 class="text-emerald-800 dark:text-emerald-500 font-bold mb-4">Resumen</h3>
               <p class="text-gray-600 dark:text-gray-400">Total a Pagar</p>
               <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(presupuesto.total) }}</p>
          </Card>
      </div>
      
      <Card class="p-0 overflow-hidden h-[800px]">
          <!-- PDF Viewer iframe -->
          <iframe :src="route('admin.presupuestos.pdf', presupuesto.id) + '?t=' + new Date().getTime()" class="w-full h-full border-0"></iframe>
      </Card>
    </div>

    <Modal :show="emailModal" @close="emailModal = false">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Enviar PDF por Correo
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 mb-4">
                El presupuesto se enviará a esta dirección como archivo adjunto.
            </p>
            <form @submit.prevent="sendEmail">
                <div class="mb-4">
                    <InputLabel value="Correo del destinatario" />
                    <TextInput v-model="emailForm.email" type="email" class="w-full mt-1" required placeholder="correo@cliente.com" />
                </div>
                <div class="mb-4">
                    <InputLabel value="CC (Con copia a)" />
                    <TextInput v-model="emailForm.cc_emails" type="text" class="w-full mt-1" placeholder="email1@ejemplo.com, email2@ejemplo.com..." />
                    <p class="text-xs text-gray-500 dark:text-zinc-500 mt-1">Separa los correos con comas si son varios.</p>
                </div>
                <div class="mb-4 flex items-center gap-2">
                    <input type="checkbox" id="send_copy_to_me" v-model="emailForm.send_copy_to_me" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500 dark:bg-zinc-900 dark:border-zinc-700">
                    <label for="send_copy_to_me" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer select-none">Enviarme una copia oculta a mí mismo</label>
                </div>
                <div class="mb-4">
                    <InputLabel value="Mensaje adjunto (opcional)" />
                    <TextArea v-model="emailForm.message" class="w-full mt-1" rows="4" placeholder="Estimado cliente, adjunto le enviamos el presupuesto solicitado..." />
                </div>
                
                <div class="mb-4">
                    <InputLabel value="Archivos adicionales (Opcional)" />
                    
                    <!-- Input oculto fuera del área de click para evitar burbujeo -->
                    <input ref="fileInput" type="file" multiple class="hidden" @change="handleFileChange">

                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-zinc-700 border-dashed rounded-md cursor-pointer hover:border-emerald-500 transition-colors" 
                         @click="$refs.fileInput.click()"
                         @dragenter.prevent
                         @dragover.prevent
                         @dragleave.prevent
                         @drop.prevent="handleDrop">
                        <div class="space-y-1 text-center">
                            <PaperClipIcon class="mx-auto h-12 w-12 text-gray-400" pointer-events="none" />
                            <div class="flex text-sm text-gray-600 dark:text-gray-400 justify-center pointer-events-none">
                                <span class="relative bg-transparent rounded-md font-medium text-emerald-600">
                                    Sube archivos
                                </span>
                                <p class="pl-1">o arrástralos aquí</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 pointer-events-none">
                                PDF, JPG, PNG, DOCX, XLSX hasta 10MB. Máx 5 archivos.
                            </p>
                        </div>
                    </div>
                    <InputError :message="emailForm.errors.attachments" class="mt-2" />
                    
                    <!-- Lista de archivos seleccionados -->
                    <ul v-if="emailForm.attachments.length > 0" class="mt-3 divide-y divide-gray-200 dark:divide-zinc-700 border border-gray-200 dark:border-zinc-700 rounded-md">
                        <li v-for="(file, index) in emailForm.attachments" :key="index" class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
                            <div class="w-0 flex-1 flex items-center">
                                <PaperClipIcon class="flex-shrink-0 h-5 w-5 text-gray-400" aria-hidden="true" />
                                <span class="ml-2 flex-1 w-0 truncate text-gray-700 dark:text-gray-300">
                                    {{ file.name }}
                                </span>
                            </div>
                            <div class="ml-4 flex-shrink-0 flex items-center gap-3">
                                <span class="text-xs text-gray-500 dark:text-zinc-500">{{ formatFileSize(file.size) }}</span>
                                <button type="button" @click="removeAttachment(index)" class="font-medium text-red-600 hover:text-red-500">
                                    <XMarkIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </ul>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <SecondaryButton @click="emailModal = false" type="button">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="emailForm.processing" :class="{ 'opacity-25': emailForm.processing }">
                        {{ emailForm.processing ? 'Enviando...' : 'Enviar ahora' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
    <ConfirmModal
      :show="showConvertModal"
      title="Convertir a Factura"
      :content="`¿Confirmas convertir el presupuesto '${presupuesto.number}' en una nueva factura? El presupuesto quedará marcado como 'Facturado' y serás redirigido a la factura generada.`"
      confirm-text="Sí, convertir"
      cancel-text="Cancelar"
      @close="showConvertModal = false"
      @confirm="executeConvert"
    />
  </AuthenticatedLayout>
</template>
