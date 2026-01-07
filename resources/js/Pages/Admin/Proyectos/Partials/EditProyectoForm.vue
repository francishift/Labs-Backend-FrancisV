<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { formatDateForInput } from '@/Utils/date';

const props = defineProps({
    proyecto: Object,
    clients: Array,
    availableExtensions: Array,
    closeEditModal: Function,
});

const editForm = useForm({
    proyecto: '',
    descripcion: '',
    fecha_inicio: '',
    fecha_fin: '',
    presupuesto: '',
    estado: '',
    client_id: '',
    extensiones: [],
});

watch(() => props.proyecto, (proyecto) => {
    if (proyecto) {
        editForm.proyecto = proyecto.proyecto;
        editForm.descripcion = proyecto.descripcion || '';
        editForm.fecha_inicio = formatDateForInput(proyecto.fecha_inicio);
        editForm.fecha_fin = formatDateForInput(proyecto.fecha_fin);
        editForm.presupuesto = proyecto.presupuesto;
        editForm.estado = proyecto.estado;
        editForm.client_id = proyecto.client_id;
        editForm.extensiones = proyecto.extensiones ? proyecto.extensiones.map(e => e.id) : [];
        editForm.clearErrors();
    }
}, { immediate: true });

const submitEdit = () => {
    if (!props.proyecto) return;
    
    editForm.patch(route('admin.proyectos.update', props.proyecto.id), {
        preserveScroll: true,
        onSuccess: () => props.closeEditModal(),
    });
};
</script>

<template>
    <form id="edit-proyecto-form" class="mt-4 space-y-4" @submit.prevent="submitEdit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <InputLabel for="edit_proyecto" value="Nombre del Proyecto" />
                <TextInput id="edit_proyecto" v-model="editForm.proyecto" type="text" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.proyecto" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_client_id" value="Cliente" />
                <SearchableSelect
                    id="edit_client_id"
                    v-model="editForm.client_id"
                    :options="clients"
                    placeholder="Buscar y seleccionar cliente..."
                    class="mt-1"
                />
                <InputError class="mt-2" :message="editForm.errors.client_id" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="edit_extensiones" value="Extensiones" />
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
                <InputLabel for="edit_presupuesto" value="Presupuesto (€)" />
                <TextInput id="edit_presupuesto" v-model="editForm.presupuesto" type="number" step="0.01" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.presupuesto" />
            </div>

            <div>
                <InputLabel for="edit_estado" value="Estado" />
                <SearchableSelect
                    id="edit_estado"
                    v-model="editForm.estado"
                    required
                    class="mt-1 block w-full"
                    :options="[
                        { id: 'En proceso', name: 'En proceso' },
                        { id: 'Finalizado', name: 'Finalizado' },
                        { id: 'Cancelado', name: 'Cancelado' }
                    ]"
                />
                <InputError class="mt-2" :message="editForm.errors.estado" />
            </div>
        </div>
    </form>
</template>
