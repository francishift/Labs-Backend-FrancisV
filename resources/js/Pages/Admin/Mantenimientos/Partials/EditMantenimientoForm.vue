<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { formatDateForInput } from '@/Utils/date';

const props = defineProps({
    mantenimiento: Object,
    clients: Array,
    availableExtensions: Array,
    closeEditModal: Function,
});

const editForm = useForm({
    aplicacion: '',
    url: '',
    client_id: '',
    extensiones: [],
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    tipo_pago: '',
    importe: '',
    estado: '',
});

const setFormValues = () => {
    if (props.mantenimiento) {
        editForm.aplicacion = props.mantenimiento.aplicacion;
        editForm.url = props.mantenimiento.url || '';
        editForm.client_id = props.mantenimiento.client_id;
        editForm.extensiones = props.mantenimiento.extensiones?.map(e => e.id) || [];
        editForm.descripcion = props.mantenimiento.descripcion;
        editForm.fecha_inicio = formatDateForInput(props.mantenimiento.fecha_inicio);
        editForm.fecha_fin = formatDateForInput(props.mantenimiento.fecha_fin);
        editForm.tipo_pago = props.mantenimiento.tipo_pago;
        editForm.importe = props.mantenimiento.importe;
        editForm.estado = props.mantenimiento.estado;
    }
};

onMounted(setFormValues);
watch(() => props.mantenimiento, setFormValues);

const submitUpdate = () => {
    editForm.patch(route('admin.mantenimientos.update', props.mantenimiento.id), {
        preserveScroll: true,
        onSuccess: () => {
            props.closeEditModal();
        },
    });
};
</script>

<template>
    <form id="edit-mantenimiento-form" class="mt-4 space-y-4" @submit.prevent="submitUpdate">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <InputLabel for="edit_aplicacion" value="Aplicación / Web" />
                <TextInput id="edit_aplicacion" v-model="editForm.aplicacion" type="text" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.aplicacion" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_url" value="URL del proyecto" />
                <TextInput id="edit_url" v-model="editForm.url" type="url" placeholder="https://..." class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.url" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_client_id" value="Cliente" />
                <SearchableSelect
                    v-model="editForm.client_id"
                    :options="clients"
                    placeholder="Seleccionar cliente..."
                    class="mt-1"
                />
                <InputError class="mt-2" :message="editForm.errors.client_id" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_extensiones" value="Extensiones Utilizadas" />
                <MultiSelect
                    id="edit_extensiones"
                    v-model="editForm.extensiones"
                    :options="availableExtensions"
                    placeholder="Seleccionar extensiones..."
                    class="mt-1"
                />
                <InputError class="mt-2" :message="editForm.errors.extensiones" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_descripcion" value="Descripción" />
                <TextArea
                    id="edit_descripcion"
                    v-model="editForm.descripcion"
                    class="mt-1 block w-full"
                    rows="3"
                />
                <InputError class="mt-2" :message="editForm.errors.descripcion" />
            </div>

            <div>
                <InputLabel for="edit_fecha_inicio" value="Fecha de Inicio" />
                <TextInput id="edit_fecha_inicio" v-model="editForm.fecha_inicio" type="date" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.fecha_inicio" />
            </div>

            <div>
                <InputLabel for="edit_fecha_fin" value="Fecha de Fin" />
                <TextInput id="edit_fecha_fin" v-model="editForm.fecha_fin" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.fecha_fin" />
            </div>

            <div>
                <InputLabel for="edit_tipo_pago" value="Tipo de Pago" />
                <SearchableSelect
                    id="edit_tipo_pago"
                    v-model="editForm.tipo_pago"
                    required
                    class="mt-1 block w-full"
                    :options="[
                        { id: 'mensual', name: 'Mensual' },
                        { id: 'trimestral', name: 'Trimestral' },
                        { id: 'anual', name: 'Anual' }
                    ]"
                />
                <InputError class="mt-2" :message="editForm.errors.tipo_pago" />
            </div>

            <div>
                <InputLabel for="edit_importe" value="Importe (€)" />
                <TextInput id="edit_importe" v-model="editForm.importe" type="number" step="0.01" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.importe" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_estado" value="Estado" />
                <SearchableSelect
                    id="edit_estado"
                    v-model="editForm.estado"
                    required
                    class="mt-1 block w-full"
                    :options="[
                        { id: 'en curso', name: 'En curso' },
                        { id: 'finalizado', name: 'Finalizado' }
                    ]"
                />
                <InputError class="mt-2" :message="editForm.errors.estado" />
            </div>
        </div>
    </form>
</template>
