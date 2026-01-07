<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, Link, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import Card from '@/Components/Card.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import PageHeader from '@/Components/PageHeader.vue'
import StatCard from '@/Components/StatCard.vue'

import CreateProyectoForm from './Partials/CreateProyectoForm.vue'
import EditProyectoForm from './Partials/EditProyectoForm.vue'
import { PencilIcon, TrashIcon, PlusIcon, EyeIcon, CurrencyEuroIcon, ClockIcon, WrenchScrewdriverIcon, ChartBarIcon } from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  proyectos: Object,
  filters: Object,
  clients: Array,
  availableExtensions: Array,
  stats: Object,
})

const { formatDate, formatCurrency, formatDuration } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.proyectos.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const columns = [
  { key: 'proyecto', label: 'Proyecto' },
  { key: 'client', label: 'Cliente' },
  { key: 'fecha_inicio', label: 'Inicio' },
  { key: 'presupuesto', label: 'Presupuesto' },
  { key: 'estado', label: 'Estado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroyProyecto = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.proyectos.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}

const getStatusVariant = (status) => {
  switch (status) {
    case 'En proceso': return 'green'
    case 'Finalizado': return 'red'
    case 'Cancelado': return 'gray'
    default: return 'gray'
  }
}

const navigateToShow = (item) => {
  router.visit(route('admin.proyectos.show', item.id))
}
</script>

<template>
  <Head title="Proyectos" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Proyectos">
        <template #actions>
          <PrimaryButton type="button" @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nuevo proyecto
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <!-- Stats Cards Area -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <StatCard
            title="Total Presupuesto"
            :value="stats.total_presupuesto"
            :icon="CurrencyEuroIcon"
            icon-color="text-emerald-500"
            icon-bg="bg-emerald-100 dark:bg-emerald-900/30"
            :is-currency="true"
          />
          <StatCard
            title="Extensiones + Software"
            :value="stats.total_fijo"
            :icon="WrenchScrewdriverIcon"
            icon-color="text-indigo-500"
            icon-bg="bg-indigo-100 dark:bg-indigo-900/30"
            :is-currency="true"
          />
          <StatCard
            title="Horas + Extensiones + Software"
            :value="stats.total_gastos"
            :icon="ChartBarIcon"
            variant="amber"
            :is-currency="true"
          />
          <StatCard
            title="Suma servicios por horas"
            :value="stats.total_servicios"
            :secondary-value="formatDuration(stats.total_minutos)"
            :secondary-is-currency="false"
            :icon="ClockIcon"
            variant="indigo"
            :is-currency="true"
          />
        </div>

        <Card class="p-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de proyectos</h3>
            <div class="w-full md:w-80">
              <SearchInput placeholder="Buscar por proyecto o cliente..." class="w-full" v-model="search" />
            </div>
          </div>

          <div class="mt-4">
            <DataTable
              :columns="columns"
              :items="proyectos.data"
              @row-click="navigateToShow"
            >
              <template #cell-proyecto="{ item }">
                <span class="font-medium text-gray-900 dark:text-zinc-200">
                  {{ item.proyecto }}
                </span>
              </template>
              <template #cell-client="{ item }">
                {{ item.client?.name || 'S/N' }}
              </template>
              <template #cell-fecha_inicio="{ item }">
                {{ formatDate(item.fecha_inicio) }}
              </template>
              <template #cell-presupuesto="{ item }">
                {{ formatCurrency(item.presupuesto) }}
              </template>
              <template #cell-estado="{ item }">
                <Badge :variant="getStatusVariant(item.estado)">
                  {{ item.estado }}
                </Badge>
              </template>
              <template #cell-actions="{ item }">
                <div class="flex justify-end gap-2 text-nowrap">
                  <Link :href="route('admin.proyectos.show', item.id)">
                    <SecondaryButton title="Ver detalles">
                      <EyeIcon class="h-4 w-4" />
                    </SecondaryButton>
                  </Link>
                  <SecondaryButton @click.stop="openEditModal(item)" title="Editar">
                    <PencilIcon class="h-4 w-4" />
                  </SecondaryButton>
                  <DangerButton @click.stop="confirmDelete(item)">
                    <TrashIcon class="h-4 w-4" />
                  </DangerButton>
                </div>
              </template>
            </DataTable>
          </div>

          <Pagination :links="proyectos.links" />
        </Card>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nuevo Proyecto</template>
      <template #content>
        <CreateProyectoForm 
          :clients="props.clients" 
          :available-extensions="props.availableExtensions"
          :close-create-modal="closeCreateModal" 
        />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-proyecto-form">Crear Proyecto</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar Proyecto</template>
      <template #content>
        <EditProyectoForm 
          :proyecto="editingItem" 
          :clients="props.clients" 
          :available-extensions="props.availableExtensions"
          :close-edit-modal="closeEditModal" 
        />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-proyecto-form">Guardar Cambios</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Proyecto"
      :content="`¿Estás seguro de que deseas eliminar el proyecto '${itemToDelete?.proyecto}'? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroyProyecto"
    />
  </AuthenticatedLayout>
</template>
