// VERSION: 6.0 (PostMessage Async Recovery Protocol for iOS PWA)

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
        icon: '/images/logo.png', // O logo de la app
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
            pushData.url = payload.action; // Fallback legacy
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

    const url = (event.notification.data && event.notification.data.url) ? event.notification.data.url : '/admin/notas';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            if (clientList.length > 0) {
                const client = clientList[0];
                return client.focus().then((focusedClient) => {
                    const target = focusedClient || client;

                    // iOS freeze workaround: The Javascript execution thread in the waking PWA
                    // is suspended for several milliseconds after focusing.
                    // We dispatch the navigation payload several times to ensure the JS thread catches it.
                    let pings = 0;
                    const interval = setInterval(() => {
                        target.postMessage({
                            type: 'PWA_ROUTING',
                            url: url
                        });
                        pings++;
                        if (pings > 10) { // Keep pinging for 2.5 seconds
                            clearInterval(interval);
                        }
                    }, 250);
                });
            }

            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
