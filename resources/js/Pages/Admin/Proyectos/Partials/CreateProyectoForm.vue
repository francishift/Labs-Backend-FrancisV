<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import MultiSelect from '@/Components/MultiSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { getTodayDate } from '@/Utils/date';
import { watch } from 'vue';

const props = defineProps({
    clients: Array,
    availableExtensions: Array,
    closeCreateModal: Function,
});

const createForm = useForm({
    proyecto: '',
    descripcion: '',
    fecha_inicio: getTodayDate(),
    fecha_fin: '',
    presupuesto: '',
    estado: 'En proceso',
    client_id: '',
    extensiones: [],
});

watch(() => createForm.estado, (newEstado) => {
    if (newEstado === 'Finalizado' && !createForm.fecha_fin) {
        createForm.fecha_fin = getTodayDate();
    }
});

const submitCreate = () => {
    createForm.post(route('admin.proyectos.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            props.closeCreateModal();
        },
    });
};
</script>

<template>
    <form id="create-proyecto-form" class="mt-4 space-y-4" @submit.prevent="submitCreate">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <InputLabel for="create_proyecto" value="Nombre del Proyecto" />
                <TextInput id="create_proyecto" v-model="createForm.proyecto" type="text" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.proyecto" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="create_client_id" value="Cliente" />
                <SearchableSelect
                    id="create_client_id"
                    v-model="createForm.client_id"
                    :options="clients"
                    placeholder="Buscar y seleccionar cliente..."
                    class="mt-1"
                />
                <InputError class="mt-2" :message="createForm.errors.client_id" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="create_extensiones" value="Extensiones" />
                <MultiSelect
                    id="create_extensiones"
                    v-model="createForm.extensiones"
                    :options="availableExtensions"
                    placeholder="Seleccionar extensiones..."
                    class="mt-1"
                />
                <InputError class="mt-2" :message="createForm.errors.extensiones" />
            </div>


            <div class="md:col-span-2">
                <InputLabel for="create_descripcion" value="Descripción" />
                <TextArea
                    id="create_descripcion"
                    v-model="createForm.descripcion"
                    class="mt-1 block w-full"
                    rows="3"
                />
                <InputError class="mt-2" :message="createForm.errors.descripcion" />
            </div>

            <div>
                <InputLabel for="create_fecha_inicio" value="Fecha de Inicio" />
                <TextInput id="create_fecha_inicio" v-model="createForm.fecha_inicio" type="date" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.fecha_inicio" />
            </div>

            <div>
                <InputLabel for="create_fecha_fin" value="Fecha de Fin" />
                <TextInput id="create_fecha_fin" v-model="createForm.fecha_fin" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.fecha_fin" />
            </div>

            <div>
                <InputLabel for="create_presupuesto" value="Presupuesto (€)" />
                <CurrencyInput id="create_presupuesto" v-model="createForm.presupuesto" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.presupuesto" />
            </div>

            <div>
                <InputLabel for="create_estado" value="Estado" />
                <SearchableSelect
                    id="create_estado"
                    v-model="createForm.estado"
                    required
                    class="mt-1 block w-full"
                    :options="[
                        { id: 'En proceso', name: 'En proceso' },
                        { id: 'Finalizado', name: 'Finalizado' },
                        { id: 'Cancelado', name: 'Cancelado' }
                    ]"
                />
                <InputError class="mt-2" :message="createForm.errors.estado" />
            </div>
        </div>
    </form>
</template>
