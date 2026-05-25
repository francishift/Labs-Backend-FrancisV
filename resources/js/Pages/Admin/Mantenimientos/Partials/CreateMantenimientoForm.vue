<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import CurrencyInput from '@/Components/CurrencyInput.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import AjaxSearchableSelect from '@/Components/AjaxSearchableSelect.vue';
import AjaxMultiSelect from '@/Components/AjaxMultiSelect.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useForm } from '@inertiajs/vue3';
import { getTodayDate } from '@/Utils/date';

const props = defineProps({
    closeCreateModal: Function,
});

const createForm = useForm({
    aplicacion: '',
    url: '',
    client_id: '',
    extensiones: [],
    descripcion: '',
    fecha_inicio: getTodayDate(),
    fecha_fin: '',
    tipo_pago: 'mensual',
    importe: '',
    estado: 'en curso',
});

const submitCreate = () => {
    createForm.post(route('admin.mantenimientos.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            props.closeCreateModal();
        },
    });
};
</script>

<template>
    <form id="create-mantenimiento-form" class="mt-4 space-y-4" @submit.prevent="submitCreate">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <InputLabel for="create_aplicacion" value="Aplicación / Web" />
                <TextInput id="create_aplicacion" v-model="createForm.aplicacion" type="text" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.aplicacion" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="create_url" value="URL del proyecto" />
                <TextInput id="create_url" v-model="createForm.url" type="url" placeholder="https://..." class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.url" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="create_client_id" value="Cliente" />
                <AjaxSearchableSelect
                    id="create_client_id"
                    v-model="createForm.client_id"
                    endpoint="/api/dropdown/clientes"
                    placeholder="Seleccionar cliente..."
                    class="mt-1"
                />
                <InputError class="mt-2" :message="createForm.errors.client_id" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="create_extensiones" value="Extensiones Utilizadas" />
                <AjaxMultiSelect
                    id="create_extensiones"
                    v-model="createForm.extensiones"
                    endpoint="/api/dropdown/extensiones"
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
                <InputLabel for="create_tipo_pago" value="Tipo de Pago" />
                <SearchableSelect
                    id="create_tipo_pago"
                    v-model="createForm.tipo_pago"
                    required
                    class="mt-1 block w-full"
                    :options="[
                        { id: 'mensual', name: 'Mensual' },
                        { id: 'trimestral', name: 'Trimestral' },
                        { id: 'anual', name: 'Anual' }
                    ]"
                />
                <InputError class="mt-2" :message="createForm.errors.tipo_pago" />
            </div>

            <div>
                <InputLabel for="create_importe" value="Importe (€)" />
                <CurrencyInput id="create_importe" v-model="createForm.importe" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.importe" />
            </div>

            <div class="md:col-span-2">
                <InputLabel for="create_estado" value="Estado" />
                <SearchableSelect
                    id="create_estado"
                    v-model="createForm.estado"
                    required
                    class="mt-1 block w-full"
                    :options="[
                        { id: 'en curso', name: 'En curso' },
                        { id: 'finalizado', name: 'Finalizado' }
                    ]"
                />
                <InputError class="mt-2" :message="createForm.errors.estado" />
            </div>
        </div>
    </form>
</template>
