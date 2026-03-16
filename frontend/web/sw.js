/**
 * Service Worker для PWA
 * Версия: 1.0.0
 */

const CACHE_NAME = 'sneaker-store-v1';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';
const IMAGE_CACHE = 'images-v1';

// Статические ресурсы для кэширования
const STATIC_ASSETS = [
    '/',
    '/catalog',
    '/cart',
    '/css/main.css',
    '/css/catalog.css',
    '/css/product.css',
    '/js/global-helpers.js',
    '/js/cart.js',
    '/js/catalog.js',
    '/images/logo.svg',
    '/images/icons/icon-192x192.png',
    '/manifest.json',
];

// Установка Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
            .catch((error) => {
                console.error('[SW] Failed to cache static assets:', error);
            })
    );
});

// Активация Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== STATIC_CACHE && name !== DYNAMIC_CACHE && name !== IMAGE_CACHE)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Обработка запросов
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Пропускаем POST, PUT, DELETE запросы
    if (request.method !== 'GET') {
        return;
    }
    
    // API запросы - Network First
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/cart/')) {
        event.respondWith(networkFirst(request));
        return;
    }
    
    // Изображения - Cache First с fallback
    if (request.destination === 'image') {
        event.respondWith(cacheFirst(request, IMAGE_CACHE));
        return;
    }
    
    // Статические ресурсы - Cache First
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }
    
    // Остальное - Stale While Revalidate
    event.respondWith(staleWhileRevalidate(request, DYNAMIC_CACHE));
});

// Стратегия: Cache First
async function cacheFirst(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        // Fallback для изображений
        if (request.destination === 'image') {
            return caches.match('/images/placeholder.png');
        }
        throw error;
    }
}

// Стратегия: Network First
async function networkFirst(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }
        throw error;
    }
}

// Стратегия: Stale While Revalidate
async function staleWhileRevalidate(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cached = await cache.match(request);
    
    const fetchPromise = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => cached);
    
    return cached || fetchPromise;
}

// Проверка статического ресурса
function isStaticAsset(pathname) {
    return pathname.endsWith('.css') ||
           pathname.endsWith('.js') ||
           pathname.endsWith('.woff2') ||
           pathname.endsWith('.woff') ||
           pathname.endsWith('.ttf') ||
           pathname.startsWith('/images/icons/');
}

// Push уведомления
self.addEventListener('push', (event) => {
    const data = event.data?.json() ?? {};
    
    const options = {
        body: data.body || 'Новое уведомление',
        icon: '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
        },
        actions: [
            { action: 'open', title: 'Открыть' },
            { action: 'close', title: 'Закрыть' },
        ],
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'Sneaker Store', options)
    );
});

// Обработка клика на уведомление
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'open' || !event.action) {
        const url = event.notification.data.url;
        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true })
                .then((clientList) => {
                    // Ищем открытое окно
                    for (const client of clientList) {
                        if (client.url === url && 'focus' in client) {
                            return client.focus();
                        }
                    }
                    // Открываем новое окно
                    if (clients.openWindow) {
                        return clients.openWindow(url);
                    }
                })
        );
    }
});

// Background Sync для офлайн заказов
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOrders());
    }
});

async function syncOrders() {
    try {
        const pendingOrders = await getPendingOrders();
        
        for (const order of pendingOrders) {
            const response = await fetch('/checkout/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': order.csrfToken,
                },
                body: JSON.stringify(order.data),
            });
            
            if (response.ok) {
                await removePendingOrder(order.id);
            }
        }
    } catch (error) {
        console.error('[SW] Failed to sync orders:', error);
    }
}

// IndexedDB helpers (упрощённые)
async function getPendingOrders() {
    return [];
}

async function removePendingOrder(id) {
    return true;
}
