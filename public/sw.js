self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (_) {
        payload = {};
    }

    const title = (payload.title || 'Meetra').toString();
    const options = {
        body: (payload.body || '').toString().slice(0, 180),
        tag: (payload.tag || 'meetra-notify').toString(),
        data: {
            url: payload.url || '/',
        },
        icon: '/brand/favicon-192.png',
        badge: '/brand/favicon-192.png',
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification?.data?.url || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    try {
                        const current = new URL(client.url);
                        const target = new URL(targetUrl, self.location.origin);
                        if (current.origin === target.origin && current.pathname === target.pathname) {
                            return client.focus();
                        }
                    } catch (_) {
                        return client.focus();
                    }
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
            return null;
        })
    );
});
