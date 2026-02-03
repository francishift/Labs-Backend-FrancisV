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

import CreateServicioForm from './Partials/CreateServicioForm.vue'
import EditServicioForm from './Partials/EditServicioForm.vue'
import { PencilIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  servicios: Object,
  filters: Object,
  proyectos: Array,
})

const { formatDate, formatCurrency, formatDuration } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.servicios.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const columns = [
  { key: 'fecha', label: 'Fecha' },
  { key: 'servicio', label: 'Servicio' },
  { key: 'proyecto', label: 'Proyecto' },
  { key: 'duracion', label: 'Duración' },
  { key: 'precio', label: 'Precio' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroyServicio = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.servicios.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}
</script>

<template>
  <Head title="Servicios" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Servicios">
        <template #actions>
          <PrimaryButton type="button" @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nuevo servicio
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <Card class="p-4 sm:p-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de servicios</h3>
            <div class="w-full md:w-80">
              <SearchInput 
                id="search-servicios"
                name="search-servicios"
                placeholder="Buscar por servicio o proyecto..." 
                class="w-full" 
                v-model="search" 
              />
           </div>
          </div>

          <div class="mt-4">
            <DataTable
              :columns="columns"
              :items="servicios.data"
              @row-click="openEditModal"
            >
              <template #cell-proyecto="{ item }">
                {{ item.proyecto?.proyecto || 'S/N' }}
              </template>
              <template #cell-fecha="{ item }">
                {{ formatDate(item.fecha) }}
              </template>
              <template #cell-duracion="{ item }">
                {{ formatDuration(item.duracion_minutos) }}
              </template>
              <template #cell-precio="{ item }">
                {{ formatCurrency(item.precio) }}
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

          <Pagination :links="servicios.links" />
        </Card>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nuevo Servicio</template>
      <template #content>
        <CreateServicioForm :proyectos="proyectos" :closeCreateModal="closeCreateModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-servicio-form">Crear Servicio</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar Servicio</template>
      <template #content>
        <EditServicioForm :servicio="editingItem" :proyectos="proyectos" :closeEditModal="closeEditModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-servicio-form">Guardar Cambios</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Servicio"
      :content="`¿Estás seguro de que deseas eliminar el servicio '${itemToDelete?.servicio}'? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroyServicio"
    />
  </AuthenticatedLayout>
</template>
