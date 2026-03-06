# Notificaciones Push y Recordatorios (PWA)

Este documento detalla la arquitectura y el funcionamiento del sistema de notificaciones Push implementado en Labs Backend, específicamente diseñado para el módulo de Notas y Recordatorios, prestando especial atención a la compatibilidad con iOS (PWA).

## 1. Arquitectura General

El sistema se basa en el estándar WebPush, utilizando:
- **Backend (Laravel):** Paquete `laravel-notification-channels/webpush` para gestionar las suscripciones y encriptar los payloads enviados a los servidores de notificaciones de los navegadores (Google FCM, Mozilla, Apple Push, etc).
- **Frontend (Vue/Inertia):** La API nativa del navegador `ServiceWorkerRegistration.showNotification()` para recibir y mostrar la alerta al usuario.

### 1.1 Dependencias Externas y VAPID Keys
- **¿Qué son las VAPID Keys?** VAPID (Voluntary Application Server Identification) es un estándar de encriptación y autenticación. El par de claves (`VAPID_PUBLIC_KEY` y `VAPID_PRIVATE_KEY` en tu archivo `.env`) es básicamente tu "Carnet de Identidad" como servidor que envía notificaciones. 
- **¿Para qué sirven?** Cuando Labs Backend quiere mandar un aviso a un iPhone, en realidad no se lo manda directamente al iPhone. Laravel se lo envía a los grandísimos servidores globales de Apple (APNs), o a los de Google (FCM) si es Android, quienes a su vez retransmiten el mensaje a los teléfonos de tus clientes. Las claves VAPID permiten a Apple/Google/Mozilla verificar matemáticamente que ese mensaje Push proviene legítimamente de tu servidor (`TU_DOMINIO`) y no de un hacker malicioso intentando spamear a tus usuarios.
- **Dependencias:** Tu sistema ahora depende de estos servidores puente globales. El estándar WebPush es gratuito e integrado en los navegadores, no pagas por él, pero debes saber que si los servidores de notificaciones de Apple o Google fallan masivamente a nivel mundial, tus notificaciones Push también se retrasarían, ya que la arquitectura nativa del navegador depende de sus nubes para despertar a los terminales.

## 2. Funcionamiento Backend

### 2.1 Modelo y Rutas
- El modelo `User` incluye el trait `HasPushSubscriptions`.
- Las suscripciones se generan en el frontend y se envían a `/admin/push-subscriptions` (gestionado por `PushSubscriptionController@store`).
- Las notas se guardan con una `fecha`, `hora`, y una opción configurable de `notificacion_minutos_antes` (-1, 0, 1, 5, 15, 60, 1440). 
- Si un usuario programa una notificación distinta a `-1` (Sin notificación), el `StoreNotaRequest` en el backend rechaza explícitamente cualquier combinación de fecha/hora que pertenezca al pasado.
- Por defecto al crear una nota nueva, el sistema preselecciona la opción "A la hora indicada" (`0`), priorizando el uso de las notificaciones como herramienta estándar de recordatorio.

### 2.2 Tarea Programada (CRON)
El comando `php artisan notas:send-reminders` (`app/Console/Commands/SendNotaRecordatorios.php`) se ejecuta cada minuto mediante `routes/console.php`.
1. Obtiene todas las notas pendientes (`notificado = false`) optimizando la consulta con Eager Loading (`Nota::with('user')`) para evitar problemas N+1.
2. Calcula la fecha exacta del aviso: `(fecha + hora) - notificacion_minutos_antes`.
3. Si la nota tiene como opción `-1` (Sin notificación), la marca como `notificado = true` y la ignora.
4. Si el tiempo actual ha superado el tiempo de aviso calculado, despacha la notificación `NotaRecordatorio` al usuario dueño de la nota y actualiza el flag `notificado`.

## 3. Frontend y Service Worker (La solución iOS PWA)

Hacer funcionar el enrutamiento al hacer clic en una notificación Push dentro de una PWA instalada en iOS presenta un desafío técnico conocido en WebKit. 

Cuando la app está cerrada completamente, `clients.openWindow(url)` funciona bien. Sin embargo, **cuando la app está en segundo plano (minimizada), iOS congela el hilo de ejecución de Javascript**. Al tocar la notificación e invocar `client.focus()`, la app se maximiza visualmente al instante, pero el framework (Vue/Inertia) tarda varios milisegundos en reaccionar. Las órdenes de navegación convencionales en ese lapso de tiempo son ignoradas por el sistema operativo, manteniendo al usuario en la pantalla en la que estaba originalmente.

### 3.1 El Protocolo V9 (Cache Storage + Native Visibility Event)
Para sortear la restricción de iOS, se ha implementado la siguiente arquitectura de dos partes basadas puramente en eventos nativos y persistencia pasiva:

**Parte A: `public/sw.js` (El Service Worker)**
1. Captura el evento `notificationclick` y **ejecuta compulsivamente `event.preventDefault()`**. Esto es crítico para detener la intención por defecto inactiva de Safari.
2. Escribe de forma pasiva la URL de destino (`/admin/notas/123/edit`) en un contenedor del `Cache API` del navegador nativo (`caches.open('pwa-routing')`).
3. Identifica si hay alguna instancia abierta de la aplicación (`clients.matchAll()`).
4. Si existe, la trae al frente con `client.focus()`. Si no existe, abre la app desde la raíz (`clients.openWindow('/')`). Como Vue se encarga del enrutamiento real desde la caché, evitamos el bug de `clients.openWindow(url_especifica)` del iPhone.

**Parte B: `AuthenticatedLayout.vue` (El Cliente)**
1. El archivo principal que envuelve a toda la aplicación inicializa una función asíncrona que consume y purga la ruta almacenada en el caché (`checkPendingNavigation()`).
2. Se inscribe un *listener* global al evento nativo del DOM `visibilitychange`. 
3. Cuando el SO maximiza la aplicación o la saca de su estado dormido, el estado salta a `visible`. Vue dispara un temporizador de 150 milisegundos (`setTimeout`) para darle a WebKit un margen de aire para restaurar la memoria por completo, y luego invoca al lector de Caché.
4. Si extrae una URL, ejecuta un `router.visit()` forzando a la Single Page Application a renderizar el nuevo estado.

### 3.2 Registro Obligatorio del Service Worker
El front incluye el comando `await navigator.serviceWorker.register('/sw.js');`  dentro del componente responsable de suscribir (ej. `PushToggleButton.vue`) para asegurar que todo nuevo dispositivo que inicie sesión y solicite permisos pase obligatoriamente por el proceso de bajada e instalación técnica del demonio, previniendo cuelgues de estado en nuevos iPhones.
