<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useForm } from '@inertiajs/vue3';
import { getTodayDate } from '@/Utils/date';

const props = defineProps({
    closeCreateModal: Function,
});

const createForm = useForm({
    name: '',
    cif_nif: '',
    email: '',
    phone: '',
    mobile: '',
    address: '',
    city: '',
    zip_code: '',
    province: '',
    country: '',
    contact: '',
    excel_created_at: getTodayDate(),
});

const submitCreate = () => {
    createForm.post(route('admin.clientes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            props.closeCreateModal();
        },
    });
};
</script>

<template>
    <form id="create-client-form" class="mt-4 space-y-4" @submit.prevent="submitCreate">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <InputLabel for="create_name" value="Nombre" />
                <TextInput id="create_name" v-model="createForm.name" type="text" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.name" />
            </div>
            <div>
                <InputLabel for="create_cif_nif" value="NIF/CIF" />
                <TextInput id="create_cif_nif" v-model="createForm.cif_nif" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.cif_nif" />
            </div>
            <div>
                <InputLabel for="create_email" value="Email" />
                <TextInput id="create_email" v-model="createForm.email" type="email" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.email" />
            </div>
            <div>
                <InputLabel for="create_phone" value="Teléfono" />
                <TextInput id="create_phone" v-model="createForm.phone" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.phone" />
            </div>
            <div>
                <InputLabel for="create_mobile" value="Móvil" />
                <TextInput id="create_mobile" v-model="createForm.mobile" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.mobile" />
            </div>
            <div class="lg:col-span-2">
                <InputLabel for="create_address" value="Dirección" />
                <TextInput id="create_address" v-model="createForm.address" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.address" />
            </div>
            <div>
                <InputLabel for="create_city" value="Población" />
                <TextInput id="create_city" v-model="createForm.city" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.city" />
            </div>
            <div>
                <InputLabel for="create_zip_code" value="C.P." />
                <TextInput id="create_zip_code" v-model="createForm.zip_code" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.zip_code" />
            </div>
            <div>
                <InputLabel for="create_province" value="Provincia" />
                <TextInput id="create_province" v-model="createForm.province" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.province" />
            </div>
            <div>
                <InputLabel for="create_country" value="País" />
                <TextInput id="create_country" v-model="createForm.country" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.country" />
            </div>
            <div>
                <InputLabel for="create_contact" value="Contact (Holded ID)" />
                <TextInput id="create_contact" v-model="createForm.contact" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.contact" />
            </div>
            <div>
                <InputLabel for="create_excel_created_at" value="Fecha Creación (Excel)" />
                <TextInput id="create_excel_created_at" v-model="createForm.excel_created_at" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="createForm.errors.excel_created_at" />
            </div>
        </div>
        <!-- Hidden button to handle form submission via Enter key globally in the modal context if needed, though DialogModal handles external button -->
    </form>
</template>
