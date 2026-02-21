<script setup>
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    roles: Array,
    closeCreateModal: Function,
});

const createForm = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
});

const submitCreate = () => {
    createForm.post(route('admin.usuarios.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset('name', 'email', 'password', 'role');
            props.closeCreateModal(); // Suponiendo que pasarás este control desde el padre, o la lógica permanece aquí
        },
    });
};
</script>

<template>
    <form id="create-user-form" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="submitCreate">
        <div class="md:col-span-1">
            <InputLabel for="name" value="Nombre" />
            <TextInput
                id="name"
                v-model="createForm.name"
                type="text"
                required
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="createForm.errors.name" />
        </div>

        <div class="md:col-span-1">
            <InputLabel for="email" value="Email" />
            <TextInput
                id="email"
                v-model="createForm.email"
                type="email"
                required
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="createForm.errors.email" />
        </div>

        <div class="md:col-span-1">
            <InputLabel for="role" value="Rol" />
            <SearchableSelect
                id="role"
                v-model="createForm.role"
                required
                class="mt-1 block w-full"
                placeholder="Selecciona rol"
                :options="roles.map(r => ({ id: r, name: r }))"
            />
            <InputError class="mt-2" :message="createForm.errors.role" />
        </div>

        <div class="md:col-span-1">
            <InputLabel for="password" value="Contraseña" />
            <TextInput
                id="password"
                v-model="createForm.password"
                type="password"
                required
                autocomplete="off"
                class="mt-1 block w-full"
            />
            <InputError class="mt-2" :message="createForm.errors.password" />
        </div>
        
        <!-- Logic for button is likely handled by parent due to DialogModal structure but the form ID is crucial -->
    </form>
</template>
