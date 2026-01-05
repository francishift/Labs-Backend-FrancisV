<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, usePage, useForm } from '@inertiajs/vue3'
import { useFormatters } from '@/Composables/useFormatters'
import PageHeader from '@/Components/PageHeader.vue'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Pagination from '@/Components/Pagination.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import { useCRUDModals } from '@/Composables/useCRUDModals'
import { ChevronLeftIcon, PencilIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline'

import CreateMantenimientoServicioForm from './Partials/CreateMantenimientoServicioForm.vue'
import EditMantenimientoServicioForm from './Partials/EditMantenimientoServicioForm.vue'
import EditMantenimientoForm from './Partials/EditMantenimientoForm.vue'

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
const { formatDate, formatCurrency } = useFormatters()

const {
    showCreateModal: isCreateModalOpen,
    showEditModal: isEditModalOpen,
    showConfirmModal: isConfirmModalOpen,
    editingItem: editingService,
    itemToDelete: serviceToDelete,
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

const getStatusVariant = (status) => {
    switch (status) {
        case 'en curso': return 'green'
        case 'finalizado': return 'red'
        default: return 'gray'
    }
}

const precioHoraConDescuento = computed(() => {
    const precio = page.props.config?.precio_hora || 0
    const descuento = page.props.config?.descuento_mantenimiento || 0
    return precio * (1 - (descuento / 100))
})

const calculateServiceCost = (service) => {
    return (service.duracion_minutos / 60) * precioHoraConDescuento.value
}

const calculateExtensionPeriodCost = (extension) => {
    const isAnnualView = month.value === 'all'
    const precio = parseFloat(extension.precio)
    const tipo = (extension.tipo_licencia || '').toLowerCase()

    if (isAnnualView) {
        if (tipo === 'mensual') return precio * 12
        return precio // Anual o Pago único
    } else {
        if (tipo === 'mensual') return precio
        return precio / 12 // Anual o Pago único prorrateado
    }
}

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

const totalCost = computed(() => props.stats.coste_servicios || 0)
const totalExtensionsCost = computed(() => props.stats.coste_extensiones || 0)
const totalIncome = computed(() => props.stats.ingreso || 0)
const balance = computed(() => props.stats.balance || 0)

const formatMinutesToHours = (minutes) => {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const destroyService = () => {
    if (!serviceToDelete.value) return
    useForm({}).delete(route('admin.mantenimiento-servicios.destroy', serviceToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal()
    })
}
</script>

<template>
    <Head :title="`Mantenimiento: ${mantenimiento.aplicacion}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="mantenimiento.aplicacion" inline>
                <template #actions>
                    <div class="flex gap-2">
                        <Link :href="route('admin.mantenimientos.index')">
                            <SecondaryButton :title="`Volver a todos los mantenimientos`" class="flex items-center">
                                <ChevronLeftIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>
                        <PrimaryButton @click="openEditMainModal" title="Editar Mantenimiento" class="flex items-center">
                            <PencilIcon class="h-4 w-4" />
                        </PrimaryButton>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6">
                
                <!-- Period Filters -->
                <Card class="p-4 flex flex-col sm:flex-row items-center gap-4 bg-zinc-50/50 dark:bg-zinc-800/10">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Período:</span>
                        <select v-model="month" class="rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-gray-500 focus:border-gray-500 dark:text-zinc-200">
                            <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <select v-model="year" class="rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-sm focus:ring-gray-500 focus:border-gray-500 dark:text-zinc-200">
                            <option v-for="y in aniosDisponibles" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div class="flex-1"></div>
                    <div class="text-xs text-gray-500 dark:text-zinc-500 italic">
                        Mostrando datos de {{ months.find(m => m.value === month)?.label }} {{ year }}
                    </div>
                </Card>
                
                <!-- Maintenance Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="lg:col-span-2 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Información del Mantenimiento</h3>
                            <Badge :variant="getStatusVariant(mantenimiento.estado)">
                                {{ mantenimiento.estado }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Cliente</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200">{{ mantenimiento.cliente?.name || 'S/N' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Importe Bruto</p>
                                <p class="text-xl font-black text-emerald-900 dark:text-emerald-200">{{ formatCurrency(mantenimiento.importe) }} <span class="text-xs font-normal opacity-70">({{ mantenimiento.tipo_pago }})</span></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Inicio</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200">{{ formatDate(mantenimiento.fecha_inicio) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Fin</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200">{{ mantenimiento.fecha_fin ? formatDate(mantenimiento.fecha_fin) : 'Activo' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Descripción</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200 whitespace-pre-wrap">{{ mantenimiento.descripcion || 'Sin descripción' }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-6 bg-zinc-50 dark:bg-zinc-800/20 border-zinc-100 dark:border-zinc-800/50">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Balance del Período</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-zinc-400">Ingresos (Cuota)</span>
                                <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ formatCurrency(totalIncome) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-zinc-400">Coste Extensiones</span>
                                <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(totalExtensionsCost) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600 dark:text-zinc-400">Coste Servicios</span>
                                <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(totalCost) }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-zinc-700 pt-3 flex justify-between items-center">
                                <span class="text-base font-bold text-gray-900 dark:text-white">Balance Neto</span>
                                <span class="text-xl font-black" :class="balance >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ formatCurrency(balance) }}
                                </span>
                            </div>
                            <div v-if="totalIncome > 0" class="mt-4">
                                <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Rentabilidad</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-2.5">
                                    <div 
                                        class="h-2.5 rounded-full" 
                                        :class="balance < 0 ? 'bg-red-500' : 'bg-emerald-600'" 
                                        :style="{ width: Math.min((Math.abs(balance) / totalIncome) * 100, 100) + '%' }"
                                    ></div>
                                </div>
                                <p class="text-right text-xs mt-1" :class="balance < 0 ? 'text-red-600' : 'text-emerald-600'">
                                    Margen: {{ ((balance / totalIncome) * 100).toFixed(1) }}% de la cuota
                                </p>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Services Table -->
                <Card class="p-6 overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Servicios de Mantenimiento Realizados</h3>
                        <PrimaryButton @click="openCreateModal" class="flex items-center gap-2">
                            <PlusIcon class="h-4 w-4" />
                            Registrar Servicio
                        </PrimaryButton>
                    </div>
                    
                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Descripción</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Fecha</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Duración</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Coste Est.</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900/50 divide-y divide-gray-200 dark:divide-zinc-800">
                                <tr v-for="service in servicios.data" :key="service.id" class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-zinc-200 whitespace-pre-wrap">{{ service.descripcion }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-zinc-400">
                                        {{ formatDate(service.fecha) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-zinc-200">
                                        {{ formatMinutesToHours(service.duracion_minutos) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400 font-medium">
                                        {{ formatCurrency(calculateServiceCost(service)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <SecondaryButton @click="openEditModal(service)" title="Editar">
                                                <PencilIcon class="h-4 w-4" />
                                            </SecondaryButton>
                                            <DangerButton @click="confirmDelete(service)" title="Eliminar">
                                                <TrashIcon class="h-4 w-4" />
                                            </DangerButton>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!servicios.data?.length">
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-zinc-500 italic">
                                        No se han registrado servicios para este mantenimiento.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="servicios.data?.length" class="bg-gray-50 dark:bg-zinc-800/50">
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-left sm:text-right">
                                        <div class="inline-flex flex-col sm:flex-row sm:items-baseline sm:justify-end gap-x-3">
                                            <span class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-widest">Total Coste Servicios</span>
                                            <span class="text-lg font-black text-red-600 dark:text-red-400 leading-none">{{ formatCurrency(totalCost) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-4">
                        <Pagination v-if="servicios.links?.length > 3" :links="servicios.links" />
                    </div>
                </Card>

                <!-- Associated Extensions -->
                <Card v-if="mantenimiento.extensiones?.length" class="p-6 overflow-hidden">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Extensiones de Terceros en Uso</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="extension in mantenimiento.extensiones" :key="extension.id" class="p-4 border border-gray-200 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800/50">
                            <div class="font-bold text-gray-900 dark:text-zinc-100">{{ extension.nombre }}</div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold text-lg">
                                    {{ formatCurrency(calculateExtensionPeriodCost(extension)) }}
                                </span>
                                <span class="text-[10px] text-gray-500 dark:text-zinc-500 uppercase font-bold tracking-tighter">
                                    {{ month === 'all' ? 'Anual' : 'Mensual' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Record Navigation Pagination -->
                <div class="flex justify-center pb-8 pt-6">
                    <div class="text-center">
                        <Pagination :links="pagination.links" />
                    </div>
                </div>

            </div>

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

        <!-- Modals for Services -->
        <DialogModal :show="isCreateModalOpen" @close="closeCreateModal">
            <template #title>Registrar Servicio de Mantenimiento</template>
            <template #content>
                <CreateMantenimientoServicioForm 
                    :mantenimiento-id="mantenimiento.id"
                    @close="closeCreateModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="create-mantenimiento-servicio-form">Registrar</PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="isEditModalOpen" @close="closeEditModal">
            <template #title>Editar Servicio de Mantenimiento</template>
            <template #content>
                <EditMantenimientoServicioForm 
                    v-if="editingService"
                    :servicio="editingService"
                    @close="closeEditModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="edit-mantenimiento-servicio-form">Actualizar</PrimaryButton>
            </template>
        </DialogModal>

        <ConfirmModal
            :show="isConfirmModalOpen"
            title="Eliminar Servicio"
            content="¿Estás seguro de que deseas eliminar este registro de servicio? Esta acción no se puede deshacer."
            @close="closeConfirmModal"
            @confirm="destroyService"
        />
    </AuthenticatedLayout>
</template>
