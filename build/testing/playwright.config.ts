import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for SneakerHead Store
 * Тестирование пользовательской части и админ-панели
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false, // Последовательное выполнение для стабильности
  forbidOnly: !!process.env.CI,
  retries: 1, // Один повтор при ошибке
  workers: 1, // Один worker для стабильности
  timeout: 30000, // 30 секунд на тест
  reporter: [['html'], ['list']],

  use: {
    baseURL: 'http://localhost:8080',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    headless: false, // Показывать браузер для отладки
    viewport: { width: 1280, height: 720 },
    ignoreHTTPSErrors: true,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // Сервер должен быть запущен вручную: php yii serve --port=8080 --docroot=frontend/web
  // webServer не нужен, так как сервер запускается отдельно
});
