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
import PageHeader from '@/Components/PageHeader.vue'

import CreateClientForm from './Partials/CreateClientForm.vue'
import EditClientForm from './Partials/EditClientForm.vue'
import ImportClientForm from './Partials/ImportClientForm.vue'
import { PencilIcon, TrashIcon, ArrowUpTrayIcon, UserPlusIcon, EyeIcon } from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  clients: Object,
  filters: Object,
  syncError: String,
})

const { search } = useDebouncedSearch(props.filters.search, 'admin.clientes.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

// Extra modal for import
import { ref } from 'vue'
const showImportModal = ref(false)
const openImportModal = () => showImportModal.value = true
const closeImportModal = () => showImportModal.value = false

const columns = [
  { key: 'name', label: 'Nombre' },
  { key: 'cif_nif', label: 'NIF/CIF' },
  { key: 'email', label: 'Email' },
  { key: 'city', label: 'Ciudad' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroyClient = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.clientes.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}

const navigateToClient = (item) => {
  router.visit(route('admin.clientes.show', item.id))
}
</script>

<template>
  <Head title="Clientes" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Clientes">
        <template #actions>
          <PrimaryButton type="button" @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <UserPlusIcon class="h-4 w-4" />
            Añadir cliente
          </PrimaryButton>

          <SecondaryButton type="button" @click="openImportModal" class="flex items-center gap-2 w-full sm:w-auto">
            <ArrowUpTrayIcon class="h-4 w-4" />
            Importar Excel
          </SecondaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <!-- Error de sincronización con Holded -->
        <div v-if="syncError" class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/30 border-l-4 border-amber-500 text-amber-700 dark:text-amber-200">
          <div class="flex items-center gap-2">
            <p class="font-bold text-sm">Aviso de sincronización:</p>
            <p class="text-sm italic">Holded {{ syncError }}</p>
          </div>
          <p class="text-xs mt-1">Los datos mostrados podrían no estar actualizados con Holded.</p>
        </div>

        <Card class="p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Listado de clientes</h3>
              <div class="w-full md:w-80">
                <SearchInput 
                  id="search-clientes"
                  name="search-clientes"
                  placeholder="Buscar por nombre, CIF o email..." 
                  class="w-full" 
                  v-model="search" 
                />
             </div>
            </div>

            <div class="mt-4">
              <DataTable
                :columns="columns"
                :items="clients.data"
                @row-click="navigateToClient"
              >
                <template #cell-actions="{ item }">
                  <div class="flex justify-end gap-2">
                    <Link :href="route('admin.clientes.show', item.id)" @click.stop>
                      <SecondaryButton>
                        <EyeIcon class="h-4 w-4" />
                      </SecondaryButton>
                    </Link>
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

            <Pagination :links="clients.links" />
        </Card>
    </div>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar cliente</template>
      <template #content>
        <EditClientForm :client="editingItem" :closeEditModal="closeEditModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-client-form">Guardar</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Import Modal -->
    <DialogModal :show="showImportModal" @close="closeImportModal">
      <template #title>Importar clientes desde Excel</template>
      <template #content>
        <p class="text-sm text-gray-600 dark:text-zinc-400">
          Sube un archivo .xlsx, .xls o .csv con las columnas en el siguiente orden: Nombre, NIF/CIF, Email, Teléfono, Móvil, Dirección, Población, C.P., Provincia, País, Fecha Creación.
        </p>
        <ImportClientForm :closeImportModal="closeImportModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeImportModal">Cancelar</SecondaryButton>
        <PrimaryButton form="import-client-form">Comenzar importación</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Añadir cliente</template>
      <template #content>
        <CreateClientForm :closeCreateModal="closeCreateModal" />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-client-form">Crear Cliente</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Cliente"
      :content="`¿Estás seguro de que deseas eliminar al cliente '${itemToDelete?.name}'? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroyClient"
    />
  </AuthenticatedLayout>
</template>
