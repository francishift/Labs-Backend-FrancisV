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
  extension: Object,
  closeEditModal: Function,
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

watch(
  () => props.extension,
  (ext) => {
    if (ext) {
      form.nombre = ext.nombre
      form.url = ext.url || ''
      form.descripcion = ext.descripcion || ''
      form.precio = ext.precio
      form.tipo_licencia = ext.tipo_licencia || ''
    }
  },
  { immediate: true }
)

const submit = () => {
  form.patch(route('admin.extensiones.update', props.extension.id), {
    preserveScroll: true,
    onSuccess: () => {
      props.closeEditModal()
    },
  })
}
</script>

<template>
  <form @submit.prevent="submit" id="edit-extension-form" class="space-y-6">
    <div>
      <InputLabel for="nombre_edit" value="Nombre de la extensión" />
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
      <InputLabel for="url_edit" value="URL" />
      <TextInput
        id="url_edit"
        v-model="form.url"
        type="url"
        class="mt-1 block w-full"
        placeholder="https://example.com"
      />
      <InputError :message="form.errors.url" class="mt-2" />
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
  </form>
</template>
