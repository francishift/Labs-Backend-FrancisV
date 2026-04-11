# Arquitectura: Notificaciones Push (PWA)

## 1. Stack Tecnológico

- **Backend (Laravel):** Paquete `laravel-notification-channels/webpush`.
- **Frontend (Vue/Inertia):** Objeto nativo WebKit `ServiceWorkerRegistration.showNotification()`.
- **Cifrado (VAPID):** Implementación de claves estándar para identificación de servidor (VAPID). Registradas en `.env` mediante `VAPID_PUBLIC_KEY` y `VAPID_PRIVATE_KEY` e interactuando contra los servidores APNs (Apple) y FCM (Google).

## 2. Inyección de Datos (Backend API)

### 2.1 Modelo y Suscripción
- Interfaz `HasPushSubscriptions` vinculada al modelo `User`.
- El endpoint `/admin/push-subscriptions` recepciona y encripta el token emitido por el Service Worker del cliente en la tabla `push_subscriptions`.

### 2.2 Motor de Envío (CRON)
- Hook en `routes/console.php`. La directiva `php artisan notas:send-reminders` itera recurrentemente cada minuto (`* * * * *`).
- La iteración descarta valores `-1` (Sin notificación).
- Formula la aritmética de despachado: `Timestamp de Lanzamiento = (Fecha + Hora) - Rango_de_Aviso_Minutos`.
- Al rebasarse el umbral de Timestamp, se emite la clase Notification nativa de Laravel y el flag `notificado` booleano pasa a verdadero en DB.

## 3. Resolución de Enrutamiento PWA (iOS WebKit Bug)

La congelación de hilos del SO Apple (iOS) impide la ejecución del comando estándar API `clients.openWindow(url)` sobre PWAs colapsadas en background al accionar el PopUp. Para solventarlo, se implementa una pasarela de persistencia delegada al evento asíncrono y la Caché del nav:

### 3.1 Proceso Asíncrono Híbrido

**Fase 1: Intercepción SW (`public/sw.js`)**
1. Hook pasivo al listener `notificationclick`.
2. Supresión forzada de instintos WebKit mediante `event.preventDefault()`.
3. Volcado de la URL (Target Payload) al contenedor `caches.open('pwa-routing')`.
4. Extracción de la ventana dormida (`client.focus()`) o invocación raíz (`clients.openWindow('/')`) en caso de SW huérfano.

**Fase 2: Hidratación SPA (`AuthenticatedLayout.vue`)**
1. Instanciación del listener sobre el evento del DOM `visibilitychange`.
2. Al recibir el SO el flag de maximizado (`visible`), se establece un Delay en el event loop primario de `150ms`.
3. Extracción de la URL desde la caché y purga transaccional.
4. Despliegue de un redireccionamiento reactivo (`router.visit()`) inyectando el payload.
