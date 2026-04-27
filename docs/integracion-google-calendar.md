# Módulo de Calendario y Sincronización Google Calendar

El antiguo módulo de "Notas" ha sido reemplazado por un sólido sistema de **Calendario Visual** impulsado por `FullCalendar para Vue3` e integrado en el backend a través del paquete `spatie/laravel-google-calendar`.

## 1. Arquitectura de UI (Frontend)

Se utiliza `@fullcalendar/vue3` para disponer de una vista rica del tipo "Agenda".
- Vista por defecto: Mes (DayGridMonth).
- Botonera superior que permite alternar a vista Semana y vista Día.
- Formularios interactivos en modal `DialogModal` para creación, edición y visualización de la información completa del evento arrastrando fechas (drag and drop y resize events habilitado nativamente e interceptando la API local).

## 2. Arquitectura de Sincronización (Backend)

*   **Modelo de Base de Datos (`CalendarEvent`)**: A diferencia de delegar puramente en la nube, todos los eventos persisten localmente asegurando un performance instantáneo y evitando consultas `N+1` hacia los servidores de Google.
*   **Gestor API (`Spatie\GoogleCalendar`)**: Todas las acciones (creación / edición / eliminación) originadas desde el sistema local, replican instantáneamente sus parámetros en el Google Calendar general.
*   **Almacenamiento de Enlace**: Una vez replicado un evento nuevo, Google devuelve un Hash ID que almacenaremos en `google_event_id` en nuestro registro local.

## 3. Notificaciones y Prevención de Ruido

Con el fin de evitar "ruido de notificaciones" y no saturar de correos mediante Google, en cada petición de *crear / actualizar* mandamos el parámetro `['sendUpdates' => 'none']` limitando la comunicación nativa de Google de estos eventos.
A su vez, en la base de datos local gestionamos un campo `notification_minutes_before` que, combinado con nuestro sistema de **Web Push**, permite que el sistema informe al usuario según sus tiempos solicitados sin interferencias.

## 4. Autenticación / Credenciales (Spatie)

Para mantener desacoplada la interfaz local de la dependencia directa en la cuenta (sin requerir autorización OAuth visual), este método recomienda altamente el uso de **Credenciales por cuenta de servicio (Service Account)**.

1.  En *Google Cloud Console* acceder a "Service Accounts".
2.  Generar una cuenta de tipo Service Account, exportar la clave en formato `JSON` y renombrarla o ubicarla en una ruta accesible.
3.  En `.env` se debe declarar `GOOGLE_CALENDAR_AUTH_PROFILE=service_account` y apuntar el directorio de JSON.
4.  Tomar la dirección de email artificial de la _Service Account_ y añadirla al Calendario de Google deseado, con permisos para **Hacer cambios en eventos**. La ID general del calendario se pasará a la variable de entorno correspondiente, típicamente `GOOGLE_CALENDAR_ID`.
