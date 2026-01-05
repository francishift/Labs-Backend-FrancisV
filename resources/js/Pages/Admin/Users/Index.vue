<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import Pagination from '@/Components/Pagination.vue'
import FlashMessages from '@/Components/FlashMessages.vue'
import Card from '@/Components/Card.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import SearchInput from '@/Components/SearchInput.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import PageHeader from '@/Components/PageHeader.vue'

import CreateUserForm from './Partials/CreateUserForm.vue'
import EditUserForm from './Partials/EditUserForm.vue'
import { PencilIcon, TrashIcon, UserPlusIcon } from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  users: Object,
  roles: Array,
  me: Object,
  filters: Object,
})

const { search } = useDebouncedSearch(props.filters?.search, 'admin.usuarios.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const columns = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Nombre' },
  { key: 'email', label: 'Email' },
  { key: 'roles', label: 'Rol' },
  { key: 'created_at', label: 'Creado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroyUser = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.usuarios.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}
</script>

<template>
  <Head title="Usuarios" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Usuarios">
        <template #actions>
          <PrimaryButton @click="openCreateModal" class="flex items-center gap-2">
            <UserPlusIcon class="h-4 w-4" />
            Añadir usuario
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <FlashMessages />

        <Card class="p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Listado</h3>
              <div class="w-full md:w-80">
                <SearchInput placeholder="Buscar por nombre o email..." class="w-full" v-model="search" />
              </div>
            </div>

            <div class="mt-4">
              <DataTable
                :columns="columns"
                :items="users.data"
                @row-click="openEditModal"
              >
                <template #cell-roles="{ item }">
                  <Badge variant="zinc">
                    {{ item.roles?.[0] }}
                  </Badge>
                </template>

                <template #cell-actions="{ item }">
                  <div class="flex flex-col md:flex-row justify-end gap-2">
                    <SecondaryButton
                      type="button"
                      class="inline-flex items-center justify-center whitespace-nowrap"
                      @click.stop="openEditModal(item)"
                    >
                      <PencilIcon class="h-4 w-4 mr-2" />
                      Editar
                    </SecondaryButton>
                    <DangerButton
                      v-if="item.id !== me.id"
                      type="button"
                      class="inline-flex items-center justify-center whitespace-nowrap"
                      @click.stop="confirmDelete(item)"
                      title="Eliminar usuario"
                    >
                      <TrashIcon class="h-4 w-4 mr-2" />
                      Eliminar
                    </DangerButton>
                  </div>
                </template>
              </DataTable>
            </div>

            <Pagination :links="users.links" />
        </Card>
    </div>

    <!-- Edit User Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar usuario</template>
      <template #content>
        <EditUserForm :user="editingItem" :roles="roles" :me="me" :closeEditModal="closeEditModal" />
      </template>
      <template #footer>
        <SecondaryButton type="button" @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-user-form">Guardar cambios</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Create User Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
        <template #title>Crear usuario</template>
        <template #content>
            <CreateUserForm :roles="roles" :closeCreateModal="closeCreateModal" />
        </template>
        <template #footer>
            <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
            <PrimaryButton form="create-user-form">Crear</PrimaryButton>
        </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Usuario"
      :content="`¿Estás seguro de que deseas eliminar al usuario '${itemToDelete?.name}' (${itemToDelete?.email})? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroyUser"
    />
  </AuthenticatedLayout>
</template>