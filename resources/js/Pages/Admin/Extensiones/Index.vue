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

import CreateExtensionForm from './Partials/CreateExtensionForm.vue'
import EditExtensionForm from './Partials/EditExtensionForm.vue'
import { PencilIcon, TrashIcon, PlusIcon, PuzzlePieceIcon, ArrowTopRightOnSquareIcon, CurrencyEuroIcon, CalendarIcon } from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  extensiones: Object,
  stats: Object,
  filters: Object,
})

const { formatCurrency } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.extensiones.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const columns = [
  { key: 'nombre', label: 'Nombre' },
  { key: 'url', label: 'URL', align: 'center' },
  { key: 'tipo_licencia', label: 'Licencia' },
  { key: 'estado', label: 'Estado', align: 'center' },
  { key: 'descripcion', label: 'Descripción' },
  { key: 'precio', label: 'Precio' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroyExtension = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.extensiones.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}
</script>

<template>
  <Head title="Extensiones de terceros" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Extensiones de terceros">
        <template #actions>
          <PrimaryButton type="button" @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nueva extensión
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <StatCard
            title="Repercutido Mensual"
            :value="stats.total_mensual"
            :icon="CurrencyEuroIcon"
            variant="emerald"
            :is-currency="true"
          />
          <StatCard
            title="Repercutido Anual"
            :value="stats.total_anual"
            :icon="CalendarIcon"
            variant="indigo"
            :is-currency="true"
          />
        </div>

        <Card class="p-4 sm:p-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de extensiones</h3>
            <div class="w-full md:w-80">
              <SearchInput 
                id="search-extensiones"
                name="search-extensiones"
                placeholder="Buscar por nombre o descripción..." 
                class="w-full" 
                v-model="search" 
              />
           </div>
          </div>

          <div class="mt-4">
            <DataTable
              :columns="columns"
              :items="extensiones.data"
              @row-click="openEditModal"
            >
              <template #cell-nombre="{ item }">
                <div class="flex items-center">
                  <PuzzlePieceIcon class="h-5 w-5 text-gray-400 mr-2 shrink-0" />
                  <span class="font-medium text-gray-900 dark:text-zinc-200">{{ item.nombre }}</span>
                </div>
              </template>
              <template #cell-url="{ item }">
                <a v-if="item.url" :href="item.url" target="_blank" class="text-gray-400 hover:text-emerald-500 transition-colors" title="Visitar sitio" @click.stop>
                  <ArrowTopRightOnSquareIcon class="h-5 w-5 mx-auto" />
                </a>
                <span v-else class="text-gray-300 dark:text-zinc-700">-</span>
              </template>
              <template #cell-tipo_licencia="{ item }">
                <Badge variant="zinc">{{ item.tipo_licencia }}</Badge>
              </template>
              <template #cell-estado="{ item }">
                <Badge :variant="item.estado === 'Activada' ? 'green' : 'red'">{{ item.estado }}</Badge>
              </template>
              <template #cell-descripcion="{ item }">
                <span class="text-sm text-gray-500 dark:text-zinc-400 line-clamp-1" :title="item.descripcion">
                  {{ item.descripcion || '-' }}
                </span>
              </template>
              <template #cell-precio="{ item }">
                <span class="whitespace-nowrap text-nowrap">
                {{ formatCurrency(item.precio) }}
                </span>
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

          <Pagination :links="extensiones.links" />
        </Card>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nueva Extensión</template>
      <template #content>
        <CreateExtensionForm :closeCreateModal="closeCreateModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-extension-form">Crear Extensión</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar Extensión</template>
      <template #content>
        <EditExtensionForm :extension="editingItem" :closeEditModal="closeEditModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-extension-form">Guardar Cambios</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Extensión"
      :content="`¿Estás seguro de que deseas eliminar la extensión '${itemToDelete?.nombre}'? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroyExtension"
    />
  </AuthenticatedLayout>
</template>
