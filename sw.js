
const CACHE = "pwa0.0.1";
self.addEventListener('install', function(event) {
    var indexPage = new Request('/');
    event.waitUntil(fetch(indexPage).then(function(response) {
        var response2 = response.clone();
        return caches.open(CACHE).then(function(cache) {
            return cache.put(indexPage, response2)
        })
    }))
});

self.addEventListener('fetch', function (event) {
    event.respondWith(
        fetch(event.request).then(function(networkResponse) {
            return networkResponse
        })
    )
})