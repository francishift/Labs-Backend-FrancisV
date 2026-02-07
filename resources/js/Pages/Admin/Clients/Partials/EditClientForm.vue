<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { formatDateForInput } from '@/Utils/date';

const props = defineProps({
    client: Object,
    closeEditModal: Function,
});

const editForm = useForm({
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
    secondary_contacts: '',
    excel_created_at: '',
});

// Initialize form with client data when client prop changes
watch(() => props.client, (client) => {
    if (client) {
        editForm.name = client.name;
        editForm.cif_nif = client.cif_nif;
        editForm.email = client.email;
        editForm.phone = client.phone;
        editForm.mobile = client.mobile;
        editForm.address = client.address;
        editForm.city = client.city;
        editForm.zip_code = client.zip_code;
        editForm.province = client.province;
        editForm.country = client.country;
        editForm.contact = client.contact || '';
        editForm.secondary_contacts = Array.isArray(client.secondary_contacts) ? client.secondary_contacts.join(', ') : '';
        editForm.excel_created_at = formatDateForInput(client.excel_created_at);
        editForm.clearErrors();
    }
}, { immediate: true });

const submitEdit = () => {
    if (!props.client) return;
    
    editForm.patch(route('admin.clientes.update', props.client.id), {
        preserveScroll: true,
        onSuccess: () => props.closeEditModal(),
    });
};
</script>

<template>
    <form id="edit-client-form" class="mt-4 space-y-4" @submit.prevent="submitEdit">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2">
                <InputLabel for="edit_name" value="Nombre" />
                <TextInput id="edit_name" v-model="editForm.name" type="text" required class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.name" />
            </div>
            <div>
                <InputLabel for="edit_cif_nif" value="NIF/CIF" />
                <TextInput id="edit_cif_nif" v-model="editForm.cif_nif" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.cif_nif" />
            </div>
            <div>
                <InputLabel for="edit_email" value="Email" />
                <TextInput id="edit_email" v-model="editForm.email" type="email" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.email" />
            </div>
            <div>
                <InputLabel for="edit_phone" value="Teléfono" />
                <TextInput id="edit_phone" v-model="editForm.phone" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.phone" />
            </div>
            <div>
                <InputLabel for="edit_mobile" value="Móvil" />
                <TextInput id="edit_mobile" v-model="editForm.mobile" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.mobile" />
            </div>
            <div class="lg:col-span-2">
                <InputLabel for="edit_address" value="Dirección" />
                <TextInput id="edit_address" v-model="editForm.address" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.address" />
            </div>
            <div>
                <InputLabel for="edit_city" value="Población" />
                <TextInput id="edit_city" v-model="editForm.city" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.city" />
            </div>
            <div>
                <InputLabel for="edit_zip_code" value="C.P." />
                <TextInput id="edit_zip_code" v-model="editForm.zip_code" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.zip_code" />
            </div>
            <div>
                <InputLabel for="edit_province" value="Provincia" />
                <TextInput id="edit_province" v-model="editForm.province" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.province" />
            </div>
            <div>
                <InputLabel for="edit_country" value="País" />
                <TextInput id="edit_country" v-model="editForm.country" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.country" />
            </div>
            <div>
                <InputLabel for="edit_contact" value="Contact (Holded ID)" />
                <TextInput id="edit_contact" v-model="editForm.contact" type="text" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.contact" />
            </div>
            <div class="lg:col-span-2">
                <InputLabel for="edit_secondary_contacts" value="IDs Secundarios Holded (separados por coma)" />
                <TextInput id="edit_secondary_contacts" v-model="editForm.secondary_contacts" type="text" class="mt-1 block w-full" placeholder="ID1, ID2..." />
                <InputError class="mt-2" :message="editForm.errors.secondary_contacts" />
                <p class="text-xs text-gray-500 mt-1">Usar solo si el cliente tiene múltiples fichas en Holded.</p>
            </div>
            <div>
                <InputLabel for="edit_excel_created_at" value="Fecha Creación (Excel)" />
                <TextInput id="edit_excel_created_at" v-model="editForm.excel_created_at" type="date" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="editForm.errors.excel_created_at" />
            </div>
        </div>
    </form>
</template>
