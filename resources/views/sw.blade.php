const CACHE_NAME = 'apple-dex-shell-{{ $version }}';

const APP_SHELL = [
@foreach ($assets as $asset)
    '{{ $asset }}',
@endforeach
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL))
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    const isAppShellAsset = event.request.method === 'GET'
        && url.origin === self.location.origin
        && APP_SHELL.includes(url.pathname);

    if (! isAppShellAsset) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
