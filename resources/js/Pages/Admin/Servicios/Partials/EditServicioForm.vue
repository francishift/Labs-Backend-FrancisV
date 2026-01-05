<script setup>
import { ref, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import TextArea from '@/Components/TextArea.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { formatDateForInput } from '@/Utils/date'

const props = defineProps({
  servicio: Object,
  proyectos: Array,
  hideProyectoSelector: {
    type: Boolean,
    default: false,
  },
  closeEditModal: Function,
})

const hours = ref(0)
const minutes = ref(0)

const form = useForm({
  servicio: '',
  descripcion: '',
  proyecto_id: '',
  fecha: '',
  duracion_minutos: 0,
  precio: '',
})

// Initialize form and update when servicio prop changes
watch(() => props.servicio, (servicio) => {
  if (servicio) {
    form.servicio = servicio.servicio
    form.descripcion = servicio.descripcion || ''
    form.proyecto_id = servicio.proyecto_id
    form.fecha = formatDateForInput(servicio.fecha)
    form.duracion_minutos = servicio.duracion_minutos
    form.precio = servicio.precio || ''
    
    // Initialize hours/minutes for the UI
    hours.value = Math.floor(servicio.duracion_minutos / 60)
    minutes.value = servicio.duracion_minutos % 60
    
    form.clearErrors()
  }
}, { immediate: true })

// Update duracion_minutos when hours or minutes change
watch([hours, minutes], ([h, m]) => {
  form.duracion_minutos = (parseInt(h) || 0) * 60 + (parseInt(m) || 0)
})

const submit = () => {
  form.patch(route('admin.servicios.update', props.servicio.id), {
    preserveScroll: true,
    onSuccess: () => props.closeEditModal(),
  })
}
</script>

<template>
  <form @submit.prevent="submit" id="edit-servicio-form" class="space-y-6">
    <div v-if="!hideProyectoSelector">
      <InputLabel for="proyecto_id" value="Proyecto" required />
      <SearchableSelect
        id="proyecto_id"
        v-model="form.proyecto_id"
        :options="proyectos.map(p => ({ id: p.id, name: p.proyecto }))"
        class="mt-1 block w-full"
        placeholder="Seleccionar un proyecto..."
        required
      />
      <InputError :message="form.errors.proyecto_id" class="mt-2" />
    </div>

    <div>
      <InputLabel for="servicio" value="Servicio" required />
      <TextInput
        id="servicio"
        type="text"
        v-model="form.servicio"
        class="mt-1 block w-full"
        required
      />
      <InputError :message="form.errors.servicio" class="mt-2" />
    </div>

    <div>
      <InputLabel for="descripcion" value="Descripción" />
      <TextArea
        id="descripcion"
        v-model="form.descripcion"
        class="mt-1 block w-full"
        rows="3"
      />
      <InputError :message="form.errors.descripcion" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <InputLabel for="fecha" value="Fecha" required />
        <TextInput
          id="fecha"
          type="date"
          v-model="form.fecha"
          class="mt-1 block w-full"
          required
        />
        <InputError :message="form.errors.fecha" class="mt-2" />
      </div>

      <div>
        <InputLabel for="precio" value="Precio" />
        <TextInput
          id="precio"
          type="number"
          step="0.01"
          v-model="form.precio"
          class="mt-1 block w-full"
          placeholder="0.00"
        />
        <InputError :message="form.errors.precio" class="mt-2" />
      </div>
    </div>

    <div>
      <InputLabel value="Duración" required />
      <div class="flex items-center gap-4 mt-1">
        <div class="flex-1">
          <div class="relative">
            <TextInput
              id="edit_hours"
              type="number"
              v-model="hours"
              class="block w-full pr-10"
              min="0"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
              h
            </div>
          </div>
        </div>
        <div class="flex-1">
          <div class="relative">
            <TextInput
              id="edit_minutes"
              type="number"
              v-model="minutes"
              class="block w-full pr-10"
              min="0"
              max="59"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
              min
            </div>
          </div>
        </div>
      </div>
      <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
        Total: {{ form.duracion_minutos }} minutos
      </p>
      <InputError :message="form.errors.duracion_minutos" class="mt-2" />
    </div>
  </form>
</template>
