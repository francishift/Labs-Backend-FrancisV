<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import DialogModal from '@/Components/DialogModal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import Pagination from '@/Components/Pagination.vue'
import Card from '@/Components/Card.vue'
import DataTable from '@/Components/DataTable.vue'
import Badge from '@/Components/Badge.vue'
import PageHeader from '@/Components/PageHeader.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PushToggleButton from '@/Components/PushToggleButton.vue'
import TextArea from '@/Components/TextArea.vue'

import { PencilIcon, TrashIcon, PlusIcon, ClockIcon } from '@heroicons/vue/24/outline'

import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'
import { formatDateForInput, getTodayDate } from '@/Utils/date'

const props = defineProps({
  notas: Object,
})

const { formatDate } = useFormatters()
const {
    showCreateModal, showEditModal, showConfirmModal, 
    editingItem, itemToDelete,
    openCreateModal, closeCreateModal, openEditModal, closeEditModal, 
    confirmDelete, closeConfirmModal
} = useCRUDModals()

    // Auto-refresh via WebPush BroadcastChannel
const refreshChannel = new BroadcastChannel('notas-refresh');

onMounted(() => {
    refreshChannel.onmessage = (event) => {
        if (event.data && event.data.type === 'REFRESH_NOTAS') {
            router.reload({ only: ['notas'] });
        }
    };
});

onUnmounted(() => {
    refreshChannel.close();
});

// Formulario reactivo general para Crear y Editar
const form = useForm({
    fecha: '',
    hora: '',
    comentario: '',
    notificacion_minutos_antes: 0,
})

const formOptions = [
    { value: -1, label: 'Sin notificación' },
    { value: 0, label: 'A la hora indicada' },
    { value: 1, label: '1 Minuto antes' },
    { value: 5, label: '5 Minutos antes' },
    { value: 15, label: '15 Minutos antes' },
    { value: 60, label: '1 Hora antes' },
    { value: 1440, label: '1 Día antes' },
]

const minDate = computed(() => {
    if (form.notificacion_minutos_antes != -1) {
        return getTodayDate();
    }
    return undefined;
});

const minTime = computed(() => {
    if (form.notificacion_minutos_antes != -1 && form.fecha === getTodayDate()) {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }
    return undefined;
});

// Watch para limpiar fecha/hora si se selecciona notificación y los valores son del pasado
watch(() => form.notificacion_minutos_antes, (newVal) => {
    if (newVal != -1) {
        const today = getTodayDate();
        if (form.fecha && form.fecha < today) {
            form.fecha = today; // restablecer a la fecha mínima válida
        }
        if (form.fecha === today && form.hora) {
            const now = new Date();
            const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            if (form.hora < currentTime) {
                form.hora = currentTime;
            }
        }
    }
});

const columns = [
  { key: 'comentario', label: 'Comentario' },
  { key: 'fecha_hora', label: 'Fecha' },
  { key: 'estado', label: 'Estado', align: 'center' },
  { key: 'aviso', label: '', align: 'center' },
  { key: 'actions', label: '', align: 'right' },
]

const openCreate = () => {
    form.clearErrors()
    form.fecha = ''
    form.hora = ''
    form.comentario = ''
    form.notificacion_minutos_antes = 0
    openCreateModal()
}

const openEdit = (nota) => {
    router.visit(route('admin.notas.edit', nota.id));
}

const saveNota = () => {
    if (showEditModal.value && editingItem.value) {
        form.patch(route('admin.notas.update', editingItem.value.id), {
            onSuccess: () => closeEditModal(),
        })
    } else {
        form.post(route('admin.notas.store'), {
            onSuccess: () => closeCreateModal(),
        })
    }
}

const performDelete = () => {
    if (!itemToDelete.value) return
    form.delete(route('admin.notas.destroy', itemToDelete.value.id), {
        onSuccess: () => closeConfirmModal(),
    })
}
</script>

