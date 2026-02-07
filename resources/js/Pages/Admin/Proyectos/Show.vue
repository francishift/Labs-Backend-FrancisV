<script setup>
import { ref, computed } from 'vue'
import { useForm, Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
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
import EditServicioForm from '@/Pages/Admin/Servicios/Partials/EditServicioForm.vue'
import CreateServicioForm from '@/Pages/Admin/Servicios/Partials/CreateServicioForm.vue'
import EditProyectoForm from './Partials/EditProyectoForm.vue'
import { useCRUDModals } from '@/Composables/useCRUDModals'

import { ChevronLeftIcon, PencilIcon, PlusIcon, TrashIcon, PrinterIcon, ArrowDownTrayIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    proyecto: Object,
    pagination: Object,
    clients: Array,
    availableExtensions: Array,
    stats: Object,
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
  closeConfirmModal,
} = useCRUDModals()

// Modal para editar el proyecto principal
const isEditMainModalOpen = ref(false)
const openEditMainModal = () => isEditMainModalOpen.value = true
const closeEditMainModal = () => isEditMainModalOpen.value = false

const getStatusVariant = (status) => {
    switch (status) {
        case 'En proceso': return 'green'
        case 'Finalizado': return 'red'
        case 'Cancelado': return 'gray'
        default: return 'gray'
    }
}

// Los cálculos ahora se realizan centralizadamente en el modelo Proyecto (backend)
const servicesTotal = computed(() => props.stats?.servicesTotal || 0)
const extensionsTotal = computed(() => props.stats?.extensionsTotal || 0)
const grandTotal = computed(() => props.stats?.grandTotal || 0)

const formatMinutesToHours = (minutes) => {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return m > 0 ? `${h}h ${m}min` : `${h}h`
}

const destroyService = () => {
    if (!serviceToDelete.value) return
    useForm({}).delete(route('admin.servicios.destroy', serviceToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeConfirmModal()
    })
}
</script>

<template>
    <Head :title="`Proyecto: ${proyecto.proyecto}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="proyecto.proyecto" inline>
                <template #actions>
                    <div class="flex gap-2">
                        <Link :href="route('admin.proyectos.index')" prefetch>
                            <SecondaryButton :title="`Volver a todos los proyectos`" class="flex items-center">
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
                
                <!-- Project Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <Card class="lg:col-span-2 p-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Información General</h3>
                            <Badge :variant="getStatusVariant(proyecto.estado)">
                                {{ proyecto.estado }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-8">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Cliente</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200">{{ proyecto.client?.name || 'S/N' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Presupuesto</p>
                                <p class="text-xl font-black text-emerald-900 dark:text-emerald-200">{{ formatCurrency(proyecto.presupuesto) }}</p>
                                <div v-if="proyecto.presupuesto_asociado" class="mt-1">
                                     <a :href="route('admin.visor-pdf', { 
                                            url: route('admin.holded.presupuestos.pdf', { id: proyecto.presupuesto_asociado.holded_id }),
                                            title: `Presupuesto: ${proyecto.presupuesto_asociado.raw_data?.docNumber || proyecto.presupuesto_asociado.holded_id}`,
                                            backUrl: route('admin.proyectos.show', proyecto.id)
                                        })" 
                                        class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium transition-colors"
                                        title="Ver Presupuesto PDF"
                                     >
                                        <DocumentTextIcon class="h-3 w-3" />
                                        Ref: {{ proyecto.presupuesto_asociado.raw_data?.docNumber || 'Ver PDF' }}
                                     </a>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Inicio</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200">{{ formatDate(proyecto.fecha_inicio) }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Fecha de Fin</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200">{{ proyecto.fecha_fin ? formatDate(proyecto.fecha_fin) : 'Pendiente' }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Descripción</p>
                                <p class="text-base text-gray-900 dark:text-zinc-200 whitespace-pre-wrap">{{ proyecto.descripcion || 'Sin descripción' }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-4 sm:p-6 bg-emerald-50 dark:bg-zinc-800/20 border-emerald-100 dark:border-emerald-800/50">
                        <h3 class="text-lg font-bold text-emerald-900 dark:text-emerald-400 mb-4">Resumen de Costes</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-emerald-700 dark:text-emerald-300">Total Horas</span>
                                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ stats.formattedTime }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm border-b border-emerald-100 dark:border-emerald-800/50 pb-2">
                                <span class="text-emerald-700 dark:text-emerald-300 font-bold italic">Total €/hora</span>
                                <span class="font-bold text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.hoursCostTotal) }}</span>
                            </div>
                            <div v-if="stats.hasFixedCost" class="flex justify-between items-center text-sm">
                                <span class="text-emerald-700 dark:text-emerald-300">Servicios (Fijo)</span>
                                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.fixedCostTotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-emerald-700 dark:text-emerald-300">Total Extensiones</span>
                                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.extensionsTotal) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-emerald-700 dark:text-emerald-300">Software / Hosting (Gasto Global)</span>
                                <span class="font-medium text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.costeSoftware) }}</span>
                            </div>
                            <div class="border-t border-emerald-200 dark:border-emerald-800 pt-3 flex justify-between items-center">
                                <span class="text-base font-bold text-emerald-900 dark:text-emerald-400">Coste Total</span>
                                <span class="text-xl font-black text-emerald-900 dark:text-emerald-200 text-right">{{ formatCurrency(stats.grandTotal) }}</span>
                            </div>
                            <div v-if="proyecto.presupuesto > 0" class="mt-4">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider mb-2">Utilización del Presupuesto</p>
                                <div class="w-full bg-emerald-200 dark:bg-emerald-800 rounded-full h-2.5">
                                    <div 
                                        class="h-2.5 rounded-full" 
                                        :class="grandTotal > proyecto.presupuesto ? 'bg-red-500' : 'bg-emerald-600'" 
                                        :style="{ width: Math.min((grandTotal / proyecto.presupuesto) * 100, 100) + '%' }"
                                    ></div>
                                </div>
                                <p class="text-right text-xs mt-1 text-emerald-700 dark:text-emerald-300">{{ ((grandTotal / proyecto.presupuesto) * 100).toFixed(1) }}%</p>
                            </div>
</div>
                    </Card>
                </div>

                <!-- Services Table -->
                <Card class="p-4 sm:p-6 overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Servicios Realizados</h3>
                        <PrimaryButton @click="openCreateModal" class="flex items-center gap-2">
                            <PlusIcon class="h-4 w-4" />
                            Registrar Servicio
                        </PrimaryButton>
                    </div>
                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                            <thead class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Servicio</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Fecha</th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Duración</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Coste Horas</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Importe Fijo</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider font-bold">Total</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900/50 divide-y divide-gray-200 dark:divide-zinc-800">
                                <tr 
                                    v-for="service in proyecto.servicios" 
                                    :key="service.id" 
                                    @click="openEditModal(service)"
                                    class="hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition-colors cursor-pointer"
                                >
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-zinc-200">{{ service.servicio }}</div>
                                        <div v-if="service.descripcion" class="text-xs text-gray-500 dark:text-zinc-400 truncate max-w-xs">{{ service.descripcion }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-zinc-400">
                                        {{ formatDate(service.fecha) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900 dark:text-zinc-200">
                                        <div class="flex flex-col items-center">
                                            <span>{{ formatMinutesToHours(service.duracion_minutos) }}</span>
                                            <span class="text-[10px] text-gray-500">({{ formatCurrency(service.precio_hora || page.props.config?.precio_hora || 0) }}/h)</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600/70 dark:text-red-400/70">
                                        {{ formatCurrency((service.duracion_minutos / 60) * (service.precio_hora || page.props.config?.precio_hora || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600/70 dark:text-red-400/70">
                                        {{ formatCurrency(service.precio || 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-zinc-200">
                                        {{ formatCurrency(((service.duracion_minutos / 60) * (service.precio_hora || page.props.config?.precio_hora || 0)) + parseFloat(service.precio || 0)) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <SecondaryButton @click.stop="openEditModal(service)" title="Editar servicio">
                                                <PencilIcon class="h-4 w-4" />
                                            </SecondaryButton>
                                            <DangerButton @click.stop="confirmDelete(service)" title="Eliminar servicio">
                                                <TrashIcon class="h-4 w-4" />
                                            </DangerButton>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!proyecto.servicios?.length">
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-zinc-500 italic">
                                        No hay servicios registrados para este proyecto.
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="proyecto.servicios?.length" class="bg-gray-50 dark:bg-zinc-800/50">
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-left sm:text-right">
                                        <div class="inline-flex flex-col sm:flex-row sm:items-baseline sm:justify-end gap-x-3">
                                            <span class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-widest">Subtotal Servicios</span>
                                            <span class="text-lg font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ formatCurrency(servicesTotal) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </Card>

                <!-- Extensions Grid -->
                <Card v-if="proyecto.extensiones?.length" class="p-4 sm:p-6 overflow-hidden">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Extensiones de Terceros</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="extension in proyecto.extensiones" :key="extension.id" class="p-4 border border-gray-200 dark:border-zinc-700 rounded-lg bg-gray-50 dark:bg-zinc-800/50">
                            <div class="flex flex-col h-full">
                                <div class="flex items-start justify-between">
                                    <div class="font-bold text-gray-900 dark:text-zinc-100">{{ extension.nombre }}</div>
                                    <a v-if="extension.url" :href="extension.url" target="_blank" class="text-[10px] text-emerald-600 dark:text-emerald-400 hover:underline">Ver fuente</a>
                                </div>
                                <div class="mt-3 flex items-baseline gap-2">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-lg">
                                        {{ formatCurrency(extension.pivot?.precio_aplicado || extension.precio) }}
                                    </span>
                                    <span class="text-[10px] text-gray-500 dark:text-zinc-500 uppercase font-bold tracking-tighter">
                                        {{ extension.tipo_licencia }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>


                <!-- Full Pagination -->
                <div class="flex justify-center pb-8  pt-6">
                    <div class="text-center">
                        <Pagination :links="pagination.links" prefetch />
                    </div>
                </div>

        </div>
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

        <!-- Modals for Services -->
        <DialogModal :show="isCreateModalOpen" @close="closeCreateModal">
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

        <!-- Edit Service Modal -->
        <DialogModal :show="isEditModalOpen" @close="closeEditModal">
            <template #title>Editar Servicio</template>
            <template #content>
                <EditServicioForm 
                    v-if="editingService"
                    :servicio="editingService"
                    :proyectos="$page.props.proyectos_list"
                    :hide-proyecto-selector="true"
                    :close-edit-modal="closeEditModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
                <PrimaryButton 
                    class="ms-3" 
                    form="edit-servicio-form"
                    :disabled="false"
                >
                    Actualizar
                </PrimaryButton>
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
