<script setup>
import { computed, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import EditClientForm from './Partials/EditClientForm.vue'

// Partials
import ClientInfo from './Partials/ClientInfo.vue'
import ClientStats from './Partials/ClientStats.vue'
import ClientProjects from './Partials/ClientProjects.vue'
import ClientMaintenance from './Partials/ClientMaintenance.vue'
import ClientHoldedBudgets from './Partials/ClientHoldedBudgets.vue'
import ClientHoldedInvoices from './Partials/ClientHoldedInvoices.vue'
import ClientExtensions from './Partials/ClientExtensions.vue'

import { 
    ArrowLeftIcon,
    PencilIcon,
    PrinterIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    client: Object,
    presupuestos: Array,
    facturas: Array,
    pagination: Object,
    stats: Object
})

const currentUrl = typeof window !== 'undefined' ? window.location.href : ''

// Estado del Modal de Edición
const isEditModalOpen = ref(false)
const openEditModal = () => isEditModalOpen.value = true
const closeEditModal = () => isEditModalOpen.value = false

// Todas las extensiones únicas utilizadas por este cliente (de proyectos y mantenimiento)
const clientExtensions = computed(() => {
    const extensionsMap = new Map()
    
    props.client.proyectos.forEach(proyecto => {
        proyecto.extensiones?.forEach(ext => {
            if (!extensionsMap.has(ext.id)) {
                extensionsMap.set(ext.id, { ...ext, source: 'Proyecto: ' + proyecto.proyecto })
            }
        })
    })
    
    props.client.mantenimientos.forEach(mante => {
        mante.extensiones?.forEach(ext => {
            if (!extensionsMap.has(ext.id)) {
                extensionsMap.set(ext.id, { ...ext, source: 'Mantenimiento: ' + mante.aplicacion })
            }
        })
    })
    
    return Array.from(extensionsMap.values())
})
</script>

<template>
    <Head :title="`Cliente: ${client.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <PageHeader :title="client.name" inline>
                <template #actions>
                    <div class="flex gap-2">
                        <Link :href="route('admin.clientes.index')">
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
                <!-- Info Section -->
                <ClientInfo :client="client" />
                
                <!-- Stats Section -->
                <ClientStats :stats="stats" />
            </div>

            <!-- Projects & Maintenance Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ClientProjects :projects="client.proyectos" />
                <ClientMaintenance :maintenance="client.mantenimientos" />
            </div>

            <!-- Holded Integration Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <ClientHoldedBudgets :presupuestos="presupuestos" :current-url="currentUrl" />
                <ClientHoldedInvoices :facturas="facturas" :current-url="currentUrl" />
            </div>

            <!-- Extensions Section -->
            <ClientExtensions :extensions="clientExtensions" />

            <!-- Client Pagination -->
            <div class="flex justify-center pb-8 pt-6">
                <Pagination :links="pagination.links" />
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
