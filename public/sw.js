// Minimal, intentionally conservative service worker. It exists only so the
// PWA installs and the app shell loads once when offline — it must never
// cache-first anything that could go stale in a way that matters for a real
// booking system (login state, availability, credit balances). All requests
// besides the bare app shell always go to the network.
const CACHE_NAME = 'angora-shell-v1';
const SHELL = ['/'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);
  const isShellNavigation = event.request.mode === 'navigate' && url.origin === self.location.origin;

  if (!isShellNavigation) return; // network as normal for everything else, including all API calls

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, response.clone()));
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});
