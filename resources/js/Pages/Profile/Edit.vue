<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import Card from '@/Components/Card.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-zinc-200"
            >
                Perfil
            </h2>
        </template>

        <div class="py-6 space-y-6">
                <!-- Forms Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card class="p-4 sm:p-8">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </Card>

                    <Card class="p-4 sm:p-8">
                        <UpdatePasswordForm class="max-w-xl" />
                    </Card>
                </div>

                <!-- Account Deletion (Without Card) -->
                <div class="px-4 py-8 mt-6">
                    <DeleteUserForm class="max-w-xl" />
                </div>
        </div>
    </AuthenticatedLayout>
</template>
