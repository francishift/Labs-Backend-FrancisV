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
import Modal from '@/Components/Modal.vue'
import { ref } from 'vue'
import { ArrowDownTrayIcon, PaperAirplaneIcon, PencilIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  presupuesto: Object,
})

const emailModal = ref(false)
const emailForm = useForm({
    email: props.presupuesto.cliente?.email || '',
    message: ''
})

const sendEmail = () => {
    emailForm.post(route('admin.presupuestos.send-pdf', props.presupuesto.id), {
        preserveScroll: true,
        onSuccess: () => {
            emailModal.value = false;
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
                      Volver a Todos
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
                    <InputLabel value="Mensaje adjunto (opcional)" />
                    <TextArea v-model="emailForm.message" class="w-full mt-1" rows="4" placeholder="Estimado cliente, adjunto le enviamos el presupuesto solicitado..." />
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
