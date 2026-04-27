# Módulo de Calendario y Sincronización Google Calendar

El antiguo módulo de "Notas" ha sido reemplazado por un sólido sistema de **Calendario Visual** impulsado por `FullCalendar para Vue3` e integrado en el backend a través del paquete `spatie/laravel-google-calendar`.

## 1. Arquitectura de UI (Frontend)

Se utiliza `@fullcalendar/vue3` para disponer de una vista rica del tipo "Agenda".
- Vista por defecto: Mes (DayGridMonth).
- Botonera superior que permite alternar a vista Semana y vista Día.
- Formularios interactivos en modal `DialogModal` para creación, edición y visualización de la información completa del evento arrastrando fechas (drag and drop y resize events habilitado nativamente e interceptando la API local).

## 2. Arquitectura de Sincronización (Backend)

*   **Modelo Híbrido (`CalendarEvent`)**: Los eventos creados en el portal persisten localmente e inyectan una cabecera nativa u ocurrencia `RRULE` hacia los servidores de Google API. 
*   **Aislamiento de la Muestra**: Para la visualización frontend se cargan los eventos base locales y se cruzan con la vista estricta que devuelve Google Calendar de los futuros próximos, deduplicando los bloques maestros locales que coinciden con las repeticiones autogeneradas para mantener la interfaz esmeralda sin duplicidades y delegar el trazado puramente en Google API.
*   **Gestor API (`Spatie\GoogleCalendar` modificado)**: Todas las acciones (creación / edición de serie / eliminación) originadas desde el sistema local replican instantáneamente sus parámetros en el Google Calendar general usando parámetros especiales de silenciamiento.

## 3. Validaciones Inteligentes de UX
El sistema cuenta con una tricapa de aserción preventiva para las fechas, asegurando que bloqueos cronológicos (donde la fecha de fin es anterior a la fecha de inicio o nula) jamás colisionen con las APIS strict de Google Calendar:
- **Blindaje UI (Layer 1):** El Selector Nativo de HTML restringe los días opacos (mediante el atributo `min`) impidiendo a nivel visual el retroceso indebido en la línea del tiempo.
- **Asistencia Frontend (Layer 2):** Si el operador deja el cajón `end_date` vacío por la prisa, la interfaz Vue interpela silenciosamente y clona la franja horario del inicio a la de fin y remite un paquete válido.
- **Dique Backend (Layer 3):** Por último las request de Laravel inspeccionan e inyectan el reverso condicional antes de disparar las Reglas Nullables para erradicar cualquier tipo de exploit de saltos API.

## 4. Custodia Privada de Notificaciones

Con el fin de evitar "ruido cruzado" o correos repetitivos originados por Google Calendar (`spam`), toda la plataforma impone un estricto silenciamiento (`setUseDefault(false)` junto a `Overrides` en blanco) a cualquier alarma enviada.

La Base de Datos MySQL se erige como la absoluta **Fuente de la Verdad** de las notificaciones:
*   Un `Cron Job` local (`SyncUpcomingCalendarEvents`) audita quincenal o mensualmente la línea temporal de GCal.
*   Si una instancia detectada en Google pertenece a una Serie Raíz de la base de datos local que tenía programada información de alertas o recordatorios, la herramienta hereda y clona esa alarma específicamente para el día venidero en la base de datos interna.
*   De esta manera el minuto a minuto interno dispara los Avisos PWA y Correos sin ensuciar los repositorios Cloud nativos de terceros y respetando las Ventanas de Intervención (2 horas preventivas) en caso de edición en diferido.

## 4. Autenticación / Credenciales (Spatie)

Para mantener desacoplada la interfaz local de la dependencia directa en la cuenta (sin requerir autorización OAuth visual), este método recomienda altamente el uso de **Credenciales por cuenta de servicio (Service Account)**.

1.  En *Google Cloud Console* acceder a "Service Accounts".
2.  Generar una cuenta de tipo Service Account, exportar la clave en formato `JSON` y renombrarla o ubicarla en una ruta accesible.
3.  En `.env` se debe declarar `GOOGLE_CALENDAR_AUTH_PROFILE=service_account` y apuntar el directorio de JSON.
4.  Tomar la dirección de email artificial de la _Service Account_ y añadirla al Calendario de Google deseado, con permisos para **Hacer cambios en eventos**. La ID general del calendario se pasará a la variable de entorno correspondiente, típicamente `GOOGLE_CALENDAR_ID`.