<template>
  <Head title="Notas" />

  <AuthenticatedLayout>
    <template #header>
      <PageHeader title="Gestión de Notas">
        <template #actions>
          <PushToggleButton class="w-full sm:w-auto flex justify-center" />

          <PrimaryButton type="button" @click="openCreate" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nueva nota
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6">
        <Card class="p-4 sm:p-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Mis notas y recordatorios</h3>
          </div>

          <div class="mt-4">
            <DataTable
              :columns="columns"
              :items="notas.data"
            >
              <template #header-aviso>
                <div class="flex justify-center">
                  <ClockIcon class="h-5 w-5 text-gray-400 dark:text-zinc-500" title="Aviso (Antelación)" />
                </div>
              </template>

              <template #cell-fecha_hora="{ item }">
                <span class="font-medium text-gray-900 dark:text-zinc-200">
                  {{ formatDate(item.fecha) }} a las {{ item.hora ? item.hora.substring(0, 5) : '' }}
                </span>
              </template>
              
              <template #cell-comentario="{ item }">
                {{ item.comentario }}
              </template>
              
              <template #cell-aviso="{ item }">
                {{ formOptions.find(o => o.value == item.notificacion_minutos_antes)?.label || item.notificacion_minutos_antes + ' min' }}
              </template>
              
              <template #cell-estado="{ item }">
                <Badge :variant="item.notificado ? 'green' : 'gray'">
                  {{ item.notificado ? 'Notificado' : 'Pendiente' }}
                </Badge>
              </template>
              
              <template #cell-actions="{ item }">
                <div class="flex justify-end gap-2 text-nowrap">
                  <SecondaryButton @click.stop="openEdit(item)" title="Editar">
                    <PencilIcon class="h-4 w-4" />
                  </SecondaryButton>
                  <DangerButton @click.stop="confirmDelete(item)" title="Eliminar">
                    <TrashIcon class="h-4 w-4" />
                  </DangerButton>
                </div>
              </template>
            </DataTable>
            <div v-if="notas.data && notas.data.length === 0" class="text-center py-4 text-gray-500">
                No hay notas registradas. Crea una ahora.
            </div>
          </div>

          <div class="mt-4" v-if="notas.links && notas.data.length > 0">
              <Pagination :links="notas.links" />
          </div>
        </Card>
    </div>

    <!-- Create Modal -->
    <DialogModal :show="showCreateModal" @close="closeCreateModal">
      <template #title>Nueva Nota</template>
      <template #content>
        <div class="space-y-4 py-2">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="w-full md:w-1/2">
                    <InputLabel for="fecha" value="Fecha" />
                    <TextInput
                        id="fecha"
                        type="date"
                        class="mt-1 block w-full"
                        v-model="form.fecha"
                        :min="minDate"
                    />
                    <InputError class="mt-2" :message="form.errors.fecha" />
                </div>
                <div class="w-full md:w-1/2">
                    <InputLabel for="hora" value="Hora" />
                    <TextInput
                        id="hora"
                        type="time"
                        class="mt-1 block w-full"
                        v-model="form.hora"
                        :min="minTime"
                    />
                    <InputError class="mt-2" :message="form.errors.hora" />
                </div>
            </div>

            <div>
                <InputLabel for="comentario" value="Comentario" />
                <TextArea 
                    id="comentario"
                    v-model="form.comentario"
                    class="mt-1 block w-full"
                    rows="3"
                />
                <InputError class="mt-2" :message="form.errors.comentario" />
            </div>

            <div>
                <InputLabel for="notificacion_minutos_antes" value="Avisar con antelación" />
                <select 
                    v-model="form.notificacion_minutos_antes" 
                    id="notificacion_minutos_antes"
                    class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                >
                    <option v-for="option in formOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.notificacion_minutos_antes" />
            </div>
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="closeCreateModal" class="mr-3">Cancelar</SecondaryButton>
        <PrimaryButton @click="saveNota" :disabled="form.processing">Crear Nota</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Confirm Modal -->
    <ConfirmModal
      :show="showConfirmModal"
      title="Eliminar Nota"
      content="¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer."
      confirm-text="Sí, eliminar"
      cancel-text="Cancelar"
      @close="closeConfirmModal"
      @confirm="performDelete"
    />
  </AuthenticatedLayout>
</template>
