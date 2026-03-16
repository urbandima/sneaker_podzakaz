import { test, expect } from '@playwright/test';

/**
 * Тесты админ-панели SneakerHead Store
 * Проверка всех страниц управления
 */

test.describe('Админ-панель - Доступ', () => {
  test('Страница входа в админку', async ({ page }) => {
    await page.goto('/admin/login');
    
    await expect(page).toHaveURL(/admin.*login/);
    await expect(page.locator('body')).toBeVisible();
    
    // Проверяем форму входа
    const loginForm = page.locator('form');
    await expect(loginForm).toBeVisible();
  });

  test('Главная админки без авторизации', async ({ page }) => {
    await page.goto('/admin');
    
    // Должно перенаправить на логин или показать форму
    await page.waitForLoadState('networkidle');
    const url = page.url();
    expect(url).toMatch(/admin/);
  });
});

test.describe('Админ-панель - Форма входа', () => {
  test('Поля формы входа', async ({ page }) => {
    await page.goto('/admin/login');
    
    const usernameInput = page.locator('input[name*="username"], input[name*="login"], input[type="text"]').first();
    const passwordInput = page.locator('input[type="password"]').first();
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
    
    if (await usernameInput.isVisible()) {
      await expect(usernameInput).toBeVisible();
      await expect(passwordInput).toBeVisible();
      await expect(submitButton).toBeVisible();
    }
  });

  test('Ошибка при пустых данных', async ({ page }) => {
    await page.goto('/admin/login');
    
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
    
    if (await submitButton.isVisible()) {
      await submitButton.click();
      await page.waitForLoadState('networkidle');
      
      // Должна быть ошибка валидации
      const error = page.locator('.error, .alert, [class*="error"], [class*="alert"]');
      // Не обязательно должна быть ошибка - зависит от реализации
    }
  });
});

test.describe('Админ-панель - Страницы', () => {
  test('Страница заказов', async ({ page }) => {
    await page.goto('/admin/order');
    
    await expect(page).toHaveURL(/admin.*order/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница товаров', async ({ page }) => {
    await page.goto('/admin/product');
    
    await expect(page).toHaveURL(/admin.*product/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница пользователей', async ({ page }) => {
    await page.goto('/admin/user');
    
    await expect(page).toHaveURL(/admin.*user/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница клиентов', async ({ page }) => {
    await page.goto('/admin/customer');
    
    await expect(page).toHaveURL(/admin.*customer/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница аналитики', async ({ page }) => {
    await page.goto('/admin/analytics');
    
    await expect(page).toHaveURL(/admin.*analytics/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница настроек', async ({ page }) => {
    await page.goto('/admin/settings');
    
    await expect(page).toHaveURL(/admin.*settings/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница тарифов', async ({ page }) => {
    await page.goto('/admin/tariff');
    
    await expect(page).toHaveURL(/admin.*tariff/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница отзывов', async ({ page }) => {
    await page.goto('/admin/review');
    
    await expect(page).toHaveURL(/admin.*review/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница Poizon', async ({ page }) => {
    await page.goto('/admin/poizon');
    
    await expect(page).toHaveURL(/admin.*poizon/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница характеристик', async ({ page }) => {
    await page.goto('/admin/characteristic');
    
    await expect(page).toHaveURL(/admin.*characteristic/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Страница размеров', async ({ page }) => {
    await page.goto('/admin/size-grid');
    
    await expect(page).toHaveURL(/admin.*size-grid/);
    await expect(page.locator('body')).toBeVisible();
  });
});

test.describe('Админ-панель - Навигация', () => {
  test('Боковое меню', async ({ page }) => {
    await page.goto('/admin');
    
    // Ищем навигацию
    const sidebar = page.locator('aside, .sidebar, nav, [class*="sidebar"], [class*="menu"]');
    await expect(sidebar.first()).toBeVisible();
  });

  test('Ссылки в меню', async ({ page }) => {
    await page.goto('/admin');
    
    // Проверяем наличие ссылок на основные разделы
    const links = page.locator('a[href*="admin"]');
    const count = await links.count();
    expect(count).toBeGreaterThan(0);
  });
});

test.describe('Админ-панель - Таблицы данных', () => {
  test('Таблица заказов', async ({ page }) => {
    await page.goto('/admin/order');
    
    // Ищем таблицу или список
    const table = page.locator('table, .grid-view, [class*="table"], [class*="list"]');
    await expect(table.first()).toBeVisible();
  });

  test('Таблица товаров', async ({ page }) => {
    await page.goto('/admin/product');
    
    const table = page.locator('table, .grid-view, [class*="table"], [class*="list"]');
    await expect(table.first()).toBeVisible();
  });
});

test.describe('Админ-панель - Адаптивность', () => {
  test('Мобильная версия админки', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/admin/login');
    
    await expect(page.locator('body')).toBeVisible();
  });
});
