<script setup>
import { ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { 
    CalendarDaysIcon, 
    ExclamationTriangleIcon, 
    TrashIcon 
} from '@heroicons/vue/24/outline';
import axios from 'axios';

const props = defineProps({
    show: Boolean,
    eventData: {
        type: Object,
        default: () => ({})
    },
    isEditing: Boolean
});

const emit = defineEmits(['close', 'saved', 'deleted']);

const form = ref({
    id: null,
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    reminders: [],
    recurring_event_id: null,
    update_mode: 'single',
    is_recurring: false,
    recurrence: '',
});

const formErrors = ref({});
const isLoading = ref(false);
const showDeleteConfirm = ref(false);

const recurrenceOptions = [
    { value: 'DAILY', label: 'Diariamente (Cada día)' },
    { value: 'WEEKLY', label: 'Semanalmente (Cada semana)' },
    { value: 'MONTHLY', label: 'Mensualmente (Cada mes)' },
    { value: 'YEARLY', label: 'Anualmente (Cada año)' },
];

const reminderOptions = [
    { value: 0, label: 'A la hora del evento' },
    { value: 5, label: '5 minutos antes' },
    { value: 10, label: '10 minutos antes' },
    { value: 15, label: '15 minutos antes' },
    { value: 30, label: '30 minutos antes' },
    { value: 60, label: '1 hora antes' },
    { value: 120, label: '2 horas antes' },
    { value: 1440, label: '1 día antes' },
];

// Initialize form when opened
watch(() => props.show, (newVal) => {
    if (newVal) {
        form.value = JSON.parse(JSON.stringify(props.eventData));
        formErrors.value = {};
    }
});

function addReminder() {
    form.value.reminders.push({ minutes: 0 });
}

function removeReminder(index) {
    form.value.reminders.splice(index, 1);
}

function close() {
    emit('close');
}

async function saveEvent() {
    isLoading.value = true;
    formErrors.value = {};
    
    // Validar lógicamente en cliente para mejor UX
    if (!form.value.end_date) {
        form.value.end_date = form.value.start_date;
    } else if (form.value.end_date < form.value.start_date) {
        form.value.end_date = form.value.start_date;
    }
    
    try {
        if (props.isEditing) {
            await axios.put(route('admin.calendar.update', form.value.id), form.value);
        } else {
            await axios.post(route('admin.calendar.store'), form.value);
        }
        emit('saved');
    } catch (error) {
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors;
        } else {
            console.error(error);
        }
    } finally {
        isLoading.value = false;
    }
}

function triggerDelete() {
    showDeleteConfirm.value = true;
}

