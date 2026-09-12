const CACHE_NAME = 'monarch-shell-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Network-first passthrough for now; add asset caching here when the
    // app needs to work offline.
});

// Push notifications are not implemented yet. When they are, register
// them here:
//
// self.addEventListener('push', (event) => { ... });
// self.addEventListener('notificationclick', (event) => { ... });
