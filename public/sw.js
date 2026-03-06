// VERSION: 8.0 (Blind openWindow Strategy for iOS PWA)

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
        url: '/admin/notas'
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

    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/admin/notas';

    // Estrategia V8: Open Window Ciego
    event.waitUntil(
        clients.openWindow(targetUrl)
    );
});
