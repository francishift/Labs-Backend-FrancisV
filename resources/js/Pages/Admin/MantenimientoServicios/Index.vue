<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

// Partials
import MantenimientoServiciosTable from './Partials/MantenimientoServiciosTable.vue'
import CreateMantenimientoServicioGlobalForm from './Partials/CreateMantenimientoServicioGlobalForm.vue'
import EditMantenimientoServicioForm from '../Mantenimientos/Partials/EditMantenimientoServicioForm.vue'

// Composables
import { useCRUDModals } from '@/Composables/useCRUDModals'
import { useFilters } from '@/Composables/useFilters'

const props = defineProps({
    servicios: Object,
    filters: Object,
    mantenimientos: Array,
})

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

const { filters } = useFilters({
    search: props.filters.search || '',
    mantenimiento_id: props.filters.mantenimiento_id ? Number(props.filters.mantenimiento_id) : ''
}, 'admin.mantenimiento-servicios.index')

const destroyService = () => {
    if (!itemToDelete.value) return
    useForm({}).delete(route('admin.mantenimiento-servicios.destroy', itemToDelete.value.id), {
        onSuccess: () => closeConfirmModal(),
        preserveScroll: true
    })
}
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
                <div class="mb-6 flex flex-col sm:flex-row gap-4">
                    <div class="w-full sm:w-1/2 md:w-1/3">
                        <SearchInput 
                            id="search-mantenimiento-servicios"
                            name="search-mantenimiento-servicios"
                            v-model="filters.search" 
                            placeholder="Busca por descripción o aplicación..." 
                            class="w-full"
                        />
                    </div>
                    <div class="w-full sm:w-1/2 md:w-1/3">
                        <SearchableSelect 
                            v-model="filters.mantenimiento_id"
                            :options="mantenimientos"
                            label-key="aplicacion"
                            value-key="id"
                            placeholder="Todos los mantenimientos..."
                            class="w-full"
                        />
                    </div>
                </div>

                <MantenimientoServiciosTable 
                    :items="servicios.data"
                    @row-click="openEditModal"
                    @edit="openEditModal"
                    @delete="confirmDelete"
                />

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
