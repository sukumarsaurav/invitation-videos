// Invitation Videos - Service Worker
// Minimal implementation for PWA support

self.addEventListener('install', (event) => {
    // Skip waiting to activate immediately
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    // Take control of all pages immediately
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Pass through all requests to network (no caching strategy)
    // This can be enhanced later for offline support
    event.respondWith(fetch(event.request));
});
