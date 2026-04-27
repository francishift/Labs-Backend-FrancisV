<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { BellIcon, CheckCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useFormatters } from '@/Composables/useFormatters';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import axios from 'axios';

const props = defineProps({
    notifications: Object,
});

const { formatDateTime } = useFormatters();

const markAsRead = async (id, url) => {
    try {
        await axios.patch(`/admin/api/notifications/${id}/read`);
        if (url) {
            router.visit(url);
        } else {
            router.reload({ only: ['notifications'] });
        }
    } catch (e) {
        console.error(e);
    }
};

const markAllAsRead = () => {
    router.post(route('admin.notifications.markAllRead'));
};

const destroyNotification = (id) => {
    if (confirm('¿Eliminar esta notificación permanentemente?')) {
        router.delete(route('admin.notifications.destroy', id), { preserveScroll: true });
    }
};

const destroyAll = () => {
    if (confirm('¿Estás seguro de que quieres eliminar TODAS las notificaciones de tu historial? Esta acción no se puede deshacer.')) {
        router.delete(route('admin.notifications.destroyAll'));
    }
};
</script>

<template>
    <Head title="Notificaciones" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                    Centro de Notificaciones
                </h2>
                <div class="flex gap-2">
                    <SecondaryButton @click="markAllAsRead" v-if="notifications.data.length > 0">
                        Marcar todas como leídas
                    </SecondaryButton>
                    <DangerButton @click="destroyAll" v-if="notifications.data.length > 0">
                        Vaciar Historial
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200 dark:divide-zinc-800">
                        <li v-if="notifications.data.length === 0" class="p-6 text-center text-gray-500 dark:text-zinc-400">
                            No tienes ninguna notificación almacenada en tu historial.
                        </li>
                        
                        <li 
                            v-for="notification in notifications.data" 
                            :key="notification.id"
                            class="p-4 sm:px-6 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition duration-150 ease-in-out"
                            :class="{'bg-emerald-50/50 dark:bg-emerald-900/10': !notification.read_at}"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <div class="shrink-0 mt-1">
                                        <BellIcon v-if="notification.read_at" class="h-6 w-6 text-gray-400 dark:text-zinc-500" />
                                        <BellIcon v-else class="h-6 w-6 text-emerald-500" />
                                    </div>
                                    <div class="ml-4">
                                        <p 
                                            class="text-sm font-medium"
                                            :class="notification.read_at ? 'text-gray-600 dark:text-zinc-400' : 'text-gray-900 dark:text-zinc-100'"
                                        >
                                            {{ notification.data.title || 'Alerta del Sistema' }}
                                        </p>
                                        <p 
                                            class="mt-1 text-sm text-gray-500 dark:text-zinc-400"
                                        >
                                            {{ notification.data.message || '' }}
                                        </p>
                                        <p class="mt-2 text-xs text-gray-400 dark:text-zinc-500">
                                            {{ formatDateTime(notification.created_at) }}
                                        </p>
                                        
                                        <div class="mt-4 flex items-center gap-4">
                                            <button 
                                                v-if="notification.data.url"
                                                @click="markAsRead(notification.id, notification.data.url)" 
                                                class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 font-medium transition-colors"
                                            >
                                                Ver detalle &rarr;
                                            </button>

                                            <button 
                                                @click="destroyNotification(notification.id)"
                                                class="text-sm flex items-center text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium transition-colors"
                                                title="Eliminar permanentemente"
                                            >
                                                <TrashIcon class="h-4 w-4 mr-1" />
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-if="!notification.read_at" class="ml-4 shrink-0 flex flex-col items-end">
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 dark:bg-emerald-500/20 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:text-emerald-300">
                                        Nueva
                                    </span>
                                </div>
                            </div>
                        </li>
                    </ul>
                    
                    <!-- Paginación Simple Automática de Laravel/Inertia -->
                    <div v-if="notifications.links" class="p-4 border-t border-gray-200 dark:border-zinc-800">
                        <div class="flex flex-wrap gap-1 justify-center">
                            <template v-for="(link, p) in notifications.links" :key="p">
                                <Link 
                                    v-if="link.url"
                                    :href="link.url"
                                    class="px-4 py-2 text-sm border rounded-md"
                                    :class="link.active ? 'bg-emerald-500 text-white border-emerald-500' : 'bg-white dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 border-gray-300 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-700'"
                                    v-html="link.label"
                                />
                                <span 
                                    v-else 
                                    class="px-4 py-2 text-sm border rounded-md bg-gray-100 dark:bg-zinc-900 border-gray-200 dark:border-zinc-800 text-gray-400"
                                    v-html="link.label"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
