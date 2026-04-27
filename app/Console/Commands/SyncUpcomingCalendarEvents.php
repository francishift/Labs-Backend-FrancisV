<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;

class SyncUpcomingCalendarEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:sync-upcoming';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza eventos de Google Calendar de los próximos 30 días para agendar notificaciones locales.';

    protected function getGoogleCalendarService()
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        $refresh = config('services.google.refresh_token');
        if ($refresh) {
            $accessToken = $client->fetchAccessTokenWithRefreshToken($refresh);
            if (!isset($accessToken['error'])) {
                $client->setAccessToken($accessToken);
            }
        }

        return new GoogleCalendarService($client);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando sincronización de Google Calendar...");
        
        try {
            $service = $this->getGoogleCalendarService();
            $calendarId = env('GOOGLE_CALENDAR_ID', 'primary');
            
            $optParams = [
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'timeMin' => Carbon::now()->format('c'),
                'timeMax' => Carbon::now()->addDays(30)->format('c'),
            ];
                
            $results = $service->events->listEvents($calendarId, $optParams);
            $events = $results->getItems();
            $count = 0;

            foreach ($events as $event) {
                $googleEventId = $event->getId(); // El ID de la instancia
                $recurringId = $event->getRecurringEventId();
                if (!$recurringId && preg_match('/_([0-9]{8})(T[0-9]{6}Z)?$/', $googleEventId)) {
                    $recurringId = preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $googleEventId);
                }

                $remindersOverrides = [];
                if ($recurringId) {
                    $localMaster = CalendarEvent::where('google_event_id', $recurringId)->first();
                    if ($localMaster && is_array($localMaster->reminders)) {
                        foreach ($localMaster->reminders as $rem) {
                            $remindersOverrides[] = [
                                'minutes' => (int) $rem['minutes'],
                                'notified' => false
                            ];
                        }
                    }
                }
                
                // Si el evento tiene recordatorios activos (de lo contrario no necesitamos notificar localmente)
                if (!empty($remindersOverrides)) {
                    
                    // Verificar si ya existe en la BD local
                    $localEvent = CalendarEvent::where('google_event_id', $googleEventId)->first();
                    
                    $startItem = clone ($event->getStart()->getDateTime() ? Carbon::parse($event->getStart()->getDateTime()) : Carbon::parse($event->getStart()->getDate()));
                    $endItem = clone ($event->getEnd()->getDateTime() ? Carbon::parse($event->getEnd()->getDateTime()) : Carbon::parse($event->getEnd()->getDate()));
                    
                    if (!$localEvent) {
                        // Lo creamos como caché local para las notificaciones
                        CalendarEvent::create([
                            'user_id' => 1, // Por defecto al principal si no hay sesión
                            'name' => $event->getSummary() ?: '(Sin título)',
                            'description' => $event->getDescription(),
                            'start_date' => $startItem,
                            'end_date' => $endItem,
                            'google_event_id' => $googleEventId,
                            'reminders' => $remindersOverrides,
                        ]);
                        $count++;
                    } else {
                        // Si existe, nos aseguramos que tenga los recordatorios frescos
                        // y que conservemos el estado notified
                        $currentRems = is_array($localEvent->reminders) ? $localEvent->reminders : [];
                        $newRems = [];
                        foreach ($remindersOverrides as $newRem) {
                            $notified = false;
                            foreach ($currentRems as $cr) {
                                if ((int)$cr['minutes'] === $newRem['minutes'] && isset($cr['notified'])) {
                                    $notified = $cr['notified'];
                                }
                            }
                            $newRems[] = [
                                'minutes' => $newRem['minutes'],
                                'notified' => $notified
                            ];
                        }
                        
                        $localEvent->update([
                            'name' => $event->getSummary() ?: '(Sin título)',
                            'start_date' => $startItem,
                            'end_date' => $endItem,
                            'reminders' => $newRems,
                        ]);
                    }
                }
            }

            $this->info("Sincronización completada. Se importaron/actualizaron {$count} instancias futuras en la BD local.");

        } catch (\Exception $e) {
            $this->error("Error al sincronizar: " . $e->getMessage());
        }
    }
}
