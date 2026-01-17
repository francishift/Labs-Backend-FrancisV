<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Card from '@/Components/Card.vue'
import Badge from '@/Components/Badge.vue'
import { useFormatters } from '@/Composables/useFormatters'
import PageHeader from '@/Components/PageHeader.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import EditClientForm from './Partials/EditClientForm.vue'

import { 
    BriefcaseIcon, 
    ClockIcon, 
    PuzzlePieceIcon, 
    ArrowLeftIcon,
    CalendarIcon,
    CurrencyEuroIcon,
    EnvelopeIcon,
    PhoneIcon,
    IdentificationIcon,
    MapPinIcon,
    PencilIcon,
    DocumentTextIcon,
    EyeIcon,
    PrinterIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    client: Object,
    presupuestos: Array,
    pagination: Object,
    stats: Object
})

const { formatCurrency, formatDate } = useFormatters()

const currentUrl = typeof window !== 'undefined' ? window.location.href : ''

// Edit Modal state
const isEditModalOpen = ref(false)
const openEditModal = () => isEditModalOpen.value = true
const closeEditModal = () => isEditModalOpen.value = false

const activeProjects = computed(() => 
    props.client.proyectos.filter(p => p.estado === 'En proceso')
)

const finishedProjects = computed(() => 
    props.client.proyectos.filter(p => p.estado === 'Finalizado')
)

const maintenance = computed(() => props.client.mantenimientos)

// All unique extensions used by this client (from projects and maintenance)
const clientExtensions = computed(() => {
    const extensionsMap = new Map()
    
    props.client.proyectos.forEach(proyecto => {
        proyecto.extensiones.forEach(ext => {
            if (!extensionsMap.has(ext.id)) {
                extensionsMap.set(ext.id, { ...ext, source: 'Proyecto: ' + proyecto.proyecto })
            }
        })
    })
    
    props.client.mantenimientos.forEach(mante => {
        mante.extensiones.forEach(ext => {
            if (!extensionsMap.has(ext.id)) {
                extensionsMap.set(ext.id, { ...ext, source: 'Mantenimiento: ' + mante.aplicacion })
            }
        })
    })
    
    return Array.from(extensionsMap.values())
})

const getStatusColor = (status) => {
    const colors = {
        'En proceso': 'indigo',
        'Finalizado': 'emerald',
        'en curso': 'emerald',
        'pendiente': 'amber',
        'cancelado': 'rose'
    }
    return colors[status] || 'zinc'
}

const getHoldedStatusColor = (status) => {
    const colors = {
        '0': 'amber', // Pendiente
        '1': 'emerald', // Aceptado
        '2': 'rose', // Rechazado
    }
    return colors[status] || 'zinc'
}

const getHoldedStatusLabel = (status) => {
    const labels = {
        '0': 'Pendiente',
        '1': 'Aceptado',
        '2': 'Rechazado',
    }
    return labels[status] || 'Desconocido'
}
</script>

