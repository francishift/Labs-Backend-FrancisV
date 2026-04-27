<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\Event as GoogleServiceEvent;
use Google\Service\Calendar\EventDateTime as GoogleEventDateTime;

class CalendarController extends Controller
{
    protected function getGoogleCalendarService()
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        $accessToken = $client->fetchAccessTokenWithRefreshToken(config('services.google.refresh_token'));
        if (!isset($accessToken['error'])) {
            $client->setAccessToken($accessToken);
        }

        return new GoogleCalendarService($client);
    }

    protected function getCalendarId()
    {
        return env('GOOGLE_CALENDAR_ID', 'primary');
    }

    public function index()
    {
        return Inertia::render('Admin/Calendar/Index');
    }

    public function events(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $localEvents = CalendarEvent::where('user_id', auth()->id())->get();
        $mapped = $localEvents->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->name,
                'start' => $event->start_date->toIso8601String(),
                'end' => $event->end_date->toIso8601String(),
                'extendedProps' => [
                    'description' => $event->description,
                    'reminders' => is_array($event->reminders) ? $event->reminders : [],
                    'google_event_id' => $event->google_event_id,
                    'recurring_event_id' => $event->google_event_id && preg_match('/_([0-9]{8})(T[0-9]{6}Z)?$/', $event->google_event_id) ? preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $event->google_event_id) : null,
                    'is_external' => false,
                ]
            ];
        });

        $eventsArray = $mapped->toArray();
        $skipGoogleIds = $localEvents->pluck('google_event_id')->filter()->toArray();

        try {
            $service = $this->getGoogleCalendarService();
            $calendarId = $this->getCalendarId();
            
            $optParams = [
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];
            
            if ($start) {
                $optParams['timeMin'] = Carbon::parse($start)->format('c');
            }
            if ($end) {
                $optParams['timeMax'] = Carbon::parse($end)->format('c');
            }
                
            $results = $service->events->listEvents($calendarId, $optParams);
            
            foreach ($results->getItems() as $event) {
                if (!in_array($event->getId(), $skipGoogleIds)) {
                    $startItem = clone ($event->getStart()->getDateTime() ? Carbon::parse($event->getStart()->getDateTime()) : Carbon::parse($event->getStart()->getDate()));
                    $endItem = clone ($event->getEnd()->getDateTime() ? Carbon::parse($event->getEnd()->getDateTime()) : Carbon::parse($event->getEnd()->getDate()));
                    
                    $masterId = $event->getRecurringEventId();
                    if (!$masterId && preg_match('/_([0-9]{8})(T[0-9]{6}Z)?$/', $event->getId())) {
                        $masterId = preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $event->getId());
                    }

                    $isOurs = false;
                    $localMaster = null;
                    if ($masterId) {
                        $localMaster = $localEvents->where('google_event_id', $masterId)->first();
                    }

                    if ($localMaster) {
                        $isOurs = true;
                        // Prevenir duplicado del primer día: Eliminar el bloque estático local insertado previamente
                        foreach ($eventsArray as $idx => $ea) {
                            if (isset($ea['extendedProps']['google_event_id']) && $ea['extendedProps']['google_event_id'] === $masterId) {
                                unset($eventsArray[$idx]);
                            }
                        }
                        
                        $remindersList = [];
                        if (is_array($localMaster->reminders)) {
                            foreach ($localMaster->reminders as $rem) {
                                $remindersList[] = [
                                    'minutes' => (int) $rem['minutes'],
                                    'notified' => false
                                ];
                            }
                        }
                        
                        $eventsArray[] = [
                            'id' => $event->getId(), // Mantenemos el ID string de Google para permitir aislar excepciones locales luego
                            'title' => $localMaster->name,
                            'start' => $startItem->toIso8601String(),
                            'end' => $endItem->toIso8601String(),
                            // No inyectamos bg_color para que herede el diseño verde nativo (Emerald)
                            'extendedProps' => [
                                'description' => $localMaster->description,
                                'reminders' => $remindersList,
                                'google_event_id' => $event->getId(),
                                'recurring_event_id' => $masterId,
                                'is_external' => false, // Lo tratamos como propio
                            ]
                        ];
                    } else {
                        // Evento totalmente externo (Zinc)
                        $eventsArray[] = [
                            'id' => $event->getId(),
                            'title' => $event->getSummary() ?: '(Sin título)',
                            'start' => $startItem->toIso8601String(),
                            'end' => $endItem->toIso8601String(),
                            'backgroundColor' => '#27272a', // zinc-800
                            'borderColor' => '#3f3f46', // zinc-700
                            'textColor' => '#a1a1aa', // zinc-400
                            'extendedProps' => [
                                'description' => $event->getDescription(),
                                'reminders' => [],
                                'google_event_id' => $event->getId(),
                                'recurring_event_id' => $masterId,
                                'is_external' => true,
                            ]
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error reading Google Calendar: " . $e->getMessage());
        }

        return response()->json(array_values($eventsArray));
    }

    public function store(Request $request)
    {
        if (!$request->filled('end_date')) {
            $request->merge(['end_date' => $request->input('start_date')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reminders' => 'nullable|array',
            'reminders.*.minutes' => 'required|integer|min:0',
            'is_recurring' => 'nullable|boolean',
            'recurrence' => 'nullable|string|in:DAILY,WEEKLY,MONTHLY,YEARLY',
        ]);

        $remindersData = [];
        if (!empty($validated['reminders'])) {
            foreach ($validated['reminders'] as $rem) {
                $remindersData[] = [
                    'minutes' => (int) $rem['minutes'],
                    'notified' => false
                ];
            }
        }
        $validated['reminders'] = $remindersData;

        $validated['user_id'] = auth()->id();
        $localEvent = CalendarEvent::create($validated);

        try {
            $service = $this->getGoogleCalendarService();
            $calendarId = $this->getCalendarId();
            
            $gEvent = new GoogleServiceEvent([
                'summary' => $localEvent->name,
                'description' => $localEvent->description ?? '',
                'start' => new GoogleEventDateTime([
                    'dateTime' => Carbon::parse($localEvent->start_date)->format('c'),
                    'timeZone' => config('app.timezone'),
                ]),
                'end' => new GoogleEventDateTime([
                    'dateTime' => Carbon::parse($localEvent->end_date)->format('c'),
                    'timeZone' => config('app.timezone'),
                ]),
            ]);

            if ($request->boolean('is_recurring') && !empty($validated['recurrence'])) {
                $gEvent->setRecurrence(["RRULE:FREQ=" . $validated['recurrence']]);
            }

            $remindersParam = new \Google\Service\Calendar\EventReminders();
            $remindersParam->setUseDefault(false);
            $remindersParam->setOverrides([]);
            $gEvent->setReminders($remindersParam);


            // Desactivar notificaciones por correo de Google a los invitados
            $optParams = ['sendUpdates' => 'none'];

            $savedGoogleEvent = $service->events->insert($calendarId, $gEvent, $optParams);
            
            $localEvent->update(['google_event_id' => $savedGoogleEvent->getId()]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error syncing Google Calendar: " . $e->getMessage());
        }

        return response()->json($localEvent, 201);
    }

    public function update($id, Request $request)
    {
        if (!$request->filled('end_date')) {
            $request->merge(['end_date' => $request->input('start_date')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reminders' => 'nullable|array',
            'reminders.*.minutes' => 'required|integer|min:0',
        ]);

        if (is_numeric($id)) {
            $calendarEvent = CalendarEvent::findOrFail($id);
            if ($calendarEvent->user_id !== auth()->id()) {
                abort(403);
            }

            $remindersData = [];
            $oldReminders = is_array($calendarEvent->reminders) ? $calendarEvent->reminders : [];
            $startChanged = $calendarEvent->start_date != $validated['start_date'];

            if (!empty($validated['reminders'])) {
                foreach ($validated['reminders'] as $rem) {
                    $m = (int) $rem['minutes'];
                    $wasNotified = false;
                    if (!$startChanged) {
                        foreach ($oldReminders as $or) {
                            if ($or['minutes'] == $m && isset($or['notified'])) {
                                $wasNotified = $or['notified'];
                            }
                        }
                    }
                    $remindersData[] = [
                        'minutes' => $m,
                        'notified' => $wasNotified
                    ];
                }
            }
            $validated['reminders'] = $remindersData;

            $calendarEvent->update($validated);

            $updateMode = $request->input('update_mode', 'single');
            $recurringId = $request->input('recurring_event_id');

            if ($updateMode === 'series' && $recurringId) {
                $this->syncGoogleSeries($recurringId, $calendarEvent);
            } elseif ($calendarEvent->google_event_id) {
                $this->syncGoogleEvent($calendarEvent->google_event_id, $calendarEvent);
            }

            return response()->json($calendarEvent);
        } else {
            $updateMode = $request->input('update_mode', 'single');
            $recurringId = $request->input('recurring_event_id');

            // Preparar reminders en caso de que vayan hacia Google
            $remindersData = [];
            if (!empty($validated['reminders'])) {
                foreach ($validated['reminders'] as $rem) {
                    $remindersData[] = [
                        'minutes' => (int) $rem['minutes'],
                        'notified' => false
                    ];
                }
            }
            $validated['reminders'] = $remindersData;
            $validated['user_id'] = auth()->id();

            \Illuminate\Support\Facades\Log::info("DEBUG UPDATE CALENDAR", [
                'updateMode' => $updateMode,
                'recurringId' => $recurringId,
                'id' => $id
            ]);

            if ($updateMode === 'series' && $recurringId) {
                // Modificar toda la serie maestra en Google sin asentar una nueva instancia aisalmente local
                $dummyEvent = new CalendarEvent($validated);
                $this->syncGoogleSeries($recurringId, $dummyEvent);
                return response()->json(['success' => true]);
            }

            // Adoptar (Option B: Google ID string) - Solo esta ocurrencia
            $validated['google_event_id'] = $id;
            $validated['google_event_id'] = $id;
            $calendarEvent = CalendarEvent::create($validated);
            
            $this->syncGoogleEvent($id, $calendarEvent);

            return response()->json($calendarEvent);
        }
    }

    protected function syncGoogleSeries($recurringEventId, CalendarEvent $calendarEvent)
    {
        try {
            $service = $this->getGoogleCalendarService();
            $calendarId = $this->getCalendarId();

            $gEvent = $service->events->get($calendarId, $recurringEventId);
            $gEvent->setSummary($calendarEvent->name);
            $gEvent->setDescription($calendarEvent->description ?? '');
            
            // Limitamos a no editar fechas en actualizacion de series enteras para conservar RRULE original

            $remindersParam = new \Google\Service\Calendar\EventReminders();
            $remindersParam->setUseDefault(false);
            $remindersParam->setOverrides([]);
            $gEvent->setReminders($remindersParam);

            $service->events->update($calendarId, $recurringEventId, $gEvent, ['sendUpdates' => 'none']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error updating Google Calendar Series: " . $e->getMessage());
        }
    }

    protected function syncGoogleEvent($googleEventId, CalendarEvent $calendarEvent)
    {
        try {
            $service = $this->getGoogleCalendarService();
            $calendarId = $this->getCalendarId();

            $gEvent = $service->events->get($calendarId, $googleEventId);
            $gEvent->setSummary($calendarEvent->name);
            $gEvent->setDescription($calendarEvent->description ?? '');
            
            $start = new GoogleEventDateTime();
            $start->setDateTime(Carbon::parse($calendarEvent->start_date)->format('c'));
            $start->setTimeZone(config('app.timezone'));
            $gEvent->setStart($start);

            $end = new GoogleEventDateTime();
            $end->setDateTime(Carbon::parse($calendarEvent->end_date)->format('c'));
            $end->setTimeZone(config('app.timezone'));
            $gEvent->setEnd($end);

            $remindersParam = new \Google\Service\Calendar\EventReminders();
            $remindersParam->setUseDefault(false);
            $remindersParam->setOverrides([]);
            $gEvent->setReminders($remindersParam);

            $service->events->update($calendarId, $googleEventId, $gEvent, ['sendUpdates' => 'none']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error updating Google Calendar: " . $e->getMessage());
        }
    }

    public function destroy($id, Request $request)
    {
        $updateMode = $request->input('update_mode', 'single');
        $recurringId = $request->input('recurring_event_id');

        if (is_numeric($id)) {
            $calendarEvent = CalendarEvent::findOrFail($id);
            if ($calendarEvent->user_id !== auth()->id()) {
                abort(403);
            }

            if ($calendarEvent->google_event_id) {
                try {
                    $service = $this->getGoogleCalendarService();
                    $calendarId = $this->getCalendarId();
                    
                    if ($updateMode === 'series' && $recurringId) {
                        $service->events->delete($calendarId, $recurringId, ['sendUpdates' => 'none']);
                    } else {
                        $service->events->delete($calendarId, $calendarEvent->google_event_id, ['sendUpdates' => 'none']);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error deleting from Google Calendar: " . $e->getMessage());
                }
            }

            // Si borramos la serie, tiene sentido purgar instancias locales atadas a ese Master
            if ($updateMode === 'series' && $recurringId) {
                CalendarEvent::where('google_event_id', 'like', $recurringId . '_%')->delete();
            } else {
                $calendarEvent->delete();
            }
        } else {
            // Eliminar elemento externo exclusivo de Google Calendar
            try {
                $service = $this->getGoogleCalendarService();
                $calendarId = $this->getCalendarId();
                $targetId = ($updateMode === 'series' && $recurringId) ? $recurringId : $id;
                $service->events->delete($calendarId, $targetId, ['sendUpdates' => 'none']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error deleting from Google Calendar (external): " . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }
}
