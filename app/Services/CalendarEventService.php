<?php

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Google\Service\Calendar\Event as GoogleServiceEvent;
use Google\Service\Calendar\EventDateTime as GoogleEventDateTime;
use Google\Service\Calendar\EventReminders;
use Exception;
use Illuminate\Support\Facades\Log;

class CalendarEventService
{
    private GoogleCalendarApiService $googleApiService;

    public function __construct(GoogleCalendarApiService $googleApiService)
    {
        $this->googleApiService = $googleApiService;
    }

    public function obtenerEventosCombinados(?string $fechaInicio, ?string $fechaFin, int $userId): array
    {
        $eventosLocales = CalendarEvent::where('user_id', $userId)->get();
        
        $eventosMapeados = $eventosLocales->map(function ($evento) {
            $masterId = null;
            if ($evento->google_event_id && preg_match('/_([0-9]{8})(T[0-9]{6}Z)?$/', $evento->google_event_id)) {
                $masterId = preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $evento->google_event_id);
            }

            $arrayPayload = [
                'id' => $evento->id,
                'title' => $evento->name,
                'start' => $evento->start_date->toIso8601String(),
                'end' => $evento->end_date->toIso8601String(),
                'allDay' => (bool) $evento->is_all_day,
                'extendedProps' => [
                    'description' => $evento->description,
                    'reminders' => is_array($evento->reminders) ? $evento->reminders : [],
                    'google_event_id' => $evento->google_event_id,
                    'recurring_event_id' => $masterId,
                    'is_external' => false,
                ]
            ];

            if ($evento->is_all_day) {
                $arrayPayload['backgroundColor'] = '#10b981'; // emerald-500
                $arrayPayload['borderColor'] = '#059669'; // emerald-600
                $arrayPayload['textColor'] = '#ffffff';
            }

            return $arrayPayload;
        });

        $arrayEventos = $eventosMapeados->toArray();
        $idsGoogleIgnorar = $eventosLocales->pluck('google_event_id')->filter()->toArray();

