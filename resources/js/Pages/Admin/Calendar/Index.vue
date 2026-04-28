<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import PageHeader from '@/Components/PageHeader.vue';
import EventFormModal from './EventFormModal.vue';
import { PlusIcon } from '@heroicons/vue/24/outline';
import axios from 'axios';

// Núcleo de FullCalendar + Plugins
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

const calendarRef = ref(null);
const showEventModal = ref(false);
const isEditing = ref(false);

const defaultEventData = {
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
};

const selectedEventData = ref({ ...defaultEventData });

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

function handleDateSelect(selectInfo) {
    let start_date = selectInfo.startStr.slice(0, 16);
    let end_date = selectInfo.endStr.slice(0, 16);
    
    // Si es una selección de múltiples días o mes completo, asentar las horas por defecto
    if (selectInfo.allDay) {
        start_date = selectInfo.startStr + 'T09:00';
        end_date = selectInfo.startStr + 'T10:00';
    }

    selectedEventData.value = {
        ...defaultEventData,
        start_date,
        end_date
    };

    isEditing.value = false;
    showEventModal.value = true;
    calendarRef.value.getApi().unselect(); 
}

function handleEventClick(clickInfo) {
    const event = clickInfo.event;
    
    // Formatear las fechas entrantes para el input[type="datetime-local"]
    const startStr = event.start ? toLocalDateString(event.start) : '';
    const endStr = event.end ? toLocalDateString(event.end) : startStr;

    selectedEventData.value = {
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

function onEventSavedOrDeleted() {
    showEventModal.value = false;
    calendarRef.value.getApi().refetchEvents();
}

function createNewForm() {
    selectedEventData.value = { ...defaultEventData };
    isEditing.value = false;
    showEventModal.value = true;
}
</script>

<template>
    <AuthenticatedLayout title="Calendario">
        <Head title="Calendario de Eventos" />

        <div class="py-6 sm:py-8 lg:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <PageHeader 
                    title="Calendario" 
                    subtitle="Gestiona citas, tareas y proyectos de forma eficiente y sincronizada."
                >
                    <template #actions>
                        <PrimaryButton @click="createNewForm">
                            <span class="flex items-center">
                                <PlusIcon class="w-5 h-5 mr-2" />
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

        <EventFormModal 
            :show="showEventModal" 
            :isEditing="isEditing"
            :eventData="selectedEventData"
            @close="showEventModal = false"
            @saved="onEventSavedOrDeleted"
            @deleted="onEventSavedOrDeleted"
        />

    </AuthenticatedLayout>
</template>
