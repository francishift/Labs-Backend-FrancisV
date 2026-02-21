<script setup>
import { ref } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PageHeader from '@/Components/PageHeader.vue'
import DangerButton from '@/Components/DangerButton.vue'

const isRefreshing = ref(false);

const refreshLogs = () => {
    isRefreshing.value = true;
    
    // Get the ID of the newest log currently loaded, or 0 if empty
    const lastLogId = props.logs?.data?.length > 0 ? props.logs.data[0].id : 0;

    router.get(route('admin.logs.index'), { last_log_id: lastLogId }, {
        preserveScroll: true,
        preserveState: true,
        only: ['logs', 'flash'],
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

import ConfirmModal from '@/Components/ConfirmModal.vue'

const showConfirmModal = ref(false);

const confirmClearLogs = () => {
    showConfirmModal.value = true;
};

const closeConfirmModal = () => {
    showConfirmModal.value = false;
};

const clearLogs = () => {
    router.delete(route('admin.logs.clear'), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal(),
        onFinish: () => closeConfirmModal()
    });
};

import Card from '@/Components/Card.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import Pagination from '@/Components/Pagination.vue'
import { ArrowPathIcon, ComputerDesktopIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: Object,
})

const columns = [
  { key: 'created_at', label: 'Fecha' },
  { key: 'user', label: 'Usuario (Admin)' },
  { key: 'action', label: 'Acción' },
  { key: 'details', label: 'Detalles' },
  { key: 'ip_address', label: 'IP Origen' },
]

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
};

const actionLabels = {
    'CREATE_SUCCESS': 'Creación Exitosa',
    'CREATE_FAIL': 'Fallo Creación',
    'DELETE_ATTEMPT': 'Solicitud Borrado',
    'DELETE_SUCCESS_DB': 'Borrado en DB',
    'WG_REMOVE_SUCCESS': 'Borrado Wireguard',
    'WG_REMOVE_FAIL': 'Fallo Wireguard',
    'DELETE_CRITICAL_FAIL': 'Error Crítico',
    'WG_ADD_FAIL': 'Fallo Añadir WG',
    'USER_LOGIN': 'Inicio de Sesión',
    'USER_LOGOUT': 'Cierre de Sesión'
};

const formatActionName = (action) => {
    return actionLabels[action] || action;
};

const getActionVariant = (action) => {
    if (action.includes('FAIL') || action.includes('ERROR')) return 'red';
    if (action.includes('DELETE') || action.includes('REMOVE')) return 'amber';
    if (action.includes('CREATE') || action.includes('ADD')) return 'green';
    if (action === 'USER_LOGIN') return 'cyan';
    return 'zinc';
};
</script>

<template>
    <Head title="Logs VPN" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Logs de Auditoría VPN">
                <template #actions>
                    <div class="flex items-center gap-2">
                        <DangerButton @click="confirmClearLogs" class="flex items-center gap-2" title="Vaciar Logs">
                            <TrashIcon class="w-4 h-4" />
                            Vaciar
                        </DangerButton>
                        <button @click="refreshLogs" class="p-2 text-gray-500 transition-colors hover:text-gray-700 dark:hover:text-gray-300" :disabled="isRefreshing" title="Actualizar">
                            <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': isRefreshing }" />
                        </button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6">
            <Card class="p-4 sm:p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                  <h3 class="text-lg font-medium text-gray-900 dark:text-white">Últimos eventos</h3>
                </div>

                <div class="mt-4">
                    <DataTable
                        :columns="columns"
                        :items="logs.data"
                    >
                        <template #cell-created_at="{ item }">
                            <span class="whitespace-nowrap">{{ formatDate(item.created_at) }}</span>
                        </template>

                        <template #cell-user="{ item }">
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ item.user ? item.user.name : 'Sistema / Desconocido' }}
                            </span>
                        </template>

                        <template #cell-action="{ item }">
                            <Badge :variant="getActionVariant(item.action)">
                                {{ formatActionName(item.action) }}
                            </Badge>
                        </template>

                        <template #cell-details="{ item }">
                            <div class="max-w-md truncate" :title="item.details">
                                {{ item.details }}
                                <div class="flex flex-col gap-1 mt-1">
                                    <div v-if="item.target_device_id" class="text-xs text-gray-400">
                                        ID Disp: {{ item.target_device_id }}
                                    </div>
                                    <div v-if="item.user_agent" class="flex items-center gap-1 text-xs text-gray-400" :title="item.user_agent">
                                        <ComputerDesktopIcon class="w-3 h-3" />
                                        <span class="truncate max-w-[200px]">{{ item.user_agent }}</span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <Pagination :links="logs.links" />
            </Card>
        </div>

        <ConfirmModal
          :show="showConfirmModal"
          title="Vaciar Logs"
          content="¿Estás seguro de que deseas vaciar todo el historial de logs? Esta acción no se puede deshacer."
          confirm-text="Sí, vaciar"
          cancel-text="Cancelar"
          @close="closeConfirmModal"
          @confirm="clearLogs"
        />
    </AuthenticatedLayout>
</template>
