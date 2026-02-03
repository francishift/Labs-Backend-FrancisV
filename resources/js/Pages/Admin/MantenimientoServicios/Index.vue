<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import { watch, ref } from 'vue'
import debounce from 'lodash/debounce'
import { useFormatters } from '@/Composables/useFormatters'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { useCRUDModals } from '@/Composables/useCRUDModals'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'

import CreateMantenimientoServicioGlobalForm from './Partials/CreateMantenimientoServicioGlobalForm.vue'
import EditMantenimientoServicioForm from '../Mantenimientos/Partials/EditMantenimientoServicioForm.vue'

const props = defineProps({
    servicios: Object,
    filters: Object,
    mantenimientos: Array,
})

const { formatDate, formatCurrency } = useFormatters()

const {
    showCreateModal,
    showEditModal,
    showConfirmModal,
    editingItem,
    itemToDelete,
    openCreateModal,
    closeCreateModal,
    openEditModal,
    closeEditModal,
    confirmDelete,
    closeConfirmModal
} = useCRUDModals()

const search = ref(props.filters.search)

watch(search, debounce((value) => {
    router.get(route('admin.mantenimiento-servicios.index'), { search: value }, {
        preserveState: true,
        replace: true
    })
}, 300))

const formatMinutesToHours = (minutes) => {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const destroyService = () => {
    if (!itemToDelete.value) return
    useForm({}).delete(route('admin.mantenimiento-servicios.destroy', itemToDelete.value.id), {
        onSuccess: () => closeConfirmModal(),
        preserveScroll: true
    })
}

const columns = [
    { key: 'fecha', label: 'Fecha' },
    { key: 'descripcion', label: 'Descripción' },
    { key: 'aplicacion', label: 'Cliente' },
    { key: 'duracion_minutos', label: 'Duración', align: 'center' },
    { key: 'actions', label: '', align: 'right' },
]
</script>

<template>
    <Head title="Servicios de Mantenimiento" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader title="Tareas de mantenimiento">
                <template #actions>
                    <PrimaryButton @click="openCreateModal" class="flex items-center gap-2">
                        <PlusIcon class="h-5 w-5" />
                        Registrar Tarea
                    </PrimaryButton>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6">
                <Card class="p-4 sm:p-6">
                    <div class="mb-6">
                        <SearchInput 
                          id="search-mantenimiento-servicios"
                          name="search-mantenimiento-servicios"
                          v-model="search" 
                          placeholder="Busca por descripción o aplicación..." 
                        />
                   </div>

                    <DataTable :columns="columns" :items="servicios.data" @row-click="openEditModal">
                        <template #cell-aplicacion="{ item }">
                            <div class="flex flex-col">
                                <span class="text-gray-900 dark:text-zinc-100">{{ item.mantenimiento?.aplicacion || 'N/A' }}</span>
                                <span class="text-xs text-gray-500 dark:text-zinc-400">{{ item.mantenimiento?.cliente?.name || 'S/N' }}</span>
                            </div>
                        </template>

                        <template #cell-descripcion="{ item }">
                            <span class="text-sm truncate max-w-xs block" :title="item.descripcion">{{ item.descripcion }}</span>
                        </template>

                        <template #cell-duracion_minutos="{ item }">
                            <span class="text-sm font-medium">{{ formatMinutesToHours(item.duracion_minutos) }}</span>
                        </template>

                        <template #cell-fecha="{ item }">
                            <span class="text-sm text-gray-500">{{ formatDate(item.fecha) }}</span>
                        </template>

                        <template #cell-actions="{ item }">
                            <div class="flex justify-end gap-2">
                                <Link :href="route('admin.mantenimientos.show', item.mantenimiento_id)" @click.stop>
                                    <SecondaryButton title="Ver Mantenimiento">
                                        <EyeIcon class="h-4 w-4" />
                                    </SecondaryButton>
                                </Link>
                                <SecondaryButton @click.stop="openEditModal(item)" title="Editar">
                                    <PencilIcon class="h-4 w-4" />
                                </SecondaryButton>
                                <DangerButton @click.stop="confirmDelete(item)" title="Eliminar">
                                    <TrashIcon class="h-4 w-4" />
                                </DangerButton>
                            </div>
                        </template>
                    </DataTable>

                    <div class="mt-6">
                        <Pagination :links="servicios.links" />
                    </div>
                </Card>
        </div>

        <!-- Modals -->
        <DialogModal :show="showCreateModal" @close="closeCreateModal">
            <template #title>Registrar Tarea de Mantenimiento</template>
            <template #content>
                <CreateMantenimientoServicioGlobalForm 
                    :mantenimientos="mantenimientos"
                    @close="closeCreateModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="create-mantenimiento-servicio-global-form">Registrar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="showEditModal" @close="closeEditModal">
            <template #title>Editar Tarea de Mantenimiento</template>
            <template #content>
                <EditMantenimientoServicioForm 
                    v-if="editingItem"
                    :servicio="editingItem"
                    @close="closeEditModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="edit-mantenimiento-servicio-form">Actualizar</PrimaryButton>
            </template>
        </DialogModal>

        <ConfirmModal
            :show="showConfirmModal"
            title="Eliminar Registro"
            content="¿Estás seguro de que deseas eliminar este registro de servicio? Esta acción no se puede deshacer."
            @close="closeConfirmModal"
            @confirm="destroyService"
        />
    </AuthenticatedLayout>
</template>
