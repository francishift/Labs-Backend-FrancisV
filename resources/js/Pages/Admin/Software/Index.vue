<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import Card from '@/Components/Card.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import DataTable from '@/Components/DataTable.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Badge from '@/Components/Badge.vue'
import StatCard from '@/Components/StatCard.vue'

import CreateSoftwareForm from './Partials/CreateSoftwareForm.vue'
import EditSoftwareForm from './Partials/EditSoftwareForm.vue'
import { 
    PencilIcon, 
    TrashIcon, 
    PlusIcon, 
    PuzzlePieceIcon, 
    ComputerDesktopIcon, 
    ServerIcon,
    CurrencyEuroIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  softwares: Object,
  stats: Object,
  filters: Object,
})

const { formatCurrency } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.softwares.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const columns = [
  { key: 'tipo', label: 'Tipo' },
  { key: 'nombre', label: 'Nombre' },
  { key: 'tipo_licencia', label: 'Licencia' },
  { key: 'precio', label: 'Precio' },
  { key: 'estado', label: 'Estado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroySoftware = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.softwares.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}
</script>

<template>
  <Head title="Software / Hosting" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Software / Hosting">
        <template #actions>
          <PrimaryButton type="button" @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nuevo registro
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatCard 
                title="Repercutido Mensual S/H"
                :value="stats.cobro_mensual"
                :icon="CurrencyEuroIcon"
                variant="emerald"
                :is-currency="true"
            />
            <StatCard 
                title="Repercutido Anual S/H"
                :value="stats.cobro_anual"
                :icon="CalendarIcon"
                variant="emerald"
                :is-currency="true"
            />
            <StatCard 
                title="Costo Mensual S/H"
                :value="stats.costo_mensual"
                :icon="CurrencyEuroIcon"
                variant="rose"
                :is-currency="true"
            />
            <StatCard 
                title="Costo Anual S/H"
                :value="stats.costo_anual"
                :icon="CalendarIcon"
                variant="rose"
                :is-currency="true"
            />
        </div>

        <Card class="p-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de Software y Hosting</h3>
           <div class="w-full md:w-80">
              <SearchInput placeholder="Buscar por nombre, tipo..." class="w-full" v-model="search" />
            </div>
          </div>

          <div class="mt-4">
            <DataTable
              :columns="columns"
              :items="softwares.data"
              @row-click="openEditModal"
            >
              <template #cell-tipo="{ item }">
                <div class="flex items-center">
                  <component 
                    :is="item.tipo === 'Software' ? ComputerDesktopIcon : ServerIcon" 
                    class="h-5 w-5 text-gray-400 mr-2 shrink-0" 
                  />
                  <Badge :variant="item.tipo === 'Software' ? 'indigo' : 'emerald'">{{ item.tipo }}</Badge>
                </div>
              </template>
              <template #cell-nombre="{ item }">
                <span class="font-medium text-gray-900 dark:text-zinc-200">{{ item.nombre }}</span>
              </template>
              <template #cell-tipo_licencia="{ item }">
                <Badge variant="zinc">{{ item.tipo_licencia }}</Badge>
              </template>
              <template #cell-precio="{ item }">
                {{ formatCurrency(item.precio) }}
              </template>
              <template #cell-estado="{ item }">
                <Badge :variant="item.estado === 'Activa' ? 'success' : 'danger'">{{ item.estado }}</Badge>
              </template>
              <template #cell-actions="{ item }">
                <div class="flex justify-end gap-2">
                  <SecondaryButton @click.stop="openEditModal(item)">
                    <PencilIcon class="h-4 w-4" />
                  </SecondaryButton>
                  <DangerButton @click.stop="confirmDelete(item)">
                    <TrashIcon class="h-4 w-4" />
                  </DangerButton>
                </div>
              </template>
            </DataTable>
          </div>

          <Pagination :links="softwares.links" />
        </Card>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nuevo Registro</template>
      <template #content>
        <CreateSoftwareForm :closeCreateModal="closeCreateModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-software-form">Crear</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar Registro</template>
      <template #content>
        <EditSoftwareForm :software="editingItem" :closeEditModal="closeEditModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-software-form">Guardar Cambios</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Registro"
      :content="`¿Estás seguro de que deseas eliminar '${itemToDelete?.nombre}'? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroySoftware"
    />
  </AuthenticatedLayout>
</template>
