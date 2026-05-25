<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import InputError from '@/Components/InputError.vue'
import TextArea from '@/Components/TextArea.vue'
import AjaxSearchableSelect from '@/Components/AjaxSearchableSelect.vue'
import { getTodayDate } from '@/Utils/date'

const props = defineProps({
  proyectoId: [Number, String],
  closeCreateModal: Function,
})

const page = usePage()
const hours = ref(0)
const minutes = ref(0)

const form = useForm({
  servicio: '',
  descripcion: '',
  proyecto_id: props.proyectoId || '',
  fecha: getTodayDate(),
  duracion_minutos: 0,
  precio: '',
})

// Actualizar duracion_minutos cuando cambian las horas o los minutos
watch([hours, minutes], ([h, m]) => {
  form.duracion_minutos = (parseInt(h) || 0) * 60 + (parseInt(m) || 0)
}, { immediate: true })

const submit = () => {
  form.post(route('admin.servicios.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      hours.value = 0
      minutes.value = 0
      props.closeCreateModal()
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" id="create-servicio-form" class="space-y-6">
    <div v-if="!proyectoId">
      <InputLabel for="proyecto_id" value="Proyecto" required />
      <AjaxSearchableSelect
        id="proyecto_id"
        v-model="form.proyecto_id"
        endpoint="/api/dropdown/proyectos"
        label-key="proyecto"
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
        autofocus
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
        <CurrencyInput
          id="precio"
          v-model="form.precio"
          class="mt-1 block w-full"
          placeholder="0,00"
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
              id="hours"
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
              id="minutes"
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
