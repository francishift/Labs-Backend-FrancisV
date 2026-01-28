<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

</script>

<template>
    <Head title="Resumen horas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                Resumen horas
            </h2>
        </template>

        <div class="py-12">
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-zinc-100">
                    Página de Resumen de Horas (En desarrollo)
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
