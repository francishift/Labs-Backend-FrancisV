<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import VpnManager from './VpnManager.vue';

const props = defineProps({
    user: Object,
    roles: Array,
    currentUser: Object,
    closeEditModal: Function,
});

const editForm = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
});

watch(() => props.user, (user) => {
    if (user) {
        editForm.name = user.name;
        editForm.email = user.email;
        editForm.role = user.roles?.[0] ?? (props.roles?.[0] ?? 'user');
        editForm.password = '';
        editForm.clearErrors();
    }
}, { immediate: true });

const submitEdit = () => {
    if (!props.user) return;

    editForm.patch(route('admin.usuarios.update', props.user.id), {
        preserveScroll: true,
        onSuccess: () => props.closeEditModal(),
    });
};
</script>

<template>
    <form id="edit-user-form" class="mt-4 space-y-6" @submit.prevent="submitEdit">
        <div>
            <InputLabel for="edit_name" value="Nombre" />
            <TextInput
                id="edit_name"
                v-model="editForm.name"
                type="text"
                required
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="editForm.errors.name" />
        </div>

        <div>
            <InputLabel for="edit_email" value="Email" />
            <TextInput
                id="edit_email"
                v-model="editForm.email"
                type="email"
                required
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="editForm.errors.email" />
        </div>

        <div v-if="user?.id !== currentUser?.id">
            <InputLabel for="edit_role" value="Rol" />
            <SearchableSelect
                id="edit_role"
                v-model="editForm.role"
                required
                class="mt-1 block w-full"
                :options="roles.map(r => ({ id: r, name: r }))"
            />
            <InputError class="mt-2" :message="editForm.errors.role" />
        </div>

        <div>
            <InputLabel for="edit_password" value="Contraseña (opcional)" />
            <TextInput
                id="edit_password"
                v-model="editForm.password"
                type="password"
                autocomplete="off"
                placeholder="Dejar en blanco para no cambiar"
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="editForm.errors.password" />
        </div>

        <!-- Sección de VPN -->
        <VpnManager v-if="user" :user="user" />
    </form>
</template>
