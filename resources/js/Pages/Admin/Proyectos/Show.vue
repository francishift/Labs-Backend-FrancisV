<script setup>
import { ref, computed } from 'vue'
import { useForm, Head, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Pagination from '@/Components/Pagination.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

// Partials
import ProjectInfo from './Partials/ProjectInfo.vue'
import CostSummary from './Partials/CostSummary.vue'
import ServicesTable from './Partials/ServicesTable.vue'
import ExtensionsGrid from './Partials/ExtensionsGrid.vue'
import EditServicioForm from '@/Pages/Admin/Servicios/Partials/EditServicioForm.vue'
import CreateServicioForm from '@/Pages/Admin/Servicios/Partials/CreateServicioForm.vue'
import EditProyectoForm from './Partials/EditProyectoForm.vue'

// Composables
import { useCRUDModals } from '@/Composables/useCRUDModals'
import { ChevronLeftIcon, PencilIcon, PrinterIcon, ArrowDownTrayIcon } from '@heroicons/vue/24/outline'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    proyecto: Object,
    pagination: Object,
    clients: Array,
    availableExtensions: Array,
    stats: Object,
})

const page = usePage()

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
  closeConfirmModal,
} = useCRUDModals()

// Modal para editar el proyecto principal
const isEditMainModalOpen = ref(false)
const openEditMainModal = () => isEditMainModalOpen.value = true
const closeEditMainModal = () => isEditMainModalOpen.value = false

const servicesTotal = computed(() => props.stats?.servicesTotal || 0)

const destroyService = () => {
    if (!itemToDelete.value) return
    useForm({}).delete(route('admin.servicios.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal()
    })
}
</script>

<template>
    <Head :title="`Proyecto: ${proyecto.proyecto}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="proyecto.proyecto">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('admin.proyectos.index')" prefetch>
                            <SecondaryButton title="Volver a todos los proyectos" class="flex items-center">
                                <ChevronLeftIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>
                        <PrimaryButton @click="openEditMainModal" title="Editar Proyecto" class="flex items-center">
                            <PencilIcon class="h-4 w-4" />
                        </PrimaryButton>
                        <Link 
                            :href="route('admin.visor-pdf', { 
                                url: route('admin.proyectos.pdf', proyecto.id),
                                title: `Proyecto: ${proyecto.proyecto}`,
                                backUrl: route('admin.proyectos.show', proyecto.id)
                            })"
                        >
                            <SecondaryButton title="Imprimir PDF" class="flex items-center gap-2">
                                <PrinterIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>

                        <a :href="route('admin.proyectos.pdf', { proyecto: proyecto.id, download: 1 })">
                            <SecondaryButton title="Descargar Informe PDF" class="flex items-center gap-2">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </a>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6">
            <!-- Top Section: Overview & Costs -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Project Info Partial -->
                <ProjectInfo :proyecto="proyecto" :stats="stats" />

                <!-- Cost Summary Partial -->
                <CostSummary :proyecto="proyecto" :stats="stats" />
            </div>

            <!-- Services Table Partial -->
            <ServicesTable 
                :servicios="proyecto.servicios" 
                :services-total="servicesTotal"
                @create="openCreateModal"
                @edit="openEditModal"
                @delete="confirmDelete"
            />

            <!-- Extensions Grid Partial -->
            <ExtensionsGrid :extensiones="proyecto.extensiones" />

            <!-- Pagination -->
            <div class="flex justify-center pb-8 pt-6">
                <Pagination :links="pagination.links" prefetch />
            </div>
        </div>

        <!-- Modals -->
        <DialogModal :show="isEditMainModalOpen" @close="closeEditMainModal">
            <template #title>Editar Proyecto</template>
            <template #content>
                <EditProyectoForm 
                    :proyecto="proyecto"
                    :clients="clients"
                    :available-extensions="availableExtensions"
                    :close-edit-modal="closeEditMainModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditMainModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="edit-proyecto-form">Actualizar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="showCreateModal" @close="closeCreateModal">
            <template #title>Registrar Servicio</template>
            <template #content>
                <CreateServicioForm 
                    :proyecto-id="proyecto.id"
                    @close="closeCreateModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="create-servicio-form">Registrar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="showEditModal" @close="closeEditModal">
            <template #title>Editar Servicio</template>
            <template #content>
                <EditServicioForm 
                    v-if="editingItem"
                    :servicio="editingItem"
                    :proyectos="$page.props.proyectos_list"
                    :hide-proyecto-selector="true"
                    :close-edit-modal="closeEditModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="edit-servicio-form">Actualizar</PrimaryButton>
            </template>
        </DialogModal>

        <ConfirmModal
            :show="showConfirmModal"
            title="Eliminar Servicio"
            content="¿Estás seguro de que deseas eliminar este registro de servicio? Esta acción no se puede deshacer."
            @close="closeConfirmModal"
            @confirm="destroyService"
        />
    </AuthenticatedLayout>
</template>
