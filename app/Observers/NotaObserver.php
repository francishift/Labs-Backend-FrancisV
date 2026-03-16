<?php

namespace App\Observers;

use App\Models\Nota;
use App\Services\GoogleCalendarService;

class NotaObserver
{
    private $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Handle the Nota "created" event.
     */
    public function created(Nota $nota): void
    {
        if ($nota->sync_calendar) {
            $eventId = $this->calendarService->createEvent($nota);
            if ($eventId) {
                // Actualizamos silenciando los eventos para evitar un loop infinito
                $nota->google_event_id = $eventId;
                $nota->saveQuietly();
            }
        }
    }

    /**
     * Handle the Nota "updated" event.
     */
    public function updated(Nota $nota): void
    {
        // Si antes no tenía sync y ahora sí, creamos el evento en lugar de actualizarlo
        if ($nota->sync_calendar && !$nota->google_event_id) {
             $eventId = $this->calendarService->createEvent($nota);
             if ($eventId) {
                 $nota->google_event_id = $eventId;
                 $nota->saveQuietly();
             }
             return;
        }

        // Si antes tenía sync y ahora no, lo borramos de Google Calendar
        if (!$nota->sync_calendar && $nota->google_event_id && $nota->wasChanged('sync_calendar')) {
            $this->calendarService->deleteEvent($nota->google_event_id);
            $nota->google_event_id = null;
            $nota->saveQuietly();
            return;
        }

        // Si sigue teniendo sync y tiene ID de evento, simplemente actualizamos en Google
        if ($nota->sync_calendar && $nota->google_event_id) {
            $this->calendarService->updateEvent($nota);
        }
    }

    /**
     * Handle the Nota "deleted" event.
     */
    public function deleted(Nota $nota): void
    {
        if ($nota->google_event_id) {
            $this->calendarService->deleteEvent($nota->google_event_id);
        }
    }
}
