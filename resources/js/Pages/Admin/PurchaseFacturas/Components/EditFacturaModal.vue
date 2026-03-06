<script setup>
import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DialogModal from '@/Components/DialogModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    show: Boolean,
    factura: Object
})

const emit = defineEmits(['close', 'saved'])

const editForm = useForm({
    id: null,
    number: '',
    provider_name: '',
    date: '',
    total: 0,
    net_amount: 0,
    tax_amount: 0,
    irpf_amount: 0,
    status: '',
    notes: ''
})

/**
 * Observa la apertura del modal y la factura. Al abrir,
 * llena el formulario local `editForm` con los datos para evitar la mutación de props directos.
 */
watch([() => props.show, () => props.factura], ([show, newFactura]) => {
    if (show && newFactura) {
        editForm.id = newFactura.id
        editForm.number = newFactura.number
        editForm.provider_name = newFactura.provider_name
        
        // CORRECCIÓN ZONA HORARIA:
        // Evitamos usar `new Date(date).toISOString()` que convierte la fecha local a UTC
        // y que provoca que fechas a medianoche se atrasen un día (ej. en España DST/ECT).
        if (newFactura.date) {
            const d = new Date(newFactura.date)
            // Extrae el año, mes y día de la zona horaria en la que se instanció la fecha
            const year = d.getFullYear()
            const month = String(d.getMonth() + 1).padStart(2, '0')
            const day = String(d.getDate()).padStart(2, '0')
            editForm.date = `${year}-${month}-${day}`
        } else {
            editForm.date = ''
        }

        editForm.total = newFactura.total
        editForm.net_amount = newFactura.net_amount
        editForm.tax_amount = newFactura.tax_amount
        editForm.irpf_amount = newFactura.irpf_amount || 0
        editForm.status = newFactura.status
        editForm.notes = newFactura.notes || ''
        
        editForm.clearErrors()
    } else if (!show) {
        editForm.reset()
        editForm.clearErrors()
    }
}, { deep: true, immediate: true })

/**
 * Resetea el formulario y emite el evento para cerrar el modal.
 */
const close = () => {
    editForm.reset()
    emit('close')
}

/**
 * Persiste los cambios realizados al servidor usando Inertia.js.
 * Si es exitoso, cierra el modal y emite el evento 'saved' para indicar
 * al componente padre que debe refrescar la tabla de registros.
 */
const submitEdit = () => {
    editForm.put(route('admin.purchase-facturas.update', editForm.id), {
        onSuccess: () => {
            emit('saved')
            close()
        }
    })
}
</script>

<template>
    <DialogModal :show="show" @close="close">
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
            <InputLabel for="edit_irpf" value="IRPF (€)" />
            <input 
              id="edit_irpf"
              v-model="editForm.irpf_amount"
              type="number"
              step="0.01"
              class="w-full mt-1 bg-white dark:bg-zinc-950 border-gray-300 dark:border-zinc-800 text-gray-900 dark:text-zinc-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            />
            <InputError :message="editForm.errors.irpf_amount" class="mt-2" />
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
        <SecondaryButton @click="close">
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
</template>
