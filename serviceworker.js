const CACHE_VERSION = new URL(self.location.href).searchParams.get("v") || "v2";
const SCOPE_PATH = new URL(self.registration.scope).pathname
    .replace(/[^a-z0-9]/gi, "_")
    .replace(/^_+|_+$/g, "") || "root";
const CACHE_PREFIX = `pwa-${SCOPE_PATH}`;
const STATIC_CACHE = `${CACHE_PREFIX}-static-${CACHE_VERSION}`;
const IMAGE_CACHE = `${CACHE_PREFIX}-images-${CACHE_VERSION}`;
const toScopedUrl = (path) => new URL(path, self.registration.scope).toString();
const OFFLINE_URL = toScopedUrl("offline.html");

const PRECACHE_URLS = [
    OFFLINE_URL,
    toScopedUrl("public/img/pwa/icon-192x192.png"),
    toScopedUrl("public/img/pwa/icon-512x512.png"),
    toScopedUrl("public/css/app.css"),
    toScopedUrl("public/css/fonts/material-icons/style.css"),
    toScopedUrl("public/vendor/quicksand/css/quicksand.css"),
];

const PRECACHE_IMAGE_URLS = [
    toScopedUrl("public/img/empty_pages/transactions.png"),
];

self.addEventListener("install", (event) => {
    self.skipWaiting();

    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)),
            caches.open(IMAGE_CACHE).then((cache) => cache.addAll(PRECACHE_IMAGE_URLS)),
        ])
    );
});

self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName.startsWith(CACHE_PREFIX))
                    .filter((cacheName) => ![STATIC_CACHE, IMAGE_CACHE].includes(cacheName))
                    .map((cacheName) => caches.delete(cacheName))
            );
        }).then(() => self.clients.claim())
    );
});

function isNavigationRequest(request) {
    return request.mode === "navigate" || (request.headers.get("accept") || "").includes("text/html");
}

function isImageRequest(request) {
    return request.destination === "image";
}

async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    const networkFetch = fetch(request)
        .then((response) => {
            if (response && response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        })
        .catch(() => cached);

    return cached || networkFetch;
}

async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    const response = await fetch(request);

    if (response && response.ok) {
        cache.put(request, response.clone());
    }

    return response;
}

self.addEventListener("fetch", (event) => {
    const { request } = event;

    if (!request.url.startsWith("http")) {
        return;
    }

    if (request.method !== "GET") {
        return;
    }

    if (isNavigationRequest(request)) {
        event.respondWith(
            fetch(request)
                .then((response) => response)
                .catch(async () => {
                    const offlineResponse = await caches.match(OFFLINE_URL);

                    return offlineResponse || new Response("Offline", {
                        status: 503,
                        headers: { "Content-Type": "text/plain" }
                    });
                })
        );

        return;
    }

    if (isImageRequest(request)) {
        event.respondWith(cacheFirst(request, IMAGE_CACHE));

        return;
    }

    event.respondWith(staleWhileRevalidate(request, STATIC_CACHE));
});
