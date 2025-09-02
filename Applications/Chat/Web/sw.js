self.addEventListener('install', event => {
  event.waitUntil(
    caches.open('couckan-cache-v1').then(cache => cache.addAll([
      './',
      './index.php'
    ]))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(resp => resp || fetch(event.request))
  );
});

self.addEventListener('push', event => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'Notification';
  const options = {
    body: data.body || '',
    icon: data.icon || ''
  };
  event.waitUntil(self.registration.showNotification(title, options));
});
