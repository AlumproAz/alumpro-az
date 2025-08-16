const CACHE_NAME = 'alumpro-az-v1.3';
const STATIC_CACHE_NAME = 'alumpro-static-v1.3';
const DYNAMIC_CACHE_NAME = 'alumpro-dynamic-v1.3';

// Static assets to cache
const STATIC_ASSETS = [
    '/',
    '/home.php',
    '/login.php',
    '/assets/css/style.css',
    '/assets/css/modern-dashboard.css',
    '/assets/js/script.js',
    '/assets/js/main.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    '/manifest.json'
];

// Network-first resources
const NETWORK_FIRST = [
    '/api/',
    '/admin/',
    '/sales/',
    '/customer/'
];

// Cache-first resources
const CACHE_FIRST = [
    '/assets/',
    'https://cdn.jsdelivr.net/',
    'https://fonts.googleapis.com/',
    'https://fonts.gstatic.com/'
];

// Install event
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        Promise.all([
            // Cache static assets
            caches.open(STATIC_CACHE_NAME).then(cache => {
                console.log('Service Worker: Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            }),
            // Skip waiting to activate immediately
            self.skipWaiting()
        ])
    );
});

// Activate event
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        Promise.all([
            // Clean old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE_NAME && 
                            cacheName !== DYNAMIC_CACHE_NAME &&
                            cacheName.startsWith('alumpro-')) {
                            console.log('Service Worker: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            // Claim all clients
            self.clients.claim()
        ])
    );
});

// Fetch event
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Skip chrome-extension and moz-extension requests
    if (url.protocol === 'chrome-extension:' || url.protocol === 'moz-extension:') {
        return;
    }
    
    // Handle different caching strategies
    if (NETWORK_FIRST.some(path => url.pathname.startsWith(path))) {
        // Network first for dynamic content
        event.respondWith(networkFirst(request));
    } else if (CACHE_FIRST.some(path => url.href.startsWith(path))) {
        // Cache first for static assets
        event.respondWith(cacheFirst(request));
    } else {
        // Stale while revalidate for everything else
        event.respondWith(staleWhileRevalidate(request));
    }
});

// Network first strategy
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Network failed, trying cache:', error);
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            return caches.match('/offline.html') || new Response('Offline', { status: 503 });
        }
        
        throw error;
    }
}

// Cache first strategy
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Network failed for static asset:', error);
        throw error;
    }
}

// Stale while revalidate strategy
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const cachedResponse = await cache.match(request);
    
    const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(error => {
        console.log('Network failed during revalidation:', error);
        return cachedResponse;
    });
    
    return cachedResponse || fetchPromise;
}

// Handle background sync
self.addEventListener('sync', event => {
    console.log('Background sync triggered:', event.tag);
    
    if (event.tag === 'order-sync') {
        event.waitUntil(syncOrders());
    } else if (event.tag === 'inventory-sync') {
        event.waitUntil(syncInventory());
    }
});

// Push notification event
self.addEventListener('push', event => {
    console.log('Push notification received:', event);
    
    const options = {
        body: 'Yeni bildiriş var!',
        icon: '/assets/img/icon-192.png',
        badge: '/assets/img/badge.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1
        },
        actions: [
            {
                action: 'explore',
                title: 'Bax',
                icon: '/assets/img/checkmark.png'
            },
            {
                action: 'close',
                title: 'Bağla',
                icon: '/assets/img/xmark.png'
            }
        ]
    };
    
    if (event.data) {
        const data = event.data.json();
        options.body = data.body || options.body;
        options.title = data.title || 'Alumpro.Az';
        options.data = { ...options.data, ...data };
    }
    
    event.waitUntil(
        self.registration.showNotification('Alumpro.Az', options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    if (event.action === 'explore') {
        event.waitUntil(
            clients.openWindow('/admin/')
        );
    } else if (event.action === 'close') {
        // Just close the notification
        return;
    } else {
        // Default action - open the app
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});

// Handle message from main thread
self.addEventListener('message', event => {
    console.log('Message received by service worker:', event.data);
    
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

// Sync functions
async function syncOrders() {
    try {
        console.log('Syncing pending orders...');
        return Promise.resolve();
    } catch (error) {
        console.error('Order sync failed:', error);
        throw error;
    }
}

async function syncInventory() {
    try {
        console.log('Syncing inventory updates...');
        return Promise.resolve();
    } catch (error) {
        console.error('Inventory sync failed:', error);
        throw error;
    }
}