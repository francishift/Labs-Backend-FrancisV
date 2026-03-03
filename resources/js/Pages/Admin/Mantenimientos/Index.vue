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

import CreateMantenimientoForm from './Partials/CreateMantenimientoForm.vue'
import EditMantenimientoForm from './Partials/EditMantenimientoForm.vue'
import { PencilIcon, TrashIcon, PlusIcon, EyeIcon, ArrowTopRightOnSquareIcon, CurrencyEuroIcon, ClockIcon, WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'

import { useDebouncedSearch } from '@/Composables/useDebouncedSearch'
import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'

const props = defineProps({
  mantenimientos: Object,
  filters: Object,
  clients: Array,
  availableExtensions: Array,
  stats: Object,
})

const { formatDate, formatCurrency, formatDuration } = useFormatters()
const { search } = useDebouncedSearch(props.filters.search, 'admin.mantenimientos.index')
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

const columns = [
  { key: 'aplicacion', label: 'Aplicación' },
  { key: 'url', label: 'URL', align: 'center' },
  { key: 'fecha_inicio', label: 'Inicio' },
  { key: 'tipo_pago', label: 'Pago' },
  { key: 'importe', label: 'Importe' },
  { key: 'estado', label: 'Estado' },
  { key: 'actions', label: 'Acciones', align: 'right' },
]

const destroyMantenimiento = () => {
  if (!itemToDelete.value) return

  useForm({}).delete(route('admin.mantenimientos.destroy', itemToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => closeConfirmModal(),
    onFinish: () => closeConfirmModal()
  })
}

const getStatusVariant = (status) => {
  switch (status) {
    case 'en curso': return 'green'
    case 'finalizado': return 'red'
    default: return 'gray'
  }
}

const navigateToShow = (item) => {
  router.visit(route('admin.mantenimientos.show', item.id))
}
</script>

<template>
  <Head title="Mantenimientos" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Mantenimientos">
        <template #actions>
          <PrimaryButton type="button" @click="openCreateModal" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nuevo mantenimiento
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <!-- Stats Cards Area -->
        <div class="grid grid-cols-1 md:grid-cols-2 min-[1530px]:grid-cols-3 gap-4">
          <StatCard
            title="Ingreso Mantenimientos (Anual)"
            :value="stats.total_ingresos"
            :icon="CurrencyEuroIcon"
            variant="emerald"
            :is-currency="true"
            :small-value="true"
          />
          <StatCard
            title="Extensiones + Software (Anual)"
            :value="stats.total_fijo"
            :icon="WrenchScrewdriverIcon"
            variant="indigo"
            :is-currency="true"
            :small-value="true"
          />
          <StatCard
            title="Horas realizadas (Anual)"
            :value="formatDuration(stats.total_minutos)"
            :icon="ClockIcon"
            variant="rose"
            :is-currency="false"
            :small-value="true"
          />
        </div>

        <Card class="p-4 sm:p-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gestión de mantenimientos</h3>
            <div class="w-full md:w-80">
              <SearchInput 
                id="search-mantenimientos"
                name="search-mantenimientos"
                placeholder="Buscar por aplicación o cliente..." 
                class="w-full" 
                v-model="search" 
              />
            </div>
          </div>

          <div class="mt-4">
            <DataTable
              :columns="columns"
              :items="mantenimientos.data"
              @row-click="navigateToShow"
            >
              <template #cell-aplicacion="{ item }">
                <span class="font-medium text-gray-900 dark:text-zinc-200">
                  {{ item.aplicacion }}
                </span>
              </template>
              <template #cell-url="{ item }">
                <a v-if="item.url" :href="item.url" target="_blank" class="text-gray-400 hover:text-emerald-500 transition-colors" title="Visitar sitio">
                  <ArrowTopRightOnSquareIcon class="h-5 w-5 mx-auto" />
                </a>
                <span v-else class="text-gray-300 dark:text-zinc-700">-</span>
              </template>
              <template #cell-fecha_inicio="{ item }">
                {{ formatDate(item.fecha_inicio) }}
              </template>
              <template #cell-tipo_pago="{ item }">
                <span class="capitalize">{{ item.tipo_pago }}</span>
              </template>
              <template #cell-importe="{ item }">
                <span class="whitespace-nowrap text-nowrap">
                  {{ formatCurrency(item.importe) }}
                </span>
              </template>
              <template #cell-estado="{ item }">
                <Badge :variant="getStatusVariant(item.estado)">
                  {{ item.estado }}
                </Badge>
              </template>
              <template #cell-actions="{ item }">
                <div class="flex justify-end gap-2 text-nowrap">
                  <Link :href="route('admin.mantenimientos.show', item.id)">
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

          <Pagination :links="mantenimientos.links" />
        </Card>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nuevo Mantenimiento</template>
      <template #content>
        <CreateMantenimientoForm 
          :clients="props.clients" 
          :available-extensions="props.availableExtensions"
          :close-create-modal="closeCreateModal" 
        />
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal">Cancelar</SecondaryButton>
        <PrimaryButton form="create-mantenimiento-form">Crear Mantenimiento</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Edit Modal -->
    <DialogModal :show="showEditModal" @close="closeEditModal">
      <template #title>Editar Mantenimiento</template>
      <template #content>
        <EditMantenimientoForm 
          :mantenimiento="editingItem" 
          :clients="props.clients" 
          :available-extensions="props.availableExtensions"
          :close-edit-modal="closeEditModal" 
        />
      </template>
      <template #footer>
        <SecondaryButton @click="closeEditModal">Cancelar</SecondaryButton>
        <PrimaryButton form="edit-mantenimiento-form">Guardar Cambios</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Mantenimiento"
      :content="`¿Estás seguro de que deseas eliminar el mantenimiento de '${itemToDelete?.aplicacion}'? Esta acción no se puede deshacer.`"
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="destroyMantenimiento"
    />
  </AuthenticatedLayout>
</template>
