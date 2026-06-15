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

const props = defineProps({
    initialDate: String,
});

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
    is_all_day: false,
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

const isMobile = typeof window !== 'undefined' && window.innerWidth < 768;

const calendarOptions = ref({
    plugins: [ dayGridPlugin, timeGridPlugin, interactionPlugin ],
    initialView: 'timeGridDay',
    initialDate: props.initialDate || undefined,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    scrollTime: '08:00:00', // Las 24h están disponibles, pero el scroll baja automáticamente a las 8:00 AM
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
    slotMinTime: '08:00:00',
    slotMaxTime: '32:00:00',
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
        week: 'Semana',
        day: 'Día'
    },
    allDayContent: function(arg) {
        return { html: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mx-auto text-gray-500 dark:text-zinc-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>' };
    },
    eventContent: function(arg) {
        let title = arg.event.title;
        let timeText = arg.timeText;
        
        return {
            html: `<div class="flex flex-col h-full overflow-hidden leading-tight py-0.5">
                       <span class="font-semibold text-[11px] md:text-xs text-emerald-800 dark:text-emerald-200 truncate shrink-0" title="${title}">
                           ${title}
                       </span>
                       ${timeText ? `
                       <span class="text-[9px] md:text-[10px] text-emerald-600/80 dark:text-emerald-400/80 mt-0.5 font-medium truncate">
                           ${timeText}
                       </span>` : ''}
                   </div>`
        };
    },
    dayHeaderContent: function(arg) {
        const date = arg.date;
        const isMobile = typeof window !== 'undefined' && window.innerWidth < 768;
        
        // Formatear el nombre del día
        let dayName = new Intl.DateTimeFormat('es', { 
            weekday: isMobile ? 'short' : 'long' 
        }).format(date).replace('.', '');
        
        // Si es la vista mensual, solo mostramos el nombre del día (sin número/fecha de ejemplo)
        if (arg.view.type === 'dayGridMonth') {
            return {
                html: `<div class="flex flex-col items-center justify-center leading-tight py-1">
                           <span class="font-semibold text-xs md:text-sm capitalize text-gray-700 dark:text-zinc-200">
                               ${dayName}
                           </span>
                       </div>`
            };
        }
        
        // Formatear el número/fecha
        let dateNum = isMobile 
            ? new Intl.DateTimeFormat('es', { day: '2-digit', month: '2-digit' }).format(date)
            : new Intl.DateTimeFormat('es', { day: 'numeric' }).format(date);
            
        return {
            html: `<div class="flex flex-col items-center justify-center leading-tight py-1">
                       <span class="font-semibold text-xs md:text-sm capitalize text-gray-700 dark:text-zinc-200">
                           ${dayName}
                       </span>
                       <span class="font-bold text-sm md:text-lg text-emerald-600 dark:text-emerald-400 mt-0.5">
                           ${dateNum}
                       </span>
                   </div>`
        };
    },
    locale: 'es',
    // --- Mejores prácticas para móvil ---
    longPressDelay: 250,       // Tiempo para considerar un 'mantener pulsado'
    eventLongPressDelay: 250,  // Previene arrastrar eventos accidentalmente al hacer scroll
    selectLongPressDelay: 250, // Previene seleccionar celdas accidentalmente al hacer scroll
    windowResize: function() {
        if (!calendarRef.value) return;
        const width = window.innerWidth;
        const api = calendarRef.value.getApi();
        // Mantenemos la misma toolbar para tener las mismas opciones en móvil y escritorio.
        // FullCalendar envuelve automáticamente los botones si falta espacio.
        api.setOption('headerToolbar', { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' });
    }
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
        is_all_day: event.allDay || false,
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
        <template #header>
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
        </template>

        <Head title="Calendario de Eventos" />

        <div class="py-6 sm:py-8 lg:py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-0">

                <div class="bg-white dark:bg-zinc-900 border border-transparent dark:border-zinc-800 shadow-lg rounded-xl p-4 sm:p-6 overflow-hidden">
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
