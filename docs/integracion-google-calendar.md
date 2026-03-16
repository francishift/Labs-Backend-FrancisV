# Integración de Google Calendar en el Gestor de Notas

El módulo de Notas permite la sincronización automática de registros en tiempo real con Google Calendar del usuario logueado en la aplicación, empleando el mismo proyecto de Google habilitado para Drive.

## 1. Arquitectura de Sincronización

*   **GoogleCalendarService (`app/Services/GoogleCalendarService.php`)**: Es el servicio inyectable (inyección de dependencias) puente que envuelve el cliente SDK de `google/apiclient`. Contiene los métodos puros de `createEvent`, `updateEvent`, y `deleteEvent`.
*   **NotaObserver (`app/Observers/NotaObserver.php`)**: Escucha silenciosamente los eventos Eloquent `created`, `updated` y `deleted`. Al detectar un cambio evalúa el flag booleano `sync_calendar`. Si es `true`, envía el comando al servicio.
*   **Gestión de Estados**: Al crear con éxito un evento en Calendar, la API de Google devuelve un ID alfanumérico. Este ID se guarda en el campo `google_event_id` del modelo `Nota` en la base de datos local usando `saveQuietly()` (para que Eloquent no dispare de nuevo el observer al hacer save). Con este ID, futuras ediciones en la base de datos saben exactamente qué evento de Google modificar.

## 2. Prevención de Ruido de Notificación (Override)

Por defecto, Google Calendar dispara correos electrónicos o notificaciones push 30 o 10 minutos antes a todos los dueños del calendario. Se forzó la política `UseDefault: false` en los *Reminders* del objeto API de Google para asegurar que el evento exista en el planificador visual diario, pero el motor de avisos nativos en tiempo real recaiga 100% sobre las [Notificaciones Push de la PWA](notificaciones-push-pwa.md) implementadas en este CRM, eliminando los avisos dobles (el del móvil de GCalendar + El del móvil de Labs).

## 3. Identificación Visual de la Fuente

Para entender qué eventos de la agenda proceden del CRM Labs y separar los eventos automáticos de eventos creados a mano en la aplicación Google Calendar, el servicio antepone de manera automatizada el emoji 📌 al título `$event->setSummary("📌 " . $nota->comentario)`.

## 4. Obtención Multi-Scope del OAuth 2.0

La autenticación no depende de claves de servidor a servidor, utiliza una autorización `offline` tipo *Authorization Code Flow* delegando el consentimiento al usuario físico (dueño del Calendar).
*   Variables `.env`: Utiliza el `GOOGLE_DRIVE_CLIENT_ID`, `GOOGLE_DRIVE_CLIENT_SECRET`.
*   Scope combinado: Requiere `\Google\Service\Drive::DRIVE` + `\Google\Service\Calendar::CALENDAR_EVENTS`.
*   La variable final es un `GOOGLE_DRIVE_REFRESH_TOKEN` que aloja en un solo string alfanumérico largo las potencias de ambos módulos combinados para acceso perpetuo.
