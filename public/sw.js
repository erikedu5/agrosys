const CACHE_NAME = 'agrosys-shell-v10';
const IMAGE_CACHE_NAME = 'agrosys-images-v1';
const urlsToCache = [
  '/manifest.webmanifest',
  '/agrosyslogo-word.png',
  '/logo.png'
];

self.addEventListener('install', event => {
  event.waitUntil((async () => {
    const cache = await caches.open(CACHE_NAME);
    const urls = new Set(urlsToCache);

    // The cached HTML references hashed Vite chunks. Cache the complete build
    // graph up front so a newly activated worker never leaves an HTML shell
    // whose JS/CSS has already disappeared with the previous cache.
    try {
      const manifestResponse = await fetch('/build/manifest.json', { cache: 'no-store' });
      if (manifestResponse.ok) {
        const manifest = await manifestResponse.json();
        Object.values(manifest).forEach(entry => {
          if (entry.file) urls.add(`/build/${entry.file}`);
          (entry.css || []).forEach(file => urls.add(`/build/${file}`));
          (entry.assets || []).forEach(file => urls.add(`/build/${file}`));
        });
        urls.add('/build/manifest.json');
      }
    } catch {
      // Static shell files can still install if a build is temporarily absent.
    }

    await Promise.allSettled([...urls].map(url => cache.add(url)));
  })());
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => Promise.all(
      cacheNames.filter(name => ![CACHE_NAME, IMAGE_CACHE_NAME].includes(name)).map(name => caches.delete(name))
    ))
  );
  clients.claim();
});

self.addEventListener('fetch', event => {
  const requestUrl = new URL(event.request.url);

  if (requestUrl.origin !== self.location.origin) return;

  // API reads are explicit network-only operations. Business data is persisted
  // by the application in IndexedDB, never in the HTTP cache.
  if (requestUrl.pathname.startsWith('/api/v1/offline/')) return;

  // Navigation is network-first; successful authenticated pages become the
  // fallback for that same URL. Mutations remain network-only below.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request).then(response => {
        if (response.ok) {
          const copy = response.clone();
          const documentKey = new Request(requestUrl.pathname, {
            method: 'GET',
            credentials: 'same-origin',
          });
          caches.open(CACHE_NAME).then(cache => cache.put(documentKey, copy));
        }
        return response;
      }).catch(async () => {
        const matchOptions = { ignoreSearch: true, ignoreVary: true };
        return (await caches.match(requestUrl.pathname, matchOptions))
          || (await caches.match('/dashboard', matchOptions))
          || caches.match('/venta', matchOptions);
      })
    );
    return;
  }

  // Skip caching for non-GET requests (e.g. form submissions).
  if (event.request.method !== 'GET') {
    return;
  }

  const isVersionedAsset = requestUrl.pathname.startsWith('/build/') || ['script', 'style', 'font'].includes(event.request.destination);
  if (isVersionedAsset) {
    event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request).then(response => {
      if (response.ok) caches.open(CACHE_NAME).then(cache => cache.put(event.request, response.clone()));
      return response;
    }).catch(() => new Response('', { status: 504, statusText: 'Offline asset unavailable' }))));
    return;
  }

  if (event.request.destination === 'image') {
    event.respondWith(caches.match(event.request).then(cached => {
      const refreshed = fetch(event.request).then(response => {
        if (response.ok) caches.open(IMAGE_CACHE_NAME).then(cache => cache.put(event.request, response.clone()));
        return response;
      }).catch(() => new Response('', { status: 504, statusText: 'Offline image unavailable' }));
      return cached || refreshed;
    }));
  }
});
