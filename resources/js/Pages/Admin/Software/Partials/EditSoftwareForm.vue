<script setup>
import { useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import TextArea from '@/Components/TextArea.vue'
import SelectInput from '@/Components/SelectInput.vue'
import { watch } from 'vue'

const props = defineProps({
  software: Object,
  closeEditModal: Function,
})

const form = useForm({
  tipo: '',
  nombre: '',
  descripcion: '',
  tipo_licencia: '',
  precio: 0,
  estado: '',
})

const tipoOptions = [
  { value: 'Software', label: 'Software' },
  { value: 'Hosting', label: 'Hosting' },
]

const licenciaOptions = [
  { value: 'Anual', label: 'Anual' },
  { value: 'Mensual', label: 'Mensual' },
]

const estadoOptions = [
  { value: 'Activa', label: 'Activa' },
  { value: 'Finalizada', label: 'Finalizada' },
]

watch(
  () => props.software,
  (soft) => {
    if (soft) {
      form.tipo = soft.tipo
      form.nombre = soft.nombre
      form.descripcion = soft.descripcion || ''
      form.tipo_licencia = soft.tipo_licencia
      form.precio = soft.precio
      form.estado = soft.estado
    }
  },
  { immediate: true }
)

const submit = () => {
  form.patch(route('admin.softwares.update', props.software.id), {
    preserveScroll: true,
    onSuccess: () => {
      props.closeEditModal()
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" id="edit-software-form" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <InputLabel for="tipo_edit" value="Tipo" />
          <SelectInput
            id="tipo_edit"
            v-model="form.tipo"
            :options="tipoOptions"
            placeholder="Selecciona tipo..."
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.tipo" class="mt-2" />
        </div>

        <div>
          <InputLabel for="estado_edit" value="Estado" />
          <SelectInput
            id="estado_edit"
            v-model="form.estado"
            :options="estadoOptions"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.estado" class="mt-2" />
        </div>
    </div>

    <div>
      <InputLabel for="nombre_edit" value="Nombre" />
      <TextInput
        id="nombre_edit"
        v-model="form.nombre"
        type="text"
        class="mt-1 block w-full"
        required
      />
      <InputError :message="form.errors.nombre" class="mt-2" />
    </div>

    <div>
      <InputLabel for="descripcion_edit" value="Descripción" />
      <TextArea
        id="descripcion_edit"
        v-model="form.descripcion"
        class="mt-1 block w-full"
        rows="3"
      />
      <InputError :message="form.errors.descripcion" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <InputLabel for="tipo_licencia_edit" value="Tipo de licencia" />
          <SelectInput
            id="tipo_licencia_edit"
            v-model="form.tipo_licencia"
            :options="licenciaOptions"
            placeholder="Selecciona un tipo..."
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.tipo_licencia" class="mt-2" />
        </div>

        <div>
          <InputLabel for="precio_edit" value="Precio (€)" />
          <CurrencyInput
            id="precio_edit"
            v-model="form.precio"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.precio" class="mt-2" />
        </div>
    </div>
  </form>
</template>
