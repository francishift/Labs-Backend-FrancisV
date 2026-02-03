<script setup>
import { useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import CurrencyInput from '@/Components/CurrencyInput.vue'
import TextArea from '@/Components/TextArea.vue'
import SelectInput from '@/Components/SelectInput.vue'

const props = defineProps({
  closeCreateModal: Function,
})

const form = useForm({
  tipo: '',
  nombre: '',
  descripcion: '',
  tipo_licencia: '',
  precio: 0,
  estado: 'Activa',
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

const submit = () => {
  form.post(route('admin.softwares.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      props.closeCreateModal()
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" id="create-software-form" class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <InputLabel for="tipo" value="Tipo" />
          <SelectInput
            id="tipo"
            v-model="form.tipo"
            :options="tipoOptions"
            placeholder="Selecciona tipo..."
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.tipo" class="mt-2" />
        </div>

        <div>
          <InputLabel for="estado" value="Estado" />
          <SelectInput
            id="estado"
            v-model="form.estado"
            :options="estadoOptions"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.estado" class="mt-2" />
        </div>
    </div>

    <div>
      <InputLabel for="nombre" value="Nombre" />
      <TextInput
        id="nombre"
        v-model="form.nombre"
        type="text"
        class="mt-1 block w-full"
        required
      />
      <InputError :message="form.errors.nombre" class="mt-2" />
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

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <InputLabel for="tipo_licencia" value="Tipo de licencia" />
          <SelectInput
            id="tipo_licencia"
            v-model="form.tipo_licencia"
            :options="licenciaOptions"
            placeholder="Selecciona un tipo..."
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.tipo_licencia" class="mt-2" />
        </div>

        <div>
          <InputLabel for="precio" value="Precio (€)" />
          <CurrencyInput
            id="precio"
            v-model="form.precio"
            class="mt-1 block w-full"
            required
          />
          <InputError :message="form.errors.precio" class="mt-2" />
        </div>
    </div>
  </form>
</template>
