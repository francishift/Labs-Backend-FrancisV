<script setup>
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    show: Boolean,
    factura: Object
})

const emit = defineEmits(['close', 'overwritten'])

const overwriteForm = useForm({})

/**
 * Cierra el modal de confirmación sin ejecutar modificaciones.
 */
const close = () => {
    emit('close')
}

/**
 * Confirma la sustitución enviando una petición POST. Este proceso purgará
 * el archivo anterior de Drive y eliminará el registro (soft/force delete) original.
 */
const submitOverwrite = () => {
    if (!props.factura) return

    overwriteForm.post(route('admin.purchase-facturas.overwrite', props.factura.id), {
        onSuccess: () => {
            emit('overwritten')
            close()
        }
    })
}
</script>

<template>
    <DialogModal :show="show" @close="close">
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
              La factura <strong>{{ factura?.raw_data?.intended_number || factura?.number?.replace('DUP-', '') }}</strong> ya está registrada en el sistema.
            </p>
            <p class="mt-2 text-sm text-gray-500 dark:text-zinc-400">
              ¿Quieres sobreescribirla? Esta acción eliminará la versión anterior y conservará esta nueva subida.
            </p>
          </div>
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="close">
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
</template>
