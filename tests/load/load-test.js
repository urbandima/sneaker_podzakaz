# K6 Load Testing Configuration
# Запуск: k6 run load-test.js

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Кастомные метрики
const errorRate = new Rate('errors');
const requestDuration = new Trend('request_duration');

// Конфигурация теста
export const options = {
    stages: [
        // Ramp-up: 10 -> 50 пользователей за 30 сек
        { duration: '30s', target: 50 },
        // Плато: 50 пользователей 1 минуту
        { duration: '1m', target: 50 },
        // Spike: 50 -> 200 пользователей за 10 сек
        { duration: '10s', target: 200 },
        // Плато: 200 пользователей 30 сек
        { duration: '30s', target: 200 },
        // Ramp-down: 200 -> 0 за 20 сек
        { duration: '20s', target: 0 },
    ],
    thresholds: {
        // 95% запросов должны быть быстрее 500ms
        http_req_duration: ['p(95)<500', 'p(99)<1000'],
        // Ошибки менее 1%
        errors: ['rate<0.01'],
        // Время ответа API
        request_duration: ['p(95)<300'],
    },
};

// Базовый URL
const BASE_URL = __ENV.BASE_URL || 'https://sneaker-store.by';

// CSRF токен (получаем при первом запросе)
let csrfToken = '';

export function setup() {
    // Получаем CSRF токен
    const res = http.get(`${BASE_URL}/`);
    const csrfMatch = res.body.match(/<meta name="csrf-token" content="([^"]+)"/);
    if (csrfMatch) {
        csrfToken = csrfMatch[1];
    }
    return { csrfToken };
}

export default function (data) {
    // ==================== CATALOG PAGE ====================
    group('Catalog', () => {
        const catalogRes = http.get(`${BASE_URL}/catalog`);
        
        check(catalogRes, {
            'catalog status 200': (r) => r.status === 200,
            'catalog has products': (r) => r.body.includes('product-card'),
            'catalog response time < 500ms': (r) => r.timings.duration < 500,
        });
        
        errorRate.add(catalogRes.status !== 200);
        requestDuration.add(catalogRes.timings.duration);
        
        sleep(1);
    });
    
    // ==================== PRODUCT PAGE ====================
    group('Product Page', () => {
        // Случайный ID товара (1-100)
        const productId = Math.floor(Math.random() * 100) + 1;
        const productRes = http.get(`${BASE_URL}/product/${productId}`);
        
        check(productRes, {
            'product status 200 or 404': (r) => r.status === 200 || r.status === 404,
            'product response time < 400ms': (r) => r.timings.duration < 400,
        });
        
        errorRate.add(productRes.status !== 200 && productRes.status !== 404);
        sleep(1);
    });
    
    // ==================== API: CART COUNT ====================
    group('API: Cart Count', () => {
        const cartRes = http.get(`${BASE_URL}/cart/count`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        
        check(cartRes, {
            'cart count status 200': (r) => r.status === 200,
            'cart count is JSON': (r) => r.headers['Content-Type'].includes('application/json'),
            'cart count response time < 100ms': (r) => r.timings.duration < 100,
        });
        
        errorRate.add(cartRes.status !== 200);
        requestDuration.add(cartRes.timings.duration);
        
        sleep(0.5);
    });
    
    // ==================== API: FILTER ====================
    group('API: Filter', () => {
        const filterRes = http.post(`${BASE_URL}/catalog/filter`, JSON.stringify({
            brands: [1, 2],
            page: 1,
            perPage: 24,
            sort: 'popular',
        }), {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': data.csrfToken,
            },
        });
        
        check(filterRes, {
            'filter status 200': (r) => r.status === 200,
            'filter is JSON': (r) => r.headers['Content-Type'].includes('application/json'),
            'filter response time < 300ms': (r) => r.timings.duration < 300,
        });
        
        errorRate.add(filterRes.status !== 200);
        requestDuration.add(filterRes.timings.duration);
        
        sleep(1);
    });
    
    // ==================== SEARCH ====================
    group('Search', () => {
        const searchTerms = ['nike', 'adidas', 'jordan', 'air max', 'yeezy'];
        const term = searchTerms[Math.floor(Math.random() * searchTerms.length)];
        
        const searchRes = http.get(`${BASE_URL}/catalog?search=${encodeURIComponent(term)}`);
        
        check(searchRes, {
            'search status 200': (r) => r.status === 200,
            'search response time < 600ms': (r) => r.timings.duration < 600,
        });
        
        errorRate.add(searchRes.status !== 200);
        sleep(1);
    });
}

// Отчёт после теста
export function teardown(data) {
    console.log('Load test completed');
}

// Функция для группировки запросов
function group(name, fn) {
    const start = new Date();
    fn();
    const duration = new Date() - start;
    console.log(`Group ${name}: ${duration}ms`);
}
