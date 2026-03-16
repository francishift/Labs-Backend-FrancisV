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
import Badge from '@/Components/Badge.vue'
import PageHeader from '@/Components/PageHeader.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PushToggleButton from '@/Components/PushToggleButton.vue'
import TextArea from '@/Components/TextArea.vue'

import { PencilIcon, TrashIcon, PlusIcon, ClockIcon, LinkIcon } from '@heroicons/vue/24/outline'

import { useFormatters } from '@/Composables/useFormatters'
import { useCRUDModals } from '@/Composables/useCRUDModals'
import { formatDateForInput, getTodayDate } from '@/Utils/date'

const props = defineProps({
  notas: Object,
  filters: Object,
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
    enlace_reunion: '',
    notificacion_minutos_antes: 0,
    sync_calendar: true,
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



const openCreate = () => {
    form.clearErrors()
    form.fecha = ''
    form.hora = ''
    form.comentario = ''
    form.enlace_reunion = ''
    form.notificacion_minutos_antes = 0
    form.sync_calendar = true
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
          <!--<PushToggleButton class="w-full sm:w-auto flex justify-center" />-->

          <PrimaryButton type="button" @click="openCreate" class="flex items-center gap-2 w-full sm:w-auto">
            <PlusIcon class="h-4 w-4" />
            Nueva nota
          </PrimaryButton>
        </template>
      </PageHeader>
    </template>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-0">
        <div>
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ filters?.estado === 'notificadas' ? 'Notas notificadas' : 'Notas pendientes' }}
            </h3>
            
            <!-- Filters -->
            <div class="flex bg-gray-100 dark:bg-zinc-800 p-1 rounded-lg">
                <button 
                  @click="router.get(route('admin.notas.index'), { estado: 'pendientes' }, { preserveState: true })"
                  :class="[
                      'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                      filters?.estado !== 'notificadas' 
                          ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                          : 'text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-zinc-200'
                  ]"
                >
                    Pendientes
                </button>
                <button 
                  @click="router.get(route('admin.notas.index'), { estado: 'notificadas' }, { preserveState: true })"
                  :class="[
                      'px-4 py-2 text-sm font-medium rounded-md transition-colors',
                      filters?.estado === 'notificadas' 
                          ? 'bg-white dark:bg-zinc-700 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                          : 'text-gray-500 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-zinc-200'
                  ]"
                >
                    Notificadas
                </button>
            </div>
          </div>

          <div>
            <div v-if="notas.data && notas.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              <div
                v-for="item in notas.data"
                :key="item.id"
                @click="openEdit(item)"
                class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-700/50 p-5 flex flex-col justify-between hover:shadow-md hover:border-emerald-100 dark:hover:border-emerald-900/50 transition-all duration-200 cursor-pointer group"
              >
                <!-- Card Header -->
                <div class="flex justify-between items-start mb-3 gap-2">
                  <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium text-sm">
                    <ClockIcon class="h-4 w-4 flex-shrink-0" />
                    <span class="truncate">{{ formatDate(item.fecha) }}{{ item.hora ? ' · ' + item.hora.substring(0, 5) : '' }}</span>
                  </div>
                  <Badge :variant="item.notificado ? 'green' : 'gray'" class="shadow-sm whitespace-nowrap">
                    {{ item.notificado ? 'Notificado' : 'Pendiente' }}
                  </Badge>
                </div>

                <!-- Comentario -->
                <div class="text-gray-700 dark:text-zinc-300 text-sm mb-4 flex-grow break-words whitespace-pre-wrap line-clamp-4" :title="item.comentario">{{ item.comentario }}</div>

                <!-- Enlace a Reunión (Condicional) -->
                <div v-if="item.enlace_reunion" class="mb-4">
                  <a 
                    :href="item.enlace_reunion" 
                    target="_blank" 
                    @click.stop 
                    class="inline-flex w-full items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-800/50 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 dark:focus:ring-offset-zinc-800"
                    title="Abrir videollamada"
                  >
                    <LinkIcon class="h-4 w-4" />
                    Unirse a reunión
                  </a>
                </div>

                <!-- Footer -->
                <div class="pt-3 mt-auto border-t border-gray-100 dark:border-zinc-700/50 flex justify-between items-center group-hover:border-emerald-50 dark:group-hover:border-emerald-900/30 transition-colors">
                  <div class="text-xs text-gray-500 dark:text-zinc-400 flex items-center gap-1" title="Aviso (Antelación)">
                    <ClockIcon class="h-4 w-4" />
                    {{ formOptions.find(o => o.value == item.notificacion_minutos_antes)?.label || item.notificacion_minutos_antes + ' min' }}
                  </div>
                  
                  <div class="flex gap-2">
                    <!-- Optional explicit indicator, could be an arrow, edit is implicit -->
                    <span class="p-1.5 text-transparent group-hover:text-emerald-600 dark:group-hover:text-emerald-400 rounded-lg transition-colors" title="Editar">
                      <PencilIcon class="h-4 w-4 flex-shrink-0" />
                    </span>
                    <button @click.stop="confirmDelete(item)" class="p-1.5 text-gray-400 hover:text-red-600 dark:text-zinc-500 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors" title="Eliminar">
                      <TrashIcon class="h-4 w-4 flex-shrink-0" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-12 px-4 border-2 border-dashed border-gray-200 dark:border-zinc-700 rounded-xl bg-gray-50 dark:bg-zinc-800/50">
                <ClockIcon class="h-10 w-10 mx-auto mb-3 text-gray-400 dark:text-zinc-500" />
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">No hay notas registradas</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">Crea una nueva nota para empezar a organizar tus recordatorios.</p>
            </div>
          </div>

          <div class="mt-6" v-if="notas.links && notas.data.length > 0">
              <Pagination :links="notas.links" />
          </div>
        </div>
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
                <InputLabel for="enlace_reunion" value="Enlace Videollamada (opcional)" />
                <TextInput
                    id="enlace_reunion"
                    type="url"
                    class="mt-1 block w-full"
                    v-model="form.enlace_reunion"
                    placeholder="https://meet.google.com/..."
                />
                <InputError class="mt-2" :message="form.errors.enlace_reunion" />
            </div>

            <div>
                <InputLabel for="notificacion_minutos_antes" value="Avisar con antelación" />
                <select 
                    v-model="form.notificacion_minutos_antes" 
                    id="notificacion_minutos_antes"
                    class="mt-1 block w-full border-gray-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm"
                >
                    <option v-for="option in formOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.notificacion_minutos_antes" />
            </div>

            <div class="flex items-center mt-4">
                <input 
                    id="sync_calendar" 
                    type="checkbox" 
                    v-model="form.sync_calendar"
                    class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 dark:focus:ring-emerald-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600"
                >
                <label for="sync_calendar" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                    Sincronizar con Google Calendar
                </label>
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