<template>
    <Head :title="`Cliente: ${client.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="client.name" inline>
                <template #actions>
                    <div class="flex gap-2">
                        <Link :href="route('admin.clientes.index')" prefetch>
                            <SecondaryButton class="flex items-center" title="Volver al listado">
                                <ArrowLeftIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>
                        <PrimaryButton @click="openEditModal" title="Editar Cliente" class="flex items-center">
                            <PencilIcon class="h-4 w-4" />
                        </PrimaryButton>
                        <Link 
                            :href="route('admin.visor-pdf', { 
                                url: route('admin.clientes.pdf', client.id),
                                title: `Cliente: ${client.name}`,
                                backUrl: currentUrl
                            })"
                        >
                            <SecondaryButton title="Imprimir PDF" class="flex items-center">
                                <PrinterIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </Link>
                        <a :href="route('admin.clientes.pdf', { client: client.id, download: 1 })">
                            <SecondaryButton title="Descargar PDF" class="flex items-center">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </SecondaryButton>
                        </a>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="py-6 space-y-6">
            <!-- Client Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card class="p-4 md:col-span-2">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <IdentificationIcon class="h-5 w-5 text-emerald-600" />
                        Información del Cliente
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                                <IdentificationIcon class="h-4 w-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">CIF/NIF</p>
                                <p class="text-sm font-medium dark:text-zinc-200">{{ client.cif_nif || 'No asignado' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                                <EnvelopeIcon class="h-4 w-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Email</p>
                                <p class="text-sm font-medium dark:text-zinc-200">{{ client.email || 'No asignado' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                                <PhoneIcon class="h-4 w-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Teléfono / Móvil</p>
                                <p class="text-sm font-medium dark:text-zinc-200">
                                    {{ [client.phone, client.mobile].filter(Boolean).join(' / ') || 'No asignado' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="mt-1 p-2 rounded-lg bg-gray-50 dark:bg-zinc-800">
                                <MapPinIcon class="h-4 w-4 text-gray-500" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Ubicación</p>
                                <p class="text-sm font-medium dark:text-zinc-200">
                                    {{ [client.city, client.province].filter(Boolean).join(', ') || 'Desconocida' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                <Card class="p-4 bg-emerald-50 dark:bg-emerald-600/20 text-gray-900 dark:text-white border-none">
                    <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                        <CurrencyEuroIcon class="h-5 w-5 text-emerald-600" />
                        Resumen Financiero
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs uppercase font-bold text-gray-500 dark:text-emerald-400/60">Presupuesto Proyectos Proceso</p>
                            <p class="text-2xl font-black">{{ formatCurrency(stats.active_projects_budget) }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase font-bold text-gray-500 dark:text-emerald-400/60">Ingreso Mensual Mant.</p>
                            <p class="text-2xl font-black">
                                {{ formatCurrency(stats.monthly_maintenance_income) }}
                            </p>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Projects Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Active Projects -->
                <Card class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <BriefcaseIcon class="h-5 w-5 text-indigo-600" />
                            Proyectos en Curso
                        </h3>
                        <Badge color="indigo">{{ activeProjects.length }}</Badge>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-700">
                        <div v-for="proyecto in activeProjects" :key="proyecto.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <div class="flex justify-between items-start mb-1">
                                <Link :href="route('admin.proyectos.show', proyecto.id)" class="font-bold text-gray-900 dark:text-white hover:text-emerald-600 transition-colors">
                                    {{ proyecto.proyecto }}
                                </Link>
                                <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(proyecto.presupuesto) }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <CalendarIcon class="h-3 w-3" />
                                    Inicio: {{ formatDate(proyecto.fecha_inicio) }}
                                </span>
                                <Badge :color="getStatusColor(proyecto.estado)" size="xs">{{ proyecto.estado }}</Badge>
                            </div>
                        </div>
                        <div v-if="activeProjects.length === 0" class="p-8 text-center text-gray-500 italic">
                            No hay proyectos en curso.
                        </div>
                    </div>
                </Card>

                <!-- Maintenance -->
                <Card class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <ClockIcon class="h-5 w-5 text-emerald-600" />
                            Mantenimientos Activos
                        </h3>
                        <Badge color="emerald">{{ maintenance.length }}</Badge>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-700">
                        <div v-for="mante in maintenance" :key="mante.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <div class="flex justify-between items-start mb-1">
                                <Link :href="route('admin.mantenimientos.show', mante.id)" class="font-bold text-gray-900 dark:text-white hover:text-emerald-600 transition-colors">
                                    {{ mante.aplicacion }}
                                </Link>
                                <div class="text-right">
                                    <p class="font-black text-gray-900 dark:text-white">{{ formatCurrency(mante.importe) }}</p>
                                    <p class="text-[10px] uppercase font-bold text-gray-400">{{ mante.tipo_pago }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <CalendarIcon class="h-3 w-3" />
                                    Desde: {{ formatDate(mante.fecha_inicio) }}
                                </span>
                                <Badge :color="getStatusColor(mante.estado)" size="xs">{{ mante.estado }}</Badge>
                            </div>
                        </div>
                        <div v-if="maintenance.length === 0" class="p-8 text-center text-gray-500 italic">
                            No hay servicios de mantenimiento contratados.
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Middle Section: Budgets & Extensions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Holded Budgets -->
                <Card class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <DocumentTextIcon class="h-5 w-5 text-blue-500" />
                            Presupuestos Holded (IVA incluido)
                        </h3>
                        <Badge color="blue">{{ presupuestos.length }}</Badge>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-700 max-h-80 overflow-y-auto">
                        <div v-for="presu in presupuestos" :key="presu.id" class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm">
                                        {{ presu.raw_data?.docNumber || 'Nº Desconocido' }} - {{ formatDate(presu.date * 1000) }}
                                    </p>
                                    <Badge :color="getHoldedStatusColor(presu.status)" size="xs" class="mt-1">
                                        {{ getHoldedStatusLabel(presu.status) }}
                                    </Badge>
                                </div>
                                <div class="text-right">
                                    <p class="font-black text-gray-900 dark:text-white leading-tight">{{ formatCurrency(presu.total) }}</p>
                                    <Link 
                                        :href="route('admin.visor-pdf', { 
                                            url: route('admin.holded.presupuestos.pdf', presu.holded_id),
                                            title: `Presupuesto: ${presu.raw_data?.docNumber || presu.holded_id}`,
                                            backUrl: currentUrl
                                        })" 
                                        class="inline-flex items-center gap-1 text-[10px] uppercase font-bold text-blue-600 hover:text-blue-800 transition-colors mt-2"
                                    >
                                        <EyeIcon class="h-3 w-3" />
                                        Ver PDF
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-if="presupuestos.length === 0" class="p-8 text-center text-gray-500 italic">
                            No hay presupuestos sincronizados de Holded.
                        </div>
                    </div>
                </Card>

                <!-- Extensions Used -->
                <Card class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <PuzzlePieceIcon class="h-5 w-5 text-amber-500" />
                            Extensiones & Herramientas
                        </h3>
                        <Badge color="amber">{{ clientExtensions.length }}</Badge>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-wrap gap-2">
                            <div v-for="ext in clientExtensions" :key="ext.id" 
                                class="flex flex-col p-3 rounded-xl bg-gray-50 dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 min-w-[200px] flex-1"
                            >
                                <span class="font-bold text-gray-900 dark:text-white text-sm">{{ ext.nombre }}</span>
                                <span class="text-[10px] text-gray-500 mt-1 uppercase">{{ ext.source }}</span>
                            </div>
                        </div>
                        <div v-if="clientExtensions.length === 0" class="p-4 text-center text-gray-500 italic">
                            No se han detectado extensiones vinculadas.
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Bottom Section: Finished Projects -->
            <div v-if="finishedProjects.length > 0" class="grid grid-cols-1 gap-6">
                <!-- Finished Projects -->
                <Card class="p-0 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <BriefcaseIcon class="h-5 w-5 text-gray-400" />
                            Proyectos Finalizados
                        </h3>
                        <Badge color="zinc">{{ finishedProjects.length }}</Badge>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-zinc-700 max-h-80 overflow-y-auto">
                        <div v-for="proyecto in finishedProjects" :key="proyecto.id" class="p-4 opacity-75 grayscale hover:grayscale-0 hover:opacity-100 transition-all">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-gray-900 dark:text-white">{{ proyecto.proyecto }}</span>
                                <span class="font-black text-gray-900 dark:text-white">{{ formatCurrency(proyecto.presupuesto) }}</span>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span>Fin: {{ formatDate(proyecto.fecha_fin || proyecto.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Client Pagination -->
            <div class="flex justify-center pb-8 pt-6">
                <Pagination :links="pagination.links" prefetch />
            </div>
        </div>

        <!-- Edit Client Modal -->
        <DialogModal :show="isEditModalOpen" @close="closeEditModal">
            <template #title>Editar Cliente</template>
            <template #content>
                <EditClientForm 
                    v-if="client"
                    :client="client"
                    :close-edit-modal="closeEditModal"
                />
            </template>
            <template #footer>
                <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
                <PrimaryButton class="ms-3" form="edit-client-form">Actualizar</PrimaryButton>
            </template>
        </DialogModal>
    </AuthenticatedLayout>
</template>
