<script setup>
import { computed, ref, watch } from 'vue'
import { router, Head, Link, usePage, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import Pagination from '@/Components/Pagination.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import { ChevronLeftIcon, PencilIcon, PrinterIcon, ArrowDownTrayIcon, EnvelopeIcon } from '@heroicons/vue/24/outline'

// Partials
import PeriodFilters from './Partials/PeriodFilters.vue'
import MantenimientoInfo from './Partials/MantenimientoInfo.vue'
import MaintenanceBalance from './Partials/MaintenanceBalance.vue'
import MaintenanceServicesTable from './Partials/MaintenanceServicesTable.vue'
import MaintenanceExtensionsGrid from './Partials/MaintenanceExtensionsGrid.vue'
import CreateMantenimientoServicioForm from './Partials/CreateMantenimientoServicioForm.vue'
import EditMantenimientoServicioForm from './Partials/EditMantenimientoServicioForm.vue'
import EditMantenimientoForm from './Partials/EditMantenimientoForm.vue'

// Composables
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
    mantenimiento: Object,
    servicios: Object,
    stats: Object,
    aniosDisponibles: Array,
    pagination: Object,
    clients: Array,
    availableExtensions: Array,
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
    closeConfirmModal
} = useCRUDModals()

// Modal para editar el mantenimiento principal
const isEditMainModalOpen = ref(false)
const openEditMainModal = () => isEditMainModalOpen.value = true
const closeEditMainModal = () => isEditMainModalOpen.value = false

const month = ref(props.stats.periodo.month)
const year = ref(props.stats.periodo.year)

const months = [
    { value: 1, label: 'Enero' },
    { value: 2, label: 'Febrero' },
    { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Mayo' },
    { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' },
    { value: 'all', label: 'Todo el año' },
]

watch([month, year], () => {
    router.get(route('admin.mantenimientos.show', props.mantenimiento.id), {
        month: month.value,
        year: year.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    })
})

const precioHoraConDescuento = computed(() => {
    const precio = page.props.config?.precio_hora || 0
    const descuento = page.props.config?.descuento_mantenimiento || 0
    return precio * (1 - (descuento / 100))
})

const calculateExtensionPeriodCost = (extension) => {
    const isAnnualView = month.value === 'all'
    const precio = parseFloat(extension.pivot?.precio_aplicado || extension.precio)
    const tipo = (extension.tipo_licencia || '').toLowerCase()

    if (isAnnualView) {
        if (tipo === 'mensual') return precio * 12
        return precio
    } else {
        if (tipo === 'mensual') return precio
        return precio / 12
    }
}

const destroyService = () => {
    if (!itemToDelete.value) return
    useForm({}).delete(route('admin.mantenimiento-servicios.destroy', itemToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal()
    })
}

// Modal email
const showEmailModal = ref(false)
const emailForm = useForm({
    email: props.mantenimiento.cliente?.email || ''
})

const openEmailModal = () => {
    emailForm.email = props.mantenimiento.cliente?.email || ''
    showEmailModal.value = true
}

const closeEmailModal = () => {
    showEmailModal.value = false
    emailForm.reset()
    emailForm.clearErrors()
}

const sendEmail = () => {
    emailForm.post(route('admin.mantenimientos.send-pdf', {
        mantenimiento: props.mantenimiento.id,
        month: month.value,
        year: year.value
    }), {
        preserveScroll: true,
        onSuccess: () => closeEmailModal()
    })
}
</script>

<template>
    <Head :title="`Mantenimiento: ${mantenimiento.aplicacion}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="mantenimiento.aplicacion">
                <template #actions>
                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('admin.mantenimientos.index')" prefetch>
                            <SecondaryButton title="Volver a todos los mantenimientos" class="flex items-center">
                                <ChevronLeftIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>
                        <PrimaryButton @click="openEditMainModal" title="Editar Mantenimiento" class="flex items-center">
                            <PencilIcon class="h-4 w-4" />
                        </PrimaryButton>

                        <SecondaryButton @click="openEmailModal" title="Enviar por email" class="flex items-center">
                            <EnvelopeIcon class="h-4 w-4" />
                        </SecondaryButton>

                        <Link 
                            :href="route('admin.visor-pdf', { 
                                url: route('admin.mantenimientos.pdf', { mantenimiento: mantenimiento.id, month, year }),
                                title: `Mantenimiento: ${mantenimiento.aplicacion}`,
                                backUrl: route('admin.mantenimientos.show', { mantenimiento: mantenimiento.id, month, year })
                            })"
                        >
                            <SecondaryButton title="Imprimir PDF" class="flex items-center">
                                <PrinterIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6">
            <!-- Period Filters -->
            <PeriodFilters 
                v-model:month="month" 
                v-model:year="year" 
                :months="months" 
                :aniosDisponibles="aniosDisponibles" 
            />
            
            <!-- Top Section: Overview & Costs -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Info Partial -->
                <MantenimientoInfo 
                    :mantenimiento="mantenimiento" 
                    :total-income="stats.ingreso" 
                    :stats="stats" 
                />

                <!-- Balance Partial -->
                <MaintenanceBalance 
                    :total-income="stats.ingreso"
                    :total-extensions-cost="stats.coste_extensiones"
                    :total-software-cost="stats.coste_software"
                    :total-cost="stats.coste_servicios"
                    :balance="stats.balance"
                />
            </div>

            <!-- Services Table Partial -->
            <MaintenanceServicesTable 
                :servicios="servicios" 
                :total-cost="stats.coste_servicios"
                :month-label="months.find(m => m.value == month)?.label"
                :year="year"
                :mantenimiento="mantenimiento"
                :precio-hora-con-descuento="precioHoraConDescuento"
                @create="openCreateModal"
                @edit="openEditModal"
                @delete="confirmDelete"
            />

            <!-- Extensions Partial -->
            <MaintenanceExtensionsGrid 
                :extensiones="mantenimiento.extensiones" 
                :month="month"
                :calculate-extension-period-cost="calculateExtensionPeriodCost"
            />

            <!-- Pagination for record navigation -->
            <div class="flex justify-center pb-8 pt-6">
                <Pagination :links="pagination.links" prefetch />
            </div>
        </div>

        <!-- Modals -->
        <DialogModal :show="isEditMainModalOpen" @close="closeEditMainModal">
            <template #title>Editar Mantenimiento</template>
            <template #content>
                <EditMantenimientoForm 
                    :mantenimiento="mantenimiento"
                    :clients="clients"
                    :available-extensions="availableExtensions"
                    :close-edit-modal="closeEditMainModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditMainModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="edit-mantenimiento-form">Actualizar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="showCreateModal" @close="closeCreateModal">
            <template #title>Registrar Servicio de Mantenimiento</template>
            <template #content>
                <CreateMantenimientoServicioForm 
                    :mantenimiento-id="mantenimiento.id"
                    :on-close="closeCreateModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="create-mantenimiento-servicio-form">Registrar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="showEditModal" @close="closeEditModal">
            <template #title>Editar Servicio de Mantenimiento</template>
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
                        Se generará y enviará un correo electrónico con el PDF del mantenimiento adjunto al destinatario indicado.
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
