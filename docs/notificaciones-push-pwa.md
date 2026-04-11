# Notificaciones Push y Recordatorios (PWA)

Este documento detalla la arquitectura y el funcionamiento del sistema de notificaciones Push implementado en Labs Backend, específicamente diseñado para el módulo de Notas y Recordatorios, prestando especial atención a la compatibilidad con iOS (PWA).

## 1. Arquitectura General

El sistema se basa en el estándar WebPush, utilizando:
- **Backend (Laravel):** Paquete `laravel-notification-channels/webpush` para gestionar las suscripciones y encriptar los payloads enviados a los servidores de notificaciones de los navegadores (Google FCM, Mozilla, Apple Push, etc).
- **Frontend (Vue/Inertia):** La API nativa del navegador `ServiceWorkerRegistration.showNotification()` para recibir y mostrar la alerta al usuario.

### 1.1 Dependencias Externas y VAPID Keys
- **¿Qué son las VAPID Keys?** VAPID (Voluntary Application Server Identification) es un estándar de encriptación y autenticación. El par de claves (`VAPID_PUBLIC_KEY` y `VAPID_PRIVATE_KEY` en el archivo `.env`) es la identificación central como servidor que envía notificaciones. 
- **¿Para qué sirven?** Cuando el backend envía un aviso a un iPhone (o Android), la información se transmite primero a los servidores globales de Apple (APNs) o de Google (FCM), quienes a su vez retransmiten el mensaje a los dispositivos finales. Las claves VAPID permiten a Apple/Google/Mozilla verificar matemáticamente que ese mensaje Push proviene legítimamente de este servidor (`TU_DOMINIO`) previniendo intentos maliciosos.
- **Dependencias:** El sistema depende del funcionamiento de estos servidores puente globales. El estándar WebPush es gratuito e integrado en los navegadores, pero interrupciones masivas en los servicios de notificaciones de Apple o Google retrasarían las alertas Push.

## 2. Funcionamiento Backend

### 2.1 Modelo y Rutas
- El modelo `User` incluye el trait `HasPushSubscriptions`.
- Las suscripciones se generan en el frontend y se envían a `/admin/push-subscriptions` (gestionado por `PushSubscriptionController@store`).
- Las notas se guardan con una `fecha`, `hora`, y una opción configurable de `notificacion_minutos_antes` (-1, 0, 1, 5, 15, 60, 1440). 
- Si se programa una notificación distinta a `-1` (Sin notificación), el `StoreNotaRequest` rechaza explícitamente cualquier combinación de fecha/hora en el pasado.
- Por defecto, al crear una nota nueva, el sistema preselecciona la opción "A la hora indicada" (`0`).

### 2.2 Tarea Programada (CRON)
El comando `php artisan notas:send-reminders` (`app/Console/Commands/SendNotaRecordatorios.php`) se ejecuta cada minuto mediante `routes/console.php`.
1. Obtiene todas las notas pendientes (`notificado = false`) optimizando la consulta con Eager Loading (`Nota::with('user')`) para evitar problemas N+1.
2. Calcula la fecha exacta del aviso: `(fecha + hora) - notificacion_minutos_antes`.
3. Si la nota tiene como opción `-1` (Sin notificación), la marca como `notificado = true` y la ignora.
4. Si el tiempo actual supera el tiempo de aviso, se despacha la notificación `NotaRecordatorio` al usuario dueño de la nota y se actualiza el flag `notificado`.

## 3. Frontend y Service Worker (La solución iOS PWA)

Hacer funcionar el enrutamiento al hacer clic en una notificación Push dentro de una PWA instalada en iOS presenta un desafío técnico conocido en WebKit. 

Cuando la app está completamente cerrada, `clients.openWindow(url)` funciona bien. Sin embargo, **cuando la app está en segundo plano (minimizada), iOS congela el hilo de ejecución de Javascript**. Al procesar la notificación e invocar `client.focus()`, la app se maximiza visualmente, pero los frameworks de enrutamiento tardan varios milisegundos en reaccionar. Las órdenes de navegación convencionales en ese lapso de tiempo suelen ser ignoradas por el sistema operativo.

### 3.1 El Protocolo V9 (Cache Storage + Native Visibility Event)
Para sortear la restricción de iOS, se ha implementado una arquitectura basada en eventos nativos y persistencia pasiva:

**Parte A: `public/sw.js` (El Service Worker)**
1. Captura el evento `notificationclick` y ejecuta **`event.preventDefault()`** para detener el comportamiento inactivo por defecto de Safari.
2. Almacena la URL de destino (`/admin/notas/123/edit`) en un contenedor del API nativo de Caché (`caches.open('pwa-routing')`).
3. Identifica instancias abiertas de la aplicación (`clients.matchAll()`).
4. Si existen, envía la instrucción `client.focus()`. Si no, abre la app desde la raíz (`clients.openWindow('/')`). Como el cliente procesa la URL posteriormente leyendo la caché, este enfoque es estable en iOS.

**Parte B: `AuthenticatedLayout.vue` (El Cliente)**
1. Inicializa una tarea para consumir y limpiar la caché de enrutamiento (`checkPendingNavigation()`).
2. Se inscribe un *listener* global al evento nativo del DOM `visibilitychange`. 
3. Cuando el sistema operativo maximiza la aplicación o la saca de su estado suspendido, el estado cambia a `visible`. Vue dispara un temporizador corto (150ms) permitiendo a WebKit restaurar la memoria por completo, y procesa el caché.
4. Si encuentra una URL, ejecuta un `router.visit()` forzando a la SPA a renderizar la vista pertinente.

### 3.2 Registro Obligatorio del Service Worker
El cliente incluye `await navigator.serviceWorker.register('/sw.js');` dentro del componente de suscripción (ej. `PushToggleButton.vue`) garantizando que todo dispositivo apruebe el proceso y establezca el worker antes de procesar alertas web.
