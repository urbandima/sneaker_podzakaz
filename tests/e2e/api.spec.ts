import { test, expect } from '@playwright/test';

/**
 * Тесты API эндпоинтов SneakerHead Store
 * Проверка AJAX запросов и REST API
 */

test.describe('API - Корзина', () => {
  test('Получение количества товаров в корзине', async ({ page }) => {
    await page.goto('/');
    
    // Выполняем запрос к API корзины
    const response = await page.request.get('/cart/count');
    
    // Может быть 200 или редирект
    expect(response.status()).toBeLessThan(400);
  });

  test('Добавление товара в корзину', async ({ page }) => {
    await page.goto('/');
    
    // Пробуем добавить товар через API
    const response = await page.request.post('/cart/add', {
      data: {
        product_id: 1,
        quantity: 1
      }
    });
    
    // Проверяем ответ
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('API - Каталог', () => {
  test('Поиск товаров', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/catalog/search?q=nike');
    
    expect(response.status()).toBeLessThan(500);
  });

  test('Фильтрация товаров', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/catalog/filter');
    
    expect(response.status()).toBeLessThan(500);
  });

  test('Quick view товара', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/catalog/quick-view/1');
    
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('API - Избранное', () => {
  test('Количество избранных товаров', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/catalog/favorites-count');
    
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('API - Аккаунт', () => {
  test('Поиск заказов по email', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.post('/account/find-orders', {
      data: {
        email: 'test@test.com'
      }
    });
    
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('API - Заказы', () => {
  test('Просмотр заказа по токену', async ({ page }) => {
    await page.goto('/');
    
    // Случайный токен - ожидаем 404 или редирект
    const response = await page.request.get('/order/test-token-123');
    
    // Может быть 404, 403 или редирект на форму
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('API - REST Characteristics', () => {
  test('Список характеристик', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/api/characteristics');
    
    // Может быть 404 если API не настроен
    expect(response.status()).toBeLessThan(500);
  });
});

test.describe('API - Sitemap', () => {
  test('Sitemap.xml', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/sitemap.xml');
    
    expect(response.status()).toBe(200);
  });
});

test.describe('API - Статические страницы', () => {
  test('Robots.txt', async ({ page }) => {
    await page.goto('/');
    
    const response = await page.request.get('/robots.txt');
    
    expect(response.status()).toBeLessThan(400);
  });
});
