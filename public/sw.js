const CACHE_NAME = 'agrosys-cache-v2';
const urlsToCache = [
  '/manifest.webmanifest',
  '/agrosyslogo-word.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => Promise.all(
      cacheNames.filter(name => name !== CACHE_NAME).map(name => caches.delete(name))
    ))
  );
  clients.claim();
});

self.addEventListener('fetch', event => {
  // Always hit the network for navigation requests to avoid stale pages with
  // expired CSRF tokens which can cause 419 responses on login.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
    return;
  }

  // Skip caching for non-GET requests (e.g. form submissions).
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
