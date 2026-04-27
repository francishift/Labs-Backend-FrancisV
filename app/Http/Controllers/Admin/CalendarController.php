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
                            'is_external' => true,
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error reading Google Calendar: " . $e->getMessage());
        }

        return response()->json($eventsArray);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reminders' => 'nullable|array',
            'reminders.*.minutes' => 'required|integer|min:0',
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

            $remindersOverrides = [];
            if (!empty($localEvent->reminders)) {
                foreach ($localEvent->reminders as $r) {
                    $remindersOverrides[] = ['method' => 'popup', 'minutes' => $r['minutes']];
                }
            }
            $remindersParam = new \Google\Service\Calendar\EventReminders();
            if (!empty($remindersOverrides)) {
                $remindersParam->setUseDefault(false);
                $remindersParam->setOverrides($remindersOverrides);
            } else {
                $remindersParam->setUseDefault(false);
            }
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

            if ($calendarEvent->google_event_id) {
                $this->syncGoogleEvent($calendarEvent->google_event_id, $calendarEvent);
            }

            return response()->json($calendarEvent);
        } else {
            // Adoptar (Option B: Google ID string)
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
            $validated['google_event_id'] = $id;
            $calendarEvent = CalendarEvent::create($validated);
            
            $this->syncGoogleEvent($id, $calendarEvent);

            return response()->json($calendarEvent);
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

            $remindersOverrides = [];
            if (is_array($calendarEvent->reminders)) {
                foreach ($calendarEvent->reminders as $r) {
                    $remindersOverrides[] = ['method' => 'popup', 'minutes' => $r['minutes']];
                }
            }
            $remindersParam = new \Google\Service\Calendar\EventReminders();
            if (!empty($remindersOverrides)) {
                $remindersParam->setUseDefault(false);
                $remindersParam->setOverrides($remindersOverrides);
            } else {
                $remindersParam->setUseDefault(false);
            }
            $gEvent->setReminders($remindersParam);

            $service->events->update($calendarId, $googleEventId, $gEvent, ['sendUpdates' => 'none']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error updating Google Calendar: " . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        if (is_numeric($id)) {
            $calendarEvent = CalendarEvent::findOrFail($id);
            if ($calendarEvent->user_id !== auth()->id()) {
                abort(403);
            }

            if ($calendarEvent->google_event_id) {
                try {
                    $service = $this->getGoogleCalendarService();
                    $calendarId = $this->getCalendarId();
                    $service->events->delete($calendarId, $calendarEvent->google_event_id, ['sendUpdates' => 'none']);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error deleting from Google Calendar: " . $e->getMessage());
                }
            }

            $calendarEvent->delete();
        } else {
            // Eliminar elemento exclusivo de Google Calendar
            try {
                $service = $this->getGoogleCalendarService();
                $calendarId = $this->getCalendarId();
                $service->events->delete($calendarId, $id, ['sendUpdates' => 'none']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error deleting from Google Calendar (external): " . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }
}