async function confirmDeleteEvent() {
    isLoading.value = true;
    try {
        await axios.delete(route('admin.calendar.destroy', form.value.id), {
            data: {
                update_mode: form.value.update_mode,
                recurring_event_id: form.value.recurring_event_id
            }
        });
        showDeleteConfirm.value = false;
        emit('deleted');
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="lg">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center mb-6">
                <CalendarDaysIcon class="h-6 w-6 mr-2 text-emerald-500" />
                {{ isEditing ? 'Editar Evento' : 'Nuevo Evento' }}
            </h3>

            <div v-if="form.recurring_event_id && isEditing" class="mb-6 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/50 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Este es un evento periódico.</h3>
                        <div class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                            <p>¿A qué eventos quieres aplicar los cambios?</p>
                            <div class="mt-3 space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.update_mode" value="single" class="focus:ring-amber-500 h-4 w-4 text-amber-600 border-gray-300 dark:border-zinc-700 dark:bg-zinc-800">
                                    <span class="ml-2 font-medium">Solo esta repetición</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" v-model="form.update_mode" value="series" class="focus:ring-amber-500 h-4 w-4 text-amber-600 border-gray-300 dark:border-zinc-700 dark:bg-zinc-800">
                                    <span class="ml-2 font-medium">Toda la serie</span>
                                    <span class="ml-1 text-xs opacity-75">(Título, Notas y Notificaciones)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <InputLabel for="name" value="Título del Evento" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Reunión de marketing..."
                    />
                    <InputError :message="formErrors.name?.[0]" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="start_date" value="Fecha y Hora Inicio" />
                        <TextInput
                            id="start_date"
                            v-model="form.start_date"
                            type="datetime-local"
                            class="mt-1 block w-full"
                            :disabled="form.recurring_event_id && form.update_mode === 'series'"
                        />
                        <InputError :message="formErrors.start_date?.[0]" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="end_date" value="Fecha y Hora Fin" />
                        <TextInput
                            id="end_date"
                            v-model="form.end_date"
                            type="datetime-local"
                            :min="form.start_date"
                            class="mt-1 block w-full"
                            :disabled="form.recurring_event_id && form.update_mode === 'series'"
                        />
                        <InputError :message="formErrors.end_date?.[0]" class="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel for="description" value="Descripción / Notas (Opcional)" />
                    <TextArea
                        id="description"
                        v-model="form.description"
                        class="mt-1 block w-full"
                        rows="3"
                    />
                    <InputError :message="formErrors.description?.[0]" class="mt-2" />
                </div>

                <div v-if="!isEditing" class="bg-gray-50 dark:bg-zinc-800/50 border border-gray-100 dark:border-zinc-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-zinc-100">Evento Periódico</h4>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Repetir este evento automáticamente en el futuro.</p>
                        </div>
                        <ToggleSwitch v-model:checked="form.is_recurring" />
                    </div>
                    
                    <div v-if="form.is_recurring" class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
                        <InputLabel for="recurrence" value="Frecuencia de Repetición" />
                        <SelectInput
                            id="recurrence"
                            v-model="form.recurrence"
                            class="mt-1 block w-full"
                            :options="recurrenceOptions"
                            placeholder="Selecciona una frecuencia..."
                        />
                        <InputError :message="formErrors.recurrence?.[0]" class="mt-2" />
                    </div>
                </div>
                
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <InputLabel value="Recordatorios" />
                        <button type="button" @click="addReminder" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400 dark:hover:text-emerald-300 transition">+ Añadir notificación</button>
                    </div>
                    
                    <div v-if="form.reminders.length === 0" class="text-sm text-gray-500 italic mb-2">
                        Sin notificaciones programadas.
                    </div>

                    <div v-for="(rem, index) in form.reminders" :key="index" class="flex items-center space-x-2 mb-2">
                        <SelectInput
                            v-model="rem.minutes"
                            class="block w-full text-sm"
                            :options="reminderOptions"
                        />
                        <button type="button" @click="removeReminder(index)" class="text-gray-400 hover:text-red-500 transition px-2">
                            <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <InputError :message="formErrors.reminders?.[0]" class="mt-2" />
                    <p class="text-xs text-gray-500 mt-2">Notificaciones exclusivas del sistema del portal.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row sm:justify-between gap-3 sm:gap-0">
                <div class="order-3 sm:order-1 flex mt-2 sm:mt-0">
                    <DangerButton v-if="isEditing" @click="triggerDelete" class="w-full justify-center sm:w-auto" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                        Eliminar Original
                    </DangerButton>
                </div>
                <div class="order-1 sm:order-2 flex flex-col sm:flex-row gap-2 sm:gap-3">
                    <SecondaryButton @click="close" class="order-2 sm:order-1 w-full justify-center sm:w-auto">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton @click="saveEvent" class="order-1 sm:order-2 w-full justify-center sm:w-auto" :class="{ 'opacity-25': isLoading }" :disabled="isLoading">
                        {{ isEditing ? 'Actualizar' : 'Guardar' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </Modal>

    <ConfirmModal
        :show="showDeleteConfirm"
        title="Eliminar Evento"
        content="¿Estás completamente seguro de que deseas eliminar esto? Esta acción es irreversible."
        confirmText="Sí, Eliminar"
        cancelText="Cancelar"
        @close="showDeleteConfirm = false"
        @confirm="confirmDeleteEvent"
    />
</template>
