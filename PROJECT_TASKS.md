# Задачи проекта

## Выполненные задачи
- [x] Создана модель TariffCalculation для истории расчетов по тарифам
- [x] Исправлена ошибка "Class TariffCalculation not found" в TariffController
- [x] Исправлена ошибка "Class Yii not found" в PageController - добавлен импорт Yii
- [x] Исправлены 404 ошибки в клиентской части:
  - Создан файл /frontend/views/pages/about.php
  - Создан файл /frontend/views/pages/contacts.php
  - Исправлена структура директории /frontend/web/assets/
  - Добавлен .htaccess для assets директории
- [x] Исправлена ошибка View not Found - создана правильная структура /frontend/views/page/pages/
- [x] Исправлены 404 ошибки для всех контроллеров - создана правильная структура view директорий:
  - /frontend/views/catalog/index.php (исправлен путь)
  - /frontend/views/site/site/
  - /frontend/views/cart/cart/
  - /frontend/views/order/order/
  - /frontend/views/favorite/favorite/
  - /frontend/views/adminimport/adminimport/
  - /frontend/views/sitemap/sitemap/
- [x] Исправлена 404 ошибка каталога - удален конфликтующий модуль catalog из конфигурации
- [x] Исправлена 500 ошибка каталога - добавлен недостающий use FilterBuilder
- [x] Проведена полная проверка файловой базы и исправлены ошибки:
  - Исправлен namespace в SitemapController (app\services -> app\backend\services)
  - Удалены конфликтующие модули cart и account из web.php
  - Исправлен маршрут cart/cart/index -> cart/index
  - Добавлена недостающая переменная $customer в CartController
  - HTTP 200 OK для /catalog и /cart
- [x] Исправлена ошибка findSimilarProducts - добавлен метод в ProductRepository с многоуровневой стратегией поиска
- [x] Очищен OPcache и проверена работа метода - страница товара работает корректно

## Текущие задачи
- [ ] Проверить работу контроллера после создания модели
- [ ] Создать миграцию для таблицы tariff_calculation при необходимости

## Отложенные задачи
- [ ] Добавить валидацию для модели TariffCalculation
- [ ] Настроить логирование расчетов
