# Ideas y Futuras Implementaciones

Este documento sirve como registro de ideas, peticiones y posibles mejoras para implementar en el futuro en el CRM Labs.

## 1. Integración de Notas con Google Calendar

**Descripción:**
Permitir que, al crear una nueva Nota en el sistema, el usuario tenga la opción de añadirla automáticamente como un evento en Google Calendar.

**Requisitos Previos (Ya cumplidos en su mayoría):**
- Ya existe una Service Account configurada en Google Cloud Console para interactuar con Google Drive y Google Sheets.
- Las credenciales de la Service Account ya están integradas en el flujo del backend.

**Pasos de Implementación Propuestos:**
1. **Google Cloud Console:**
   - Ir a "APIs y Servicios" en el proyecto GCP actual.
   - Habilitar la "Google Calendar API".
2. **Backend (Laravel - `NotaController@store` / `NotaController@update`):**
   - Utilizar el cliente de Google (`google/apiclient`) para instanciar el servicio de Calendar.
   - Crear un objeto `Google_Service_Calendar_Event` poblando los datos de la nota:
     - `summary`: Título de la nota o prefijo estándar (ej: "Nota Labs: [Comentario corto]").
     - `description`: Comentario completo de la nota.
     - `start`/`end`: Basado en los campos `fecha` y `hora` de la nota.
     - `reminders`: Configurados según el campo `notificacion_minutos_antes`.
   - Llamar a `$calendarService->events->insert('primary', $event)` tras guardar el modelo `Nota` en la base de datos local.
   - Guardar opcionalmente el `event_id` devuelto por Google en la tabla `notas` por si en el futuro se desea sincronizar borrados/actualizaciones.
3. **Frontend (Vue - `Admin/Notas/Index.vue` & `Edit.vue`):**
   - Añadir un control tipo Switch o Checkbox en el formulario de creación/edición de notas: **"¿Añadir evento a Google Calendar?"**.
   - Enviar este valor booleano (ej. `sync_calendar: true`) en el payload de la petición POST/PUT al backend.

**Notas Adicionales:**
La base técnica de conexión ya está superada gracias a trabajos anteriores con Sheets. La mayor complejidad radicará en mapear correctamente las fechas/horas considerando la zona horaria del usuario (ej. `Europe/Madrid`) para que los eventos cuadren perfectamente en el calendario.
