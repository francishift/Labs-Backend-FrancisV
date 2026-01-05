<script setup>
import { useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import SelectInput from '@/Components/SelectInput.vue'

const props = defineProps({
  closeCreateModal: Function,
})

const form = useForm({
  nombre: '',
  url: '',
  descripcion: '',
  precio: 0,
  tipo_licencia: '',
})

const licenciaOptions = [
  { value: 'Anual', label: 'Anual' },
  { value: 'Mensual', label: 'Mensual' },
  { value: 'Pago único', label: 'Pago único' },
]

const submit = () => {
  form.post(route('admin.extensiones.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      props.closeCreateModal()
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" id="create-extension-form" class="space-y-6">
    <div>
      <InputLabel for="nombre" value="Nombre de la extensión" />
      <TextInput
        id="nombre"
        v-model="form.nombre"
        type="text"
        class="mt-1 block w-full"
        required
        autofocus
      />
      <InputError :message="form.errors.nombre" class="mt-2" />
    </div>

    <div>
      <InputLabel for="url" value="URL" />
      <TextInput
        id="url"
        v-model="form.url"
        type="url"
        class="mt-1 block w-full"
        placeholder="https://example.com"
      />
      <InputError :message="form.errors.url" class="mt-2" />
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
      <TextInput
        id="precio"
        v-model="form.precio"
        type="number"
        step="0.01"
        min="0"
        class="mt-1 block w-full"
        required
      />
      <InputError :message="form.errors.precio" class="mt-2" />
    </div>
  </form>
</template>
