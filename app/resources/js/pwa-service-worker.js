self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let message = { title: 'Numerimondes', body: '' };
    if (event.data) {
        try {
            message = { ...message, ...event.data.json() };
        } catch {
            message.body = event.data.text();
        }
    }

    event.waitUntil(self.registration.showNotification(String(message.title || 'Numerimondes'), {
        body: String(message.body || ''),
        icon: '/brand/pwa-icon-192.png',
        data: { url: typeof message.url === 'string' ? message.url : '/' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/', self.location.origin);
    if (target.origin !== self.location.origin) target.href = '/';

    event.waitUntil(self.clients.openWindow(target.href));
});
