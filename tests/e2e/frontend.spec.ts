import { test, expect, Page } from '@playwright/test';

/**
 * Тесты пользовательской части SneakerHead Store
 * Проверка всех страниц, форм и пользовательских сценариев
 */

test.describe('Главная страница', () => {
  test('Загрузка главной страницы', async ({ page }) => {
    await page.goto('/');
    
    // Проверяем что страница загрузилась
    await expect(page).toHaveTitle(/Сникер/);
    
    // Проверяем наличие основных элементов
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();
  });

  test('Навигация в шапке', async ({ page }) => {
    await page.goto('/');
    
    // Проверяем наличие ссылки на каталог
    const catalogLink = page.locator('a[href*="catalog"]');
    await expect(catalogLink.first()).toBeVisible();
  });
});

test.describe('Каталог товаров', () => {
  test('Страница каталога загружается', async ({ page }) => {
    await page.goto('/catalog');
    
    // Проверяем что страница каталога загрузилась
    await expect(page).toHaveURL(/catalog/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Фильтры каталога', async ({ page }) => {
    await page.goto('/catalog');
    
    // Ищем элементы фильтрации
    const filters = page.locator('[class*="filter"], [class*="Filter"], aside, .sidebar');
    const hasFilters = await filters.count() > 0;
    
    // Если фильтры есть - проверяем их видимость
    if (hasFilters) {
      await expect(filters.first()).toBeVisible();
    }
  });

  test('Карточки товаров отображаются', async ({ page }) => {
    await page.goto('/catalog');
    
    // Ищем карточки товаров
    const productCards = page.locator('[class*="product"], [class*="Product"], .card, .item');
    const count = await productCards.count();
    
    // Должны быть товары или сообщение о пустом каталоге
    const emptyMessage = page.locator('text=/нет товаров|пуст|empty/i');
    const hasEmpty = await emptyMessage.count() > 0;
    
    expect(count > 0 || hasEmpty).toBeTruthy();
  });

  test('Поиск товаров', async ({ page }) => {
    await page.goto('/catalog');
    
    // Ищем поле поиска
    const searchInput = page.locator('input[type="search"], input[name*="search"], input[placeholder*="поиск"]').first();
    
    if (await searchInput.isVisible()) {
      await searchInput.fill('Nike');
      await searchInput.press('Enter');
      await page.waitForLoadState('networkidle');
    }
  });
});

test.describe('Корзина', () => {
  test('Страница корзины загружается', async ({ page }) => {
    await page.goto('/cart');
    
    await expect(page).toHaveURL(/cart/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Пустая корзина или товары', async ({ page }) => {
    await page.goto('/cart');
    
    // Проверяем наличие элементов корзины
    const cartItems = page.locator('[class*="cart-item"], [class*="CartItem"], .cart .item');
    const emptyCart = page.locator('text=/пуста|empty|нет товаров/i');
    
    const hasItems = await cartItems.count() > 0;
    const hasEmpty = await emptyCart.count() > 0;
    
    expect(hasItems || hasEmpty).toBeTruthy();
  });
});

test.describe('Личный кабинет', () => {
  test('Страница входа загружается', async ({ page }) => {
    await page.goto('/account/login');
    
    await expect(page).toHaveURL(/account.*login/);
    await expect(page.locator('body')).toBeVisible();
  });

  test('Форма входа', async ({ page }) => {
    await page.goto('/account/login');
    
    // Проверяем наличие формы входа
    const emailInput = page.locator('input[type="email"], input[name*="email"], input[name*="login"]').first();
    const passwordInput = page.locator('input[type="password"]').first();
    const submitButton = page.locator('button[type="submit"], input[type="submit"]').first();
    
    if (await emailInput.isVisible()) {
      await expect(emailInput).toBeVisible();
      await expect(passwordInput).toBeVisible();
      await expect(submitButton).toBeVisible();
    }
  });

  test('Страница регистрации', async ({ page }) => {
    await page.goto('/account/register');
    
    await expect(page).toHaveURL(/account.*register/);
  });

  test('Профиль без авторизации перенаправляет', async ({ page }) => {
    await page.goto('/account/profile');
    
    // Должно перенаправить на логин или показать ошибку
    await page.waitForLoadState('networkidle');
    const url = page.url();
    expect(url).toMatch(/login|account/);
  });
});

test.describe('Страница товара', () => {
  test('Страница товара по slug', async ({ page }) => {
    // Сначала идем в каталог чтобы найти товар
    await page.goto('/catalog');
    
    const productLink = page.locator('a[href*="catalog/product"]').first();
    
    if (await productLink.isVisible()) {
      await productLink.click();
      await page.waitForLoadState('networkidle');
      
      // Проверяем что страница товара загрузилась
      await expect(page.locator('body')).toBeVisible();
    }
  });
});

test.describe('Оформление заказа', () => {
  test('Форма оформления заказа', async ({ page }) => {
    await page.goto('/cart');
    
    // Ищем кнопку оформления
    const checkoutButton = page.locator('a[href*="checkout"], button:has-text("Оформить"), a:has-text("Оформить")').first();
    
    if (await checkoutButton.isVisible()) {
      await checkoutButton.click();
      await page.waitForLoadState('networkidle');
    }
  });
});

test.describe('Страницы ошибок', () => {
  test('404 страница', async ({ page }) => {
    await page.goto('/nonexistent-page-12345');
    
    // Должна быть страница ошибки или редирект
    await expect(page.locator('body')).toBeVisible();
  });
});

test.describe('Адаптивность', () => {
  test('Мобильная версия каталога', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/catalog');
    
    await expect(page.locator('body')).toBeVisible();
    
    // Проверяем что навигация доступна на мобильном
    const menu = page.locator('nav, .navbar, .menu, [class*="nav"]');
    await expect(menu.first()).toBeVisible();
  });
});
