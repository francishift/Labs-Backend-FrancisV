// VERSION: 9.0 (Cache Storage + Native Visibility Event Strategy)

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('push', function (event) {
    if (!(self.Notification && self.Notification.permission === 'granted')) {
        return;
    }

    let pushData = {
        title: 'Algo nuevo ha sucedido',
        body: 'Haga clic para ver más',
        icon: '/logo-icono.png',
        url: '/admin/calendar'
    };

    if (event.data) {
        const payload = event.data.json();
        pushData.title = payload.title || pushData.title;
        pushData.body = payload.body || pushData.body;
        pushData.icon = payload.icon || pushData.icon;

        if (payload.data && payload.data.url) {
            pushData.url = payload.data.url;
        } else if (payload.action && typeof payload.action === 'string') {
            pushData.url = payload.action;
        }
    }

    const options = {
        body: pushData.body,
        icon: pushData.icon,
        data: { url: pushData.url },
        vibrate: pushData.vibrate || [100, 50, 100]
    };

    event.waitUntil(
        self.registration.showNotification(pushData.title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.preventDefault();
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/admin/calendar';

    // Estrategia V9: Cache Storage + Visibility
    // 1. Escribimos la intención de navegación en el Cache API nativo.
    // 2. Traemos la PWA al frente.
    // 3. El cliente (Vue) interceptará el evento nativo de JS `visibilitychange`
    //    y leerá este caché para auto-enrutarse.
    event.waitUntil(
        caches.open('pwa-routing').then(cache => {
            return cache.put('/pending-route', new Response(targetUrl));
        }).then(() => {
            return clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
                if (clientList.length > 0) {
                    // Si ya hay una ventana, simplemente la enfocamos.
                    // Será responsabilidad de la SPA leer la caché al despertar.
                    return clientList[0].focus();
                }

                // Si no hay ventana, abrimos la PWA en la raíz. 
                // Al montar, la PWA leerá la caché y navegará.
                if (clients.openWindow) {
                    return clients.openWindow('/');
                }
            });
        })
    );
});
