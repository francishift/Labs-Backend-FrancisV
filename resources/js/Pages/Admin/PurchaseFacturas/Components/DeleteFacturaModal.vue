<script setup>
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

const props = defineProps({
    show: Boolean,
    facturaId: [Number, String]
})

const emit = defineEmits(['close', 'deleted'])

const deleteForm = useForm({})

/**
 * Cierra el modal de confirmación sin ejecutar ninguna acción.
 */
const close = () => {
    emit('close')
}

/**
 * Envía la petición DELETE al servidor para eliminar la factura y el archivo en Drive.
 */
const deleteFactura = () => {
    if (!props.facturaId) return

    deleteForm.delete(route('admin.purchase-facturas.destroy', props.facturaId), {
        onSuccess: () => {
            emit('deleted')
            close()
        },
        onError: () => {
            close()
        }
    })
}
</script>

<template>
    <DialogModal :show="show" @close="close">
      <template #title>
        Eliminar Factura
      </template>

      <template #content>
        <div class="text-gray-700 dark:text-zinc-300">
          ¿Estás seguro de que quieres eliminar esta factura? Esta acción eliminará el registro de la base de datos y el archivo correspondiente en Google Drive de forma permanente.
        </div>
      </template>

      <template #footer>
        <SecondaryButton @click="close">
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
</template>
