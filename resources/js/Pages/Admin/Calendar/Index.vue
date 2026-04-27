<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextArea from '@/Components/TextArea.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ToggleSwitch from '@/Components/ToggleSwitch.vue';
import SelectInput from '@/Components/SelectInput.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import axios from 'axios';

// Núcleo de FullCalendar + Plugins
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const props = defineProps({
    // Recibir props si fuera necesario
});

const calendarRef = ref(null);
const showEventModal = ref(false);
const showDeleteConfirm = ref(false);
const isEditing = ref(false);

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

const recurrenceOptions = [
    { value: 'DAILY', label: 'Diariamente (Cada día)' },
    { value: 'WEEKLY', label: 'Semanalmente (Cada semana)' },
    { value: 'MONTHLY', label: 'Mensualmente (Cada mes)' },
    { value: 'YEARLY', label: 'Anualmente (Cada año)' },
];

const formErrors = ref({});
const isLoading = ref(false);

function addReminder() {
    form.value.reminders.push({ minutes: 0 });
}

function removeReminder(index) {
    form.value.reminders.splice(index, 1);
}

const loadEvents = async (fetchInfo, successCallback, failureCallback) => {
    try {
        const response = await axios.get(route('admin.calendar.events'), {
            params: {
                start: fetchInfo.startStr,
                end: fetchInfo.endStr
            }
        });
        successCallback(response.data);
    } catch (error) {
        failureCallback(error);
        console.error("Error loading events", error);
    }
};

const calendarOptions = ref({
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
    initialView: 'dayGridMonth',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    events: loadEvents,
    editable: true,
    selectable: true,
    selectMirror: true,
    dayMaxEvents: true,
    weekends: true,
    select: handleDateSelect,
    eventClick: handleEventClick,
    eventDrop: handleEventDropResize,
    eventResize: handleEventDropResize,
    height: 'auto',
    themeSystem: 'standard',
    firstDay: 1, // Lunes
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
        week: 'Semana',
        day: 'Día'
    },
    allDayText: 'Todo el día',
    locale: 'es'
});

function resetForm() {
    form.value = {
        id: null,
        name: '',
        description: '',
        start_date: '',
        end_date: '',
        reminders: [],
        recurring_event_id: null,
        update_mode: 'single',
    };
    formErrors.value = {};
}

function handleDateSelect(selectInfo) {
    resetForm();
    form.value.start_date = selectInfo.startStr.slice(0, 16);
    // Ajustar la fecha final por defecto dependiendo de la selección
    form.value.end_date = selectInfo.endStr.slice(0, 16);
    
    // Si es una selección de múltiples días o mes completo, asentar las horas por defecto
    if (selectInfo.allDay) {
        form.value.start_date = selectInfo.startStr + 'T09:00';
        form.value.end_date = selectInfo.startStr + 'T10:00';
    }

    isEditing.value = false;
    showEventModal.value = true;
    calendarRef.value.getApi().unselect(); 
}

function handleEventClick(clickInfo) {
    resetForm();
    const event = clickInfo.event;
    
    // Formatear las fechas entrantes para el input[type="datetime-local"]
    const startStr = event.start ? toLocalDateString(event.start) : '';
    const endStr = event.end ? toLocalDateString(event.end) : startStr;

    form.value = {
        id: event.id,
        name: event.title,
        description: event.extendedProps.description || '',
        start_date: startStr,
        end_date: endStr,
        reminders: Array.isArray(event.extendedProps.reminders) ? [...event.extendedProps.reminders] : [],
        recurring_event_id: event.extendedProps.recurring_event_id || null,
        update_mode: 'single',
        is_recurring: false,
        recurrence: '',
    };
    
    isEditing.value = true;
    showEventModal.value = true;
}

async function handleEventDropResize(info) {
    const event = info.event;
    try {
        await axios.put(route('admin.calendar.update', event.id), {
            name: event.title,
            description: event.extendedProps.description,
            start_date: toLocalDateString(event.start),
            end_date: toLocalDateString(event.end || event.start),
            reminders: event.extendedProps.reminders || []
        });
    } catch (error) {
        info.revert();
        console.error("Error updating event timeline", error);
    }
}

