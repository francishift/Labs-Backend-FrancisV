<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import SearchInput from '@/Components/SearchInput.vue'
import AjaxSearchableSelect from '@/Components/AjaxSearchableSelect.vue'
import Card from '@/Components/Card.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import PageHeader from '@/Components/PageHeader.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

// Partials
import ServiciosTable from './Partials/ServiciosTable.vue'
import CreateServicioForm from './Partials/CreateServicioForm.vue'
import EditServicioForm from './Partials/EditServicioForm.vue'

import { useCRUDModals } from '@/Composables/useCRUDModals'
import { useFilters } from '@/Composables/useFilters'

const props = defineProps({
  servicios: Object,
  filters: Object,
})

const { filters } = useFilters({
  search: props.filters.search || '',
  proyecto_id: props.filters.proyecto_id ? Number(props.filters.proyecto_id) : ''
}, 'admin.servicios.index')

const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const destroyServicio = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.servicios.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal()
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
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de servicios</h3>
            <div class="flex flex-col sm:flex-row w-full md:w-auto gap-4">
              <div class="w-full sm:w-64">
                <AjaxSearchableSelect 
                  v-model="filters.proyecto_id"
                  endpoint="/api/dropdown/proyectos"
                  label-key="proyecto"
                  value-key="id"
                  placeholder="Todos los proyectos..."
                  class="w-full"
                />
              </div>
              <div class="w-full sm:w-80">
                <SearchInput 
                  id="search-servicios"
                  name="search-servicios"
                  placeholder="Buscar por servicio o proyecto..." 
                  class="w-full" 
                  v-model="filters.search" 
                />
              </div>
            </div>
          </div>

          <ServiciosTable 
            :items="servicios.data"
            @row-click="openEditModal"
            @edit="openEditModal"
            @delete="confirmDelete"
          />

          <Pagination :links="servicios.links" class="mt-6" />
        </Card>
    </div>

    <!-- Modals -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nuevo Servicio</template>
      <template #content>
        <CreateServicioForm :closeCreateModal="closeCreateModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-servicio-form">Crear Servicio</PrimaryButton>
      </template>
    </DialogModal>

    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar Servicio</template>
      <template #content>
        <EditServicioForm :servicio="editingItem" :closeEditModal="closeEditModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-servicio-form">Guardar Cambios</PrimaryButton>
      </template>
    </DialogModal>

    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Servicio"
      :content="`¿Estás seguro de que deseas eliminar el servicio '${itemToDelete?.servicio}'? Esta acción no se puede deshacer.`"
      @close="closeConfirmModal"
      @confirm="destroyServicio"
    />
  </AuthenticatedLayout>
</template>
