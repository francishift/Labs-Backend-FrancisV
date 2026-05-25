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
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

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
import { ChevronLeftIcon, PencilIcon, PrinterIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    proyecto: Object,
    pagination: Object,
    pagination: Object,
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

// Modal email
const showEmailModal = ref(false)
const emailForm = useForm({
    email: props.proyecto.client?.email || ''
})

const openEmailModal = () => {
    emailForm.email = props.proyecto.client?.email || ''
    showEmailModal.value = true
}

const closeEmailModal = () => {
    showEmailModal.value = false
    emailForm.reset()
    emailForm.clearErrors()
}

const sendEmail = () => {
    emailForm.post(route('admin.proyectos.send-pdf', props.proyecto.id), {
        preserveScroll: true,
        onSuccess: () => closeEmailModal()
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
                        <SecondaryButton @click="openEmailModal" title="Enviar por email" class="flex items-center">
                            <EnvelopeIcon class="h-4 w-4" />
                        </SecondaryButton>
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
                    @close="closeEditMainModal"
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
                    :close-create-modal="closeCreateModal"
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

        <DialogModal :show="showEmailModal" @close="closeEmailModal">
            <template #title>Enviar Informe por Email</template>
            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Se generará y enviará un correo electrónico con el PDF del proyecto adjunto al destinatario indicado.
                    </p>
                    <div>
                        <InputLabel for="email" value="Dirección de Email" />
                        <TextInput
                            id="email"
                            v-model="emailForm.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="emailForm.errors.email" class="mt-2" />
                    </div>
                </div>
            </template>
            <template #footer>
                <SecondaryButton @click="closeEmailModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" :class="{ 'opacity-25': emailForm.processing }" :disabled="emailForm.processing" @click="sendEmail">
                    Enviar
                </PrimaryButton>
            </template>
        </DialogModal>
    </AuthenticatedLayout>
</template>
