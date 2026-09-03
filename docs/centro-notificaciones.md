# Centro de Notificaciones Nativo

## Arquitectura

El sistema de notificaciones se ha migrado de una solución puramente Push (PWA) hacia un ecosistema híbrido **Database + WebPush**. Esto permite persistencia histórica robusta y una experiencia de usuario sin fricciones dentro la propia aplicación.

### Backend (Laravel)
- **Canal Database:** Las notificaciones (`App\Notifications\CalendarReminder`) implementan el método `via()` retornando tanto el array de base de datos como el canal `WebPushChannel::class`.
- **API Stateless:** El controlador `NotificationController` actúa de puente exponiendo los endpoints JSON de lectura y gestión rápida, además de las vistas inyectadas por Inertia de histórico general (`index`).
- **Tabla Pivot:** Toda notificación disparada se grita en la tabla estándar de Laravel `notifications`.

### Frontend (Vue 3 + Tailwind + Inertia)
- **Componente Core (`NotificationDropdown.vue`):** Desplegable estético en el navbar superior. 
    - Realiza un *polling persistente ligero* cada 60 segundos (`setInterval`) consumiendo la API JSON.
    - Soporta *Auto-Despliegue Responsivo*: Si una consulta en segundo plano detecta nuevas alertas, predespliega el panel de inmediato para captación nativa de la mirada.
    - **Cierre y Acuse de Recibo Unitario (Cruz):** Cada notificación incluye un botón de cierre (`XMarkIcon`) que permite marcarla como leída y descartarla sin obligar al usuario a abandonar la pantalla ni redirigir al calendario. Si el usuario desea ir al detalle, puede pulsar directamente sobre el cuerpo de la alerta.
    - **Acción Masiva Rápida:** Botón directo *"Marcar todas leídas"* integrado en la cabecera del panel.
- **Vista Histórico (`Admin/Notifications/Index.vue`):** Panel completo con paginación que incluye acciones masivas nativas como supresiones forzosas CRUD directo contra base de datos.
- **Responsividad Crítica:** El dropdown compensa los layouts reducidos mediante `fixed` central inyectando su Z-index por encima de la PWA pero sin mutilar la lectura del contenido circundante.

## Rutas y Endpoints

| Método   | Ruta                                            | Acción           | Función                                                       |
|----------|-------------------------------------------------|------------------|---------------------------------------------------------------|
| `GET`    | `/admin/notifications`                          | `index`          | Retorna la vista Inertia con todo el historial paginado.      |
| `GET`    | `/admin/api/notifications/unread`               | `fetchUnread`    | Polling JSON: devuelve conteo y los 5 perfiles más recientes. |
| `PATCH`  | `/admin/api/notifications/{id}/read`            | `markAsRead`     | Marca `read_at` (vía JSON en el Dropdown, o redirect en Index).|
| `POST`   | `/admin/notifications/mark-all-read`            | `markAllAsRead`  | Acción global del panel Histórico o cabecera del Dropdown.    |
| `DELETE` | `/admin/api/notifications/{id}`                 | `destroy`        | Destrucción definitiva del registro en BD (Inertia).          |
| `DELETE` | `/admin/notifications/destroy-all`              | `destroyAll`     | Supresión total e irreversible de todo el historial.          |

## Optimizaciones Implementadas
1. Inyección estática de iconos vectoriales que esquivan el colapso grid en flex-containers (`shrink-0`).
2. Persistencia dual sin colisión: Un recordatorio de Agenda salta tanto por API Push Nativa como en inyección a BD para revisión manual.
3. Descarte no bloqueante: Descartar o dar por notificada una alerta no interrumpe el flujo de trabajo del usuario.

*Última actualización: Septiembre de 2026*
