# Тестирование модальных окон страницы товара

## Обзор

Созданы автоматические тесты для проверки работы модальных окон на странице товара:
- Модалка "Купить в 1 клик"
- Модалка "Таблица размеров"

## Структура тестов

### 1. Функциональные тесты (Codeception)
**Файл:** `tests/functional/ProductModalsCest.php`

Проверяют работу модальных окон в реальном браузере:
- Открытие/закрытие модалок
- Валидация форм
- Отправка данных
- Обработка событий (ESC, клик на фон)
- Автофокус и сброс форм

### 2. Unit-тесты JavaScript (Jest)
**Файл:** `tests/unit/js/product-modals.test.js`

Проверяют логику JS-функций:
- Корректность работы функций открытия/закрытия
- Обработка событий клавиатуры и мыши
- Отправка AJAX-запросов
- Выбор размеров из таблицы
- Доступность функций в глобальной области

## Запуск тестов

### Функциональные тесты (Codeception)

```bash
# Все функциональные тесты
vendor/bin/codecept run functional

# Только тесты модальных окон
vendor/bin/codecept run functional ProductModalsCest

# Конкретный тест
vendor/bin/codecept run functional ProductModalsCest:testOpenQuickOrderModal

# С подробным выводом
vendor/bin/codecept run functional ProductModalsCest --debug
```

### Unit-тесты JavaScript (Jest)

```bash
# Установка зависимостей (первый раз)
npm install --save-dev jest @types/jest jsdom

# Запуск всех JS-тестов
npm test

# Только тесты модальных окон
npm test product-modals

# С покрытием кода
npm test -- --coverage

# В режиме watch (автоматический перезапуск)
npm test -- --watch
```

## Покрытие тестами

### Функциональные тесты проверяют:
✅ Открытие модалки "Купить в 1 клик"  
✅ Закрытие модалки кнопкой  
✅ Закрытие модалки кликом на фон  
✅ Закрытие модалки клавишей ESC  
✅ Валидация обязательных полей  
✅ Отправка быстрого заказа  
✅ Открытие/закрытие таблицы размеров  
✅ Автофокус на первое поле  
✅ Сброс формы при закрытии  
✅ Наличие глобальных функций  

### Unit-тесты проверяют:
✅ Логику открытия/закрытия модалок  
✅ Сброс формы при закрытии  
✅ Формирование FormData с product_id  
✅ Обработку успешного ответа сервера  
✅ Обработку ошибок сервера  
✅ Выбор доступных размеров  
✅ Блокировку недоступных размеров  
✅ Обработку событий ESC и клика на фон  
✅ Экспорт функций в window  

## Структура файлов

```
/Users/user/CascadeProjects/splitwise/
├── web/js/
│   └── product-modals.js          # Основной JS-модуль
├── tests/
│   ├── functional/
│   │   └── ProductModalsCest.php  # Функциональные тесты
│   └── unit/js/
│       ├── product-modals.test.js # Unit-тесты
│       └── setup.js               # Настройка окружения Jest
├── jest.config.js                 # Конфигурация Jest
└── package.json                   # NPM-зависимости
```

## Добавление новых тестов

### Функциональный тест (Codeception)

```php
public function testNewFeature(FunctionalTester $I)
{
    $I->wantTo('описание теста');
    $I->amOnPage(['/catalog/product', 'id' => $this->testProduct->id]);
    
    // Ваши проверки
    $I->click('button');
    $I->seeElement('#element');
}
```

### Unit-тест (Jest)

```javascript
test('описание теста', () => {
    // Подготовка
    const modal = document.getElementById('quickOrderModal');
    
    // Действие
    window.openQuickOrderModal();
    
    // Проверка
    expect(modal.style.display).toBe('flex');
});
```

## Continuous Integration

Тесты можно интегрировать в CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      
      - name: Install Composer dependencies
        run: composer install
      
      - name: Run Codeception tests
        run: vendor/bin/codecept run functional
      
      - name: Setup Node.js
        uses: actions/setup-node@v2
        with:
          node-version: '16'
      
      - name: Install NPM dependencies
        run: npm install
      
      - name: Run Jest tests
        run: npm test -- --coverage
```

## Отладка тестов

### Codeception
```bash
# Пошаговое выполнение
vendor/bin/codecept run functional ProductModalsCest --debug

# Скриншоты при ошибках (настроить в codeception.yml)
vendor/bin/codecept run functional --html
```

### Jest
```bash
# Подробный вывод
npm test -- --verbose

# Только упавшие тесты
npm test -- --onlyFailures

# Отладка в Node.js
node --inspect-brk node_modules/.bin/jest --runInBand
```

## Проверка синтаксиса

```bash
# PHP
php -l views/catalog/product.php

# JavaScript
npx eslint web/js/product-modals.js
```

## Полезные команды

```bash
# Проверить все тесты перед коммитом
composer test && npm test

# Генерация отчёта о покрытии
npm test -- --coverage --coverageReporters=html
# Отчёт будет в tests/coverage/index.html

# Обновить снапшоты (если используются)
npm test -- -u
```

## Troubleshooting

### Проблема: Тесты падают с ошибкой "Element not found"
**Решение:** Увеличьте таймауты ожидания или проверьте селекторы

### Проблема: Jest не находит модули
**Решение:** Проверьте пути в `jest.config.js` и установите зависимости

### Проблема: Codeception не может подключиться к БД
**Решение:** Проверьте настройки в `tests/functional.suite.yml`

## Дополнительные ресурсы

- [Codeception Documentation](https://codeception.com/docs)
- [Jest Documentation](https://jestjs.io/docs/getting-started)
- [Testing Best Practices](https://github.com/goldbergyoni/javascript-testing-best-practices)
