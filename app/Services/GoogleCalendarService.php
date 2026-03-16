<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Nota;

class GoogleCalendarService
{
    private $client;
    private $calendarId = 'primary'; // Utiliza el calendario principal del usuario autenticado

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->refreshToken(config('services.google.refresh_token'));
    }

    /**
     * Crea un evento en Google Calendar a partir de una Nota.
     * Retorna el ID del evento de Google.
     */
    public function createEvent(Nota $nota): ?string
    {
        try {
            $service = new Calendar($this->client);
            $event = $this->buildEventFromNota($nota);
            
            $createdEvent = $service->events->insert($this->calendarId, $event);
            return $createdEvent->getId();
        } catch (Exception $e) {
            Log::error('Error creando evento en Google Calendar: ' . $e->getMessage(), ['nota_id' => $nota->id]);
            return null;
        }
    }

    /**
     * Actualiza un evento existente en Google Calendar.
     */
    public function updateEvent(Nota $nota): void
    {
        if (!$nota->google_event_id) {
            return;
        }

        try {
            $service = new Calendar($this->client);
            $event = $this->buildEventFromNota($nota);

            $service->events->update($this->calendarId, $nota->google_event_id, $event);
        } catch (Exception $e) {
            Log::error('Error actualizando evento en Google Calendar: ' . $e->getMessage(), ['nota_id' => $nota->id, 'google_event_id' => $nota->google_event_id]);
        }
    }

    /**
     * Elimina un evento de Google Calendar.
     */
    public function deleteEvent(string $eventId): void
    {
        try {
            $service = new Calendar($this->client);
            $service->events->delete($this->calendarId, $eventId);
        } catch (Exception $e) {
            Log::error('Error eliminando evento en Google Calendar: ' . $e->getMessage(), ['google_event_id' => $eventId]);
        }
    }

    /**
     * Construye un objeto Google Event a partir del modelo Nota.
     */
    private function buildEventFromNota(Nota $nota): Event
    {
        $event = new Event();
        
        // Título: usamos el inicio del comentario si es muy largo, o uno genérico si está vacío
        $summary = $nota->comentario ? substr($nota->comentario, 0, 50) . (strlen($nota->comentario) > 50 ? '...' : '') : 'Nota/Recordatorio';
        $event->setSummary("📌 " . $summary);
        
        $description = $nota->comentario;
        if ($nota->enlace_reunion) {
            $description .= "\n\nEnlace: " . $nota->enlace_reunion;
            $event->setLocation($nota->enlace_reunion);
        }
        $event->setDescription($description);

        $startDateTime = new EventDateTime();
        $endDateTime = clone clone clone $startDateTime; // Clone object to avoid modifying same reference

        if ($nota->hora) {
            // Evento con hora específica
            // Formato DateTime compatible con Google (RFC3339) e.g., "2015-05-28T09:00:00-07:00"
            // Suponemos que la "Timezone" del servidor (o en config/app.php) está configurada correctamente
            $timezone = config('app.timezone', 'Europe/Madrid');
            
            $start = \Carbon\Carbon::parse("{$nota->fecha->format('Y-m-d')} {$nota->hora}", $timezone);
            $startDateTime->setDateTime($start->toRfc3339String());
            $startDateTime->setTimeZone($timezone);
            
            // Fin del evento (1 hora por defecto después del inicio)
            $end = $start->copy()->addHour();
            $endDateTime = new EventDateTime();
            $endDateTime->setDateTime($end->toRfc3339String());
            $endDateTime->setTimeZone($timezone);
        } else {
            // Evento de todo el día
            $startDateTime->setDate($nota->fecha->format('Y-m-d'));
            
            // Para eventos de todo el día en GCalendar, la fecha de fin es exclusiva (al día siguiente al comienzo).
            $endDateTime = new EventDateTime();
            $endDateTime->setDate($nota->fecha->copy()->addDay()->format('Y-m-d'));
        }

        $event->setStart($startDateTime);
        $event->setEnd($endDateTime);

        // Notificaciones: Las desactivamos por defecto en Google Calendar para que solo suene la PWA y no haya aviso doble.
        $reminders = new \Google\Service\Calendar\EventReminders();
        $reminders->setUseDefault(false); // Quita todos los avisos por defecto de Google
        $event->setReminders($reminders);

        return $event;
    }
}