function toLocalDateString(date) {
    const d = new Date(date);
    const tzOffsetMs = d.getTimezoneOffset() * 60000;
    const localISOTime = new Date(d - tzOffsetMs).toISOString().slice(0, 16);
    return localISOTime;
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
        if (isEditing.value) {
            await axios.put(route('admin.calendar.update', form.value.id), form.value);
        } else {
            await axios.post(route('admin.calendar.store'), form.value);
        }
        showEventModal.value = false;
        calendarRef.value.getApi().refetchEvents();
    } catch (error) {
        if (error.response && error.response.status === 422) {
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
        showEventModal.value = false;
        calendarRef.value.getApi().refetchEvents();
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthenticatedLayout title="Calendario">
        <Head title="Calendario de Eventos" />

        <div class="py-6 sm:py-8 lg:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <PageHeader 
                    title="Calendario" 
                    subtitle="Gestiona tus citas, tareas y proyectos de forma eficiente y sincronizada."
                >
                    <template #actions>
                        <PrimaryButton @click="() => { resetForm(); isEditing=false; showEventModal=true; }">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Nuevo Evento
                            </span>
                        </PrimaryButton>
                    </template>
                </PageHeader>

                <div class="mt-6 bg-white dark:bg-zinc-900 border border-transparent dark:border-zinc-800 shadow-lg rounded-xl p-4 sm:p-6 overflow-hidden">
                    <div class="calendar-container">
                        <FullCalendar ref="calendarRef" :options="calendarOptions" />
                    </div>
                </div>

            </div>
        </div>

        <Modal :show="showEventModal" @close="showEventModal = false" maxWidth="lg">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center mb-6">
                    <svg class="h-6 w-6 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    {{ isEditing ? 'Editar Evento' : 'Nuevo Evento' }}
                </h3>

                <div v-if="form.recurring_event_id && isEditing" class="mb-6 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700/50 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
                            <select
                                v-model="rem.minutes"
                                class="block w-full text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 dark:focus:border-emerald-600 focus:ring-emerald-500 dark:focus:ring-emerald-600 rounded-md shadow-sm"
                            >
                                <option :value="0">A la hora del evento</option>
                                <option :value="5">5 minutos antes</option>
                                <option :value="10">10 minutos antes</option>
                                <option :value="15">15 minutos antes</option>
                                <option :value="30">30 minutos antes</option>
                                <option :value="60">1 hora antes</option>
                                <option :value="120">2 horas antes</option>
                                <option :value="1440">1 día antes</option>
                            </select>
                            <button type="button" @click="removeReminder(index)" class="text-gray-400 hover:text-red-500 transition px-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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
                        <SecondaryButton @click="showEventModal = false" class="order-2 sm:order-1 w-full justify-center sm:w-auto">
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
    </AuthenticatedLayout>
</template>

<style>
/* Estilos modernos y adaptados para FullCalendar */
.calendar-container .fc {
    font-family: inherit;
    --fc-page-bg-color: transparent;
    --fc-border-color: #e5e7eb;
    --fc-button-bg-color: #10b981;
    --fc-button-border-color: #10b981;
    --fc-button-hover-bg-color: #059669;
    --fc-button-hover-border-color: #059669;
    --fc-button-active-bg-color: #047857;
    --fc-button-active-border-color: #047857;
    --fc-event-bg-color: #10b981;
    --fc-event-border-color: #10b981;
    --fc-today-bg-color: rgba(16, 185, 129, 0.05);
}

.dark .calendar-container .fc {
    color: #e4e4e7; /* Forzar color de texto claro en etiquetas como 'all-day' y horas del timeline */
    --fc-border-color: rgba(255, 255, 255, 0.05);
    --fc-page-bg-color: transparent;
    --fc-neutral-bg-color: #18181b; /* zinc-900 */
    --fc-neutral-text-color: #e4e4e7; /* zinc-200 */
    --fc-today-bg-color: rgba(255, 255, 255, 0.03);
    
    /* Estilo atenuado sutil para los botones en modo oscuro en vez de purpuras/azules brillantes */
    --fc-button-bg-color: #27272a; /* zinc-800 */
    --fc-button-border-color: #3f3f46; /* zinc-700 */
    --fc-button-hover-bg-color: #3f3f46; 
    --fc-button-hover-border-color: #52525b;
    --fc-button-active-bg-color: #52525b; 
    --fc-button-active-border-color: #71717a;
    
    --fc-event-bg-color: rgba(16, 185, 129, 0.2); /* emerald-500 con transparencia */
    --fc-event-border-color: #10b981; /* emerald-500 */
    --fc-event-text-color: #a7f3d0; /* emerald-200 */
}

.calendar-container .fc-header-toolbar {
    @apply mb-6;
}

.calendar-container .fc-toolbar-title {
    @apply text-xl font-bold text-gray-800 dark:text-zinc-100;
}

.calendar-container .fc-button {
    @apply rounded shadow-sm font-medium px-4 py-2 text-sm focus:ring-2 focus:ring-offset-1 focus:ring-emerald-500 dark:focus:ring-offset-zinc-900 transition-colors;
}

.calendar-container .fc-button-primary:not(:disabled).fc-button-active, 
.calendar-container .fc-button-primary:not(:disabled):active {
    @apply bg-emerald-700 border-emerald-700 dark:bg-zinc-700 dark:border-zinc-600 dark:text-white;
}

.calendar-container .fc-col-header-cell {
    @apply py-3 bg-gray-50 dark:bg-zinc-800/50 text-gray-700 dark:text-zinc-300 font-semibold text-sm border-b dark:border-zinc-700/50;
}

.calendar-container .fc-daygrid-day-number {
    @apply p-2 font-medium text-gray-600 dark:text-zinc-400 transition-colors hover:text-emerald-600 dark:hover:text-emerald-400;
}

.calendar-container .fc-theme-standard td, 
.calendar-container .fc-theme-standard th {
    @apply border-gray-200 dark:border-zinc-800;
}

.calendar-container .fc-day-today .fc-daygrid-day-number {
    @apply bg-emerald-100 dark:bg-emerald-500/20 rounded-full w-8 h-8 flex items-center justify-center shadow-inner font-bold text-emerald-700 dark:text-emerald-300;
}

.calendar-container .fc-event {
    @apply rounded px-2 py-1 font-medium text-xs border-0 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 hover:opacity-100 dark:hover:bg-emerald-500/20 transition-all cursor-pointer border-l-2 border-emerald-500 dark:border-emerald-400 shadow-sm;
}

/* Responsividad para móviles */
@media (max-width: 640px) {
    .calendar-container .fc-header-toolbar {
        flex-direction: column !important;
        gap: 0.75rem;
    }
    
    .calendar-container .fc-toolbar-chunk {
        display: flex;
        justify-content: center;
        width: 100%;
    }

    .calendar-container .fc-toolbar-title {
        font-size: 1.15rem !important;
        text-align: center;
    }
    
    .calendar-container .fc-button {
        padding: 0.35rem 0.75rem;
        font-size: 0.85rem;
    }
}
</style>
