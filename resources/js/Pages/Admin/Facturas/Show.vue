<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Card from '@/Components/Card.vue'
import PageHeader from '@/Components/PageHeader.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import InputLabel from '@/Components/InputLabel.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowDownTrayIcon, PaperAirplaneIcon, PencilIcon, ArrowLeftIcon, DocumentDuplicateIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  factura: Object,
})

const emailModal = ref(false)
const emailForm = useForm({
    email: props.factura.cliente?.email || '',
    message: ''
})

const sendEmail = () => {
    emailForm.post(route('admin.facturas.send-pdf', props.factura.id), {
        preserveScroll: true,
        onSuccess: () => {
            emailModal.value = false;
        }
    })
}

const formatCurrency = (val) => new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(val || 0)

const showDuplicateModal = ref(false)

const confirmDuplicate = () => {
    showDuplicateModal.value = true
}

const executeDuplicate = () => {
    router.post(route('admin.facturas.duplicate', props.factura.id), {}, {
        preserveScroll: true,
        onSuccess: () => { showDuplicateModal.value = false; }
    })
}


</script>

<template>
  <Head :title="'Factura ' + factura.number" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader :title="'Factura: ' + (factura.number || 'Sin Número')">
        <template #actions>
          <div class="flex items-center gap-4">
            <span v-if="factura.status == 2" class="px-3 py-1 font-bold text-white bg-red-600 rounded-md">
              ANULADO
            </span>
          <div class="flex flex-wrap gap-2">
              <Link :href="route('admin.facturas.index')">
                  <SecondaryButton>
                      <ArrowLeftIcon class="w-4 h-4 mr-2 inline" />
                      Volver a Todos
                  </SecondaryButton>
              </Link>
              <a :href="route('admin.facturas.pdf', { factura: factura.id, download: 1 })">
                <SecondaryButton>
                  <ArrowDownTrayIcon class="w-4 h-4 mr-2 inline" />
                  Descargar PDF
                </SecondaryButton>
              </a>
              <PrimaryButton @click="emailModal = true">
                <PaperAirplaneIcon class="w-4 h-4 mr-2 inline" />
                Enviar por Correo
              </PrimaryButton>
              <button @click="confirmDuplicate" class="inline-flex items-center px-4 py-2 bg-orange-600 dark:bg-orange-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 dark:hover:bg-orange-600 focus:bg-orange-700 dark:focus:bg-orange-600 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-800 transition ease-in-out duration-150">
                  <DocumentDuplicateIcon class="w-4 h-4 mr-2 inline" />
                  Duplicar
              </button>
              <Link :href="route('admin.facturas.edit', factura.id)">
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
               <p class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Razón Social:</strong> {{ factura.contact_name }}</p>
               <p v-if="factura.cliente?.cif_nif" class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">CIF/NIF:</strong> {{ factura.cliente.cif_nif }}</p>
               <p v-if="factura.cliente?.email" class="text-gray-700 dark:text-gray-300"><strong class="text-gray-900 dark:text-white">Email:</strong> {{ factura.cliente.email }}</p>
          </Card>
          
          <Card class="p-6 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/20">
               <h3 class="text-emerald-800 dark:text-emerald-500 font-bold mb-4">Resumen</h3>
               <p class="text-gray-600 dark:text-gray-400">Total a Pagar</p>
               <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(factura.total) }}</p>
          </Card>
      </div>
      
      <Card class="p-0 overflow-hidden h-[800px]">
          <!-- PDF Viewer iframe -->
          <iframe :src="route('admin.facturas.pdf', factura.id)" class="w-full h-full border-0"></iframe>
      </Card>
    </div>

    <Modal :show="emailModal" @close="emailModal = false">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Enviar PDF por Correo
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 mb-4">
                El factura se enviará a esta dirección como archivo adjunto.
            </p>
            <form @submit.prevent="sendEmail">
                <div class="mb-4">
                    <InputLabel value="Correo del destinatario" />
                    <TextInput v-model="emailForm.email" type="email" class="w-full mt-1" required placeholder="correo@cliente.com" />
                </div>
                <div class="mb-4">
                    <InputLabel value="Mensaje adjunto (opcional)" />
                    <TextArea v-model="emailForm.message" class="w-full mt-1" rows="4" placeholder="Estimado cliente, adjunto le enviamos el factura solicitado..." />
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
  </AuthenticatedLayout>
</template>