        try {
            $opcionesGoogle = [
                'singleEvents' => true,
                'orderBy' => 'startTime',
            ];
            
            if ($fechaInicio) {
                $opcionesGoogle['timeMin'] = Carbon::parse($fechaInicio)->format('c');
            }
            if ($fechaFin) {
                $opcionesGoogle['timeMax'] = Carbon::parse($fechaFin)->format('c');
            }
                
            $resultadosGoogle = $this->googleApiService->listEvents($opcionesGoogle);
            
            foreach ($resultadosGoogle->getItems() as $eventoGoogle) {
                if (!in_array($eventoGoogle->getId(), $idsGoogleIgnorar)) {
                    $inicioItem = clone ($eventoGoogle->getStart()->getDateTime() ? Carbon::parse($eventoGoogle->getStart()->getDateTime()) : Carbon::parse($eventoGoogle->getStart()->getDate()));
                    $finItem = clone ($eventoGoogle->getEnd()->getDateTime() ? Carbon::parse($eventoGoogle->getEnd()->getDateTime()) : Carbon::parse($eventoGoogle->getEnd()->getDate()));
                    
                    $masterId = $eventoGoogle->getRecurringEventId();
                    if (!$masterId && preg_match('/_([0-9]{8})(T[0-9]{6}Z)?$/', $eventoGoogle->getId())) {
                        $masterId = preg_replace('/_([0-9]{8})(T[0-9]{6}Z)?$/', '', $eventoGoogle->getId());
                    }

                    $eventoLocalRelacionado = null;
                    $coincidenciaExacta = $eventosLocales->where('google_event_id', $eventoGoogle->getId())->first();

                    if ($coincidenciaExacta) {
                        $eventoLocalRelacionado = $coincidenciaExacta;
                    } elseif ($masterId) {
                        $eventoLocalRelacionado = $eventosLocales->where('google_event_id', $masterId)->first();
                    }

                    if ($eventoLocalRelacionado) {
                        // Prevenir duplicado del primer día: Eliminar el bloque estático local insertado previamente
                        foreach ($arrayEventos as $idx => $ea) {
                            if (isset($ea['extendedProps']['google_event_id']) && $ea['extendedProps']['google_event_id'] === $eventoLocalRelacionado->google_event_id) {
                                unset($arrayEventos[$idx]);
                            }
                        }
                        
                        $listaRecordatorios = [];
                        if (is_array($eventoLocalRelacionado->reminders)) {
                            foreach ($eventoLocalRelacionado->reminders as $rem) {
                                $listaRecordatorios[] = [
                                    'minutes' => (int) $rem['minutes'],
                                    'notified' => false
                                ];
                            }
                        }
                        
                        $isAllDay = empty($eventoGoogle->getStart()->getDateTime());
                        $payloadSync = [
                            'id' => $eventoGoogle->getId(),
                            'title' => $eventoLocalRelacionado->name,
                            'start' => $inicioItem->toIso8601String(),
                            'end' => $finItem->toIso8601String(),
                            'allDay' => $isAllDay,
                            'extendedProps' => [
                                'description' => $eventoLocalRelacionado->description,
                                'reminders' => $listaRecordatorios,
                                'google_event_id' => $eventoGoogle->getId(),
                                'recurring_event_id' => $masterId,
                                'is_external' => false,
                            ]
                        ];

                        if ($isAllDay) {
                            $payloadSync['backgroundColor'] = '#10b981'; // emerald-500
                            $payloadSync['borderColor'] = '#059669'; // emerald-600
                            $payloadSync['textColor'] = '#ffffff';
                        }
                        $arrayEventos[] = $payloadSync;
                    } else {
                        // Evento totalmente externo
                        $arrayEventos[] = [
                            'id' => $eventoGoogle->getId(),
                            'title' => $eventoGoogle->getSummary() ?: '(Sin título)',
                            'start' => $inicioItem->toIso8601String(),
                            'end' => $finItem->toIso8601String(),
                            'allDay' => empty($eventoGoogle->getStart()->getDateTime()),
                            'backgroundColor' => '#27272a',
                            'borderColor' => '#3f3f46',
                            'textColor' => '#a1a1aa',
                            'extendedProps' => [
                                'description' => $eventoGoogle->getDescription(),
                                'reminders' => [],
                                'google_event_id' => $eventoGoogle->getId(),
                                'recurring_event_id' => $masterId,
                                'is_external' => true,
                            ]
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("Error leyendo Google Calendar: " . $e->getMessage());
            // No bloqueamos la UI si Google falla al leer, devolvemos los locales.
        }

        return array_values($arrayEventos);
    }

    public function crearEventoLocalYGoogle(array $datosValidados, int $userId): CalendarEvent
    {
        $datosValidados['user_id'] = $userId;
        $datosValidados['reminders'] = $this->formatearRecordatorios($datosValidados['reminders'] ?? []);
        
        $eventoLocal = CalendarEvent::create($datosValidados);

        try {
            $gEvent = new GoogleServiceEvent([
                'summary' => $eventoLocal->name,
                'description' => $eventoLocal->description ?? '',
            ]);
            
            $start = new GoogleEventDateTime();
            $end = new GoogleEventDateTime();
            
            if ($eventoLocal->is_all_day) {
                $start->setDate(Carbon::parse($eventoLocal->start_date)->format('Y-m-d'));
                // Para Google, el end date de todo el día es exclusivo (+1 día)
                $end->setDate(Carbon::parse($eventoLocal->end_date)->addDay()->format('Y-m-d'));
            } else {
                $start->setDateTime(Carbon::parse($eventoLocal->start_date)->format('c'));
                $start->setTimeZone(config('app.timezone'));
                $end->setDateTime(Carbon::parse($eventoLocal->end_date)->format('c'));
                $end->setTimeZone(config('app.timezone'));
            }
            $gEvent->setStart($start);
            $gEvent->setEnd($end);

            if (!empty($datosValidados['is_recurring']) && !empty($datosValidados['recurrence'])) {
                $gEvent->setRecurrence(["RRULE:FREQ=" . $datosValidados['recurrence']]);
            }

            $gEvent->setReminders($this->construirRecordatoriosVaciosGoogle());

            $opciones = ['sendUpdates' => 'none'];
            $eventoGuardadoGoogle = $this->googleApiService->insertEvent($gEvent, $opciones);
            
            $eventoLocal->update(['google_event_id' => $eventoGuardadoGoogle->getId()]);
        } catch (Exception $e) {
            // Propagamos la excepción para que el controlador pueda devolver error 500
            Log::error("Error sincronizando Google Calendar al crear: " . $e->getMessage());
            throw new Exception("El evento se guardó localmente, pero falló la sincronización con Google: " . $e->getMessage());
        }

        return $eventoLocal;
    }

    public function actualizarEventoExistente(string $idOCodigoGoogle, array $datosValidados, int $userId): CalendarEvent
    {
        $modoActualizacion = $datosValidados['update_mode'] ?? 'single';
        $idRecurrenteGoogle = $datosValidados['recurring_event_id'] ?? null;
        
        if (is_numeric($idOCodigoGoogle)) {
            // Actualización de un evento local con ID numérico
            $eventoLocal = CalendarEvent::findOrFail($idOCodigoGoogle);
            if ($eventoLocal->user_id !== $userId) {
                throw new Exception("No tienes permiso para editar este evento.", 403);
            }

            $datosValidados['reminders'] = $this->preservarRecordatoriosViejos(
                $datosValidados['reminders'] ?? [],
                $eventoLocal->reminders,
                $eventoLocal->start_date != $datosValidados['start_date']
            );

            $eventoLocal->update($datosValidados);

            if ($modoActualizacion === 'series' && $idRecurrenteGoogle) {
                $this->sincronizarSerieGoogle($idRecurrenteGoogle, $eventoLocal);
            } elseif ($eventoLocal->google_event_id) {
                $this->sincronizarEventoAisladoGoogle($eventoLocal->google_event_id, $eventoLocal);
            }

            return $eventoLocal;
        } else {
            // Actualización de una instancia aislada que no existía localmente aún (Adoptar)
            $datosValidados['user_id'] = $userId;
            $datosValidados['reminders'] = $this->formatearRecordatorios($datosValidados['reminders'] ?? []);

            if ($modoActualizacion === 'series' && $idRecurrenteGoogle) {
                // Modificar toda la serie maestra localmente
                $eventoMaestro = CalendarEvent::updateOrCreate(
                    [
                        'google_event_id' => $idRecurrenteGoogle,
                        'user_id' => $userId,
                    ],
                    [
                        'name' => $datosValidados['name'],
                        'description' => $datosValidados['description'] ?? null,
                        'start_date' => $datosValidados['start_date'],
                        'end_date' => $datosValidados['end_date'],
                        'reminders' => $datosValidados['reminders'],
                    ]
                );

                // Propagar a instancias en caché
                CalendarEvent::where('google_event_id', 'like', $idRecurrenteGoogle . '\_%')
                    ->where('user_id', $userId)
                    ->update([
                        'name' => $datosValidados['name'],
                        'description' => $datosValidados['description'] ?? null,
                        'reminders' => $datosValidados['reminders'],
                    ]);
                
                $this->sincronizarSerieGoogle($idRecurrenteGoogle, $eventoMaestro);
                return $eventoMaestro;
            }

            // Adoptar solo esta ocurrencia aislada
            $datosValidados['google_event_id'] = $idOCodigoGoogle;
            $eventoLocal = CalendarEvent::create($datosValidados);
            
            $this->sincronizarEventoAisladoGoogle($idOCodigoGoogle, $eventoLocal);

            return $eventoLocal;
        }
    }

    public function eliminarEvento(string $idOCodigoGoogle, string $modoActualizacion, ?string $idRecurrenteGoogle, int $userId): void
    {
        if (is_numeric($idOCodigoGoogle)) {
            $eventoLocal = CalendarEvent::findOrFail($idOCodigoGoogle);
            if ($eventoLocal->user_id !== $userId) {
                throw new Exception("No tienes permiso para eliminar este evento.", 403);
            }

            if ($eventoLocal->google_event_id) {
                try {
                    $targetId = ($modoActualizacion === 'series' && $idRecurrenteGoogle) ? $idRecurrenteGoogle : $eventoLocal->google_event_id;
                    $this->googleApiService->deleteEvent($targetId, ['sendUpdates' => 'none']);
                } catch (\Google\Service\Exception $e) {
                    if ($e->getCode() != 404 && $e->getCode() != 410) {
                        Log::error("Error borrando en Google Calendar: " . $e->getMessage());
                        throw new Exception("Falló la eliminación en Google Calendar: " . $e->getMessage());
                    }
                } catch (Exception $e) {
                    Log::error("Error general borrando en Google Calendar: " . $e->getMessage());
                    throw new Exception("Falló la eliminación en Google Calendar: " . $e->getMessage());
                }
            }

            if ($modoActualizacion === 'series' && $idRecurrenteGoogle) {
                CalendarEvent::where('google_event_id', 'like', $idRecurrenteGoogle . '_%')->delete();
            } else {
                $eventoLocal->delete();
            }
        } else {
            // Es un ID de Google
            try {
                $targetId = ($modoActualizacion === 'series' && $idRecurrenteGoogle) ? $idRecurrenteGoogle : $idOCodigoGoogle;
                $this->googleApiService->deleteEvent($targetId, ['sendUpdates' => 'none']);
            } catch (\Google\Service\Exception $e) {
                if ($e->getCode() != 404 && $e->getCode() != 410) {
                    Log::error("Error borrando en Google Calendar (externo): " . $e->getMessage());
                    throw new Exception("Falló la eliminación externa en Google Calendar: " . $e->getMessage());
                }
            } catch (Exception $e) {
                Log::error("Error general borrando en Google Calendar (externo): " . $e->getMessage());
                throw new Exception("Falló la eliminación externa en Google Calendar: " . $e->getMessage());
            }

            // También borrar cualquier rastro local que haya quedado atascado con este ID de Google
            if ($modoActualizacion === 'series' && $idRecurrenteGoogle) {
                CalendarEvent::where('google_event_id', 'like', $idRecurrenteGoogle . '%')->where('user_id', $userId)->delete();
            } else {
                CalendarEvent::where('google_event_id', $idOCodigoGoogle)->where('user_id', $userId)->delete();
            }
        }
    }

    private function sincronizarSerieGoogle(string $idRecurrenteGoogle, CalendarEvent $eventoLocal): void
    {
        try {
            $gEvent = $this->googleApiService->getEvent($idRecurrenteGoogle);
            $gEvent->setSummary($eventoLocal->name);
            $gEvent->setDescription($eventoLocal->description ?? '');
            
            $gEvent->setReminders($this->construirRecordatoriosVaciosGoogle());

            $this->googleApiService->updateEvent($idRecurrenteGoogle, $gEvent, ['sendUpdates' => 'none']);
        } catch (Exception $e) {
            Log::error("Error actualizando Serie en Google Calendar: " . $e->getMessage());
            throw new Exception("No se pudo actualizar la serie en Google Calendar: " . $e->getMessage());
        }
    }

    private function sincronizarEventoAisladoGoogle(string $idEventoGoogle, CalendarEvent $eventoLocal): void
    {
        try {
            $gEvent = $this->googleApiService->getEvent($idEventoGoogle);
            $gEvent->setSummary($eventoLocal->name);
            $gEvent->setDescription($eventoLocal->description ?? '');
            
            $start = new GoogleEventDateTime();
            $end = new GoogleEventDateTime();
            
            if ($eventoLocal->is_all_day) {
                $start->setDate(Carbon::parse($eventoLocal->start_date)->format('Y-m-d'));
                $end->setDate(Carbon::parse($eventoLocal->end_date)->addDay()->format('Y-m-d'));
            } else {
                $start->setDateTime(Carbon::parse($eventoLocal->start_date)->format('c'));
                $start->setTimeZone(config('app.timezone'));
                $end->setDateTime(Carbon::parse($eventoLocal->end_date)->format('c'));
                $end->setTimeZone(config('app.timezone'));
            }
            
            $gEvent->setStart($start);
            $gEvent->setEnd($end);

            $gEvent->setReminders($this->construirRecordatoriosVaciosGoogle());

            $this->googleApiService->updateEvent($idEventoGoogle, $gEvent, ['sendUpdates' => 'none']);
        } catch (Exception $e) {
            Log::error("Error actualizando Evento en Google Calendar: " . $e->getMessage());
            throw new Exception("No se pudo actualizar el evento en Google Calendar: " . $e->getMessage());
        }
    }

    private function formatearRecordatorios(array $recordatoriosRaw): array
    {
        $lista = [];
        foreach ($recordatoriosRaw as $rem) {
            $lista[] = [
                'minutes' => (int) $rem['minutes'],
                'notified' => false
            ];
        }
        return $lista;
    }

    private function preservarRecordatoriosViejos(array $nuevosRecordatorios, mixed $viejosRecordatorios, bool $fechaInicioCambio): array
    {
        $listaFinal = [];
        $viejos = is_array($viejosRecordatorios) ? $viejosRecordatorios : [];

        foreach ($nuevosRecordatorios as $rem) {
            $minutos = (int) $rem['minutes'];
            $fueNotificado = false;

            if (!$fechaInicioCambio) {
                foreach ($viejos as $viejo) {
                    if ($viejo['minutes'] == $minutos && isset($viejo['notified'])) {
                        $fueNotificado = $viejo['notified'];
                    }
                }
            }

            $listaFinal[] = [
                'minutes' => $minutos,
                'notified' => $fueNotificado
            ];
        }

        return $listaFinal;
    }

    private function construirRecordatoriosVaciosGoogle(): EventReminders
    {
        $parametros = new EventReminders();
        $parametros->setUseDefault(false);
        $parametros->setOverrides([]);
        return $parametros;
    }
}
