# Notificaciones Push y Recordatorios (PWA)

Este documento detalla la arquitectura y el funcionamiento del sistema de notificaciones Push implementado en Labs Backend, específicamente diseñado para el módulo de Notas y Recordatorios, prestando especial atención a la compatibilidad con iOS (PWA).

## 1. Arquitectura General

El sistema se basa en el estándar WebPush, utilizando:
- **Backend (Laravel):** Paquete `laravel-notification-channels/webpush` para gestionar las suscripciones y encriptar los payloads enviados a los servidores de notificaciones de los navegadores (Google FCM, Mozilla, Apple Push, etc).
- **Frontend (Vue/Inertia):** La API nativa del navegador `ServiceWorkerRegistration.showNotification()` para recibir y mostrar la alerta al usuario.

## 2. Funcionamiento Backend

### 2.1 Modelo y Rutas
- El modelo `User` incluye el trait `HasPushSubscriptions`.
- Las suscripciones se generan en el frontend y se envían a `/admin/push-subscriptions` (gestionado por `PushSubscriptionController@store`).
- Las notas se guardan con una `fecha`, `hora`, y una opción configurable de `notificacion_minutos_antes` (-1, 0, 1, 5, 15, 60, 1440). Si es `-1` significa "Sin notificación".

### 2.2 Tarea Programada (CRON)
El comando `php artisan notas:send-reminders` (`app/Console/Commands/SendNotaRecordatorios.php`) se ejecuta cada minuto mediante `routes/console.php`.
1. Obtiene todas las notas pendientes (`notificado = false`) optimizando la consulta con Eager Loading (`Nota::with('user')`) para evitar problemas N+1.
2. Calcula la fecha exacta del aviso: `(fecha + hora) - notificacion_minutos_antes`.
3. Si la nota tiene como opción `-1` (Sin notificación), la marca como `notificado = true` y la ignora.
4. Si el tiempo actual ha superado el tiempo de aviso calculado, despacha la notificación `NotaRecordatorio` al usuario dueño de la nota y actualiza el flag `notificado`.

## 3. Frontend y Service Worker (La solución iOS PWA)

Hacer funcionar el enrutamiento al hacer clic en una notificación Push dentro de una PWA instalada en iOS presenta un desafío técnico conocido en WebKit. 

Cuando la app está cerrada completamente, `clients.openWindow(url)` funciona bien. Sin embargo, **cuando la app está en segundo plano (minimizada), iOS congela el hilo de ejecución de Javascript**. Al tocar la notificación e invocar `client.focus()`, la app se maximiza visualmente al instante, pero el framework (Vue/Inertia) tarda varios milisegundos en reaccionar. Las órdenes de navegación convencionales en ese lapso de tiempo son ignoradas por el sistema operativo, manteniendo al usuario en la pantalla en la que estaba originalmente.

### 3.1 El Protocolo V6 (PostMessage Asíncrono de Recuperación)
Para sortear la restricción de iOS, se ha implementado la siguiente arquitectura de dos partes:

**Parte A: `public/sw.js` (El Service Worker)**
1. Captura el evento `notificationclick` y **ejecuta compulsivamente `event.preventDefault()`**. Esto es crítico para detener la intención por defecto inactiva de Safari.
2. Identifica si hay alguna instancia abierta de la aplicación (`clients.matchAll()`).
3. Si existe, la trae al frente con `client.focus()`. Inmediatamente después, sabiendo que la ejecución del cliente está temporalmente petrificada por el SO, **inicia un bucle `setInterval` que dispara un `postMessage` con la URL objetivo destino cada 250ms durante 2.5 segundos** apuntando al cliente recién despertado.

**Parte B: `AuthenticatedLayout.vue` (El Cliente)**
1. El archivo principal que envuelve a toda la aplicación incluye un Auto-Listener global `navigator.serviceWorker.addEventListener('message')`.
2. En la fracción de segundo en que el hilo de ejecución JS se "descongela" al volver al primer plano, caza uno de los mensajes emitidos por el Service Worker.
3. Extrae la URL objetivo (ej. `/admin/notas/123/edit`) y evalúa si es distinta a la ruta actual. Si lo es, ejecuta incondicionalmente un `router.visit()` forzando a la Single Page Application a renderizar el nuevo estado sin recargar la página. Puesto que el layout no se desmonta, esta escucha permanece viva en todas las vistas de la aplicación.

### 3.2 Registro Obligatorio del Service Worker
El front incluye el comando `await navigator.serviceWorker.register('/sw.js');`  dentro del componente responsable de suscribir (ej. `PushToggleButton.vue`) para asegurar que todo nuevo dispositivo que inicie sesión y solicite permisos pase obligatoriamente por el proceso de bajada e instalación técnica del demonio, previniendo cuelgues de estado en nuevos iPhones.
