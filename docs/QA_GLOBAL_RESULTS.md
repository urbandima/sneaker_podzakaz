# QA Global Results — 2026-04-26

_Sweep scope: tasks #15/#19/#20/#24/#25 + global refactor + integrations._

---

## 1. Tasks закрыты

| # | Задача | Коммит | Статус |
|---|--------|--------|--------|
| #15 | Apply-customer-data button + diff + order_history log | `ddffe07` | ✅ |
| #19 | МойСклад sync log — last attempt badge, error text, retry | `9921be0` + fix `133db9d` | ✅ |
| #20 | Brand mismatch filter + bulk fix-brand preview/apply | `c34f77d` | ✅ |
| #24 | Process map 4 scenarios with BPMN table | `e04c0ab` | ✅ |
| #25 | RBAC 4 roles, 8 permissions, role in order_history | `3737a6d` + fix `5aedd42` | ✅ |

---

## 2. Баги найдены и исправлены

### #19 — moysklad_sync_log: неверные имена колонок (критично)
- **Что**: view запрашивал `entity_type='order' AND entity_id=` — таких колонок нет, есть `order_id`
- **Дополнительно**: `$msLastLog['status']` → нужно `success` (int); `'details'` → нужно `'message'`
- **Фикс**: `133db9d` — исправлено, query wrapped в try/catch

### #25 — RBAC role hierarchy reversed (критично)
- **Что**: migration вставляла `(parent='manager_creator', child='admin')` — admin получал минимум прав
- **Ожидаемо**: `(parent='admin', child='manager_editor')`, `(parent='manager_editor', child='manager_creator')`
- **Фикс**: `5aedd42` — направление parent/child исправлено в safeUp и safeDown

---

## 3. Integrations health

| Сервис | Статус | Health-check метод | Замечания |
|--------|--------|--------------------|-----------|
| AmoCRM | ✅ | `AmocrmClient::getAccount()` | Long token до 2029; rate limit (429) обрабатывается с retry |
| МойСклад | ✅ | `MoySkladService::testConnection()` | Метод был, работает |
| ДоброПост | ✅ | `DobroPostService::ping()` | Метод добавлен (`ac2877e`) |
| Poizon | ✅ | `PoizonApiService::testConnection()` | Метод был |

**AmoCRM client улучшения** (`ac2877e`):
- `curl_errno` проверяется — раньше при таймауте возвращал null без лога
- 429 Rate Limit: sleep(1) + one retry
- 5xx: warning лог + return null (не бросает исключение)

---

## 4. Security fixes

| Файл | Проблема | Исправление |
|------|----------|-------------|
| `scripts/import_from_moysklad.php` | `DB_PASS = 'secret'` захардкожен | Читает из `.env` → `DB_PASSWORD` |
| `scripts/verify_ms_import.php` | То же | То же |

---

## 5. TODO/FIXME маркеры

Найдено ~25 маркеров. Распределение по приоритетам:

**Закрыты этим рефакторингом:**
- `scripts/*.php` DB_PASS — исправлено

**Низкий приоритет (backlog):**
- `CustomerController.php:46` — CSRF-токен в заголовке (работает и так, но legacy)
- `SettingsController.php:176` — то же
- `views/product/edit.php:1600` — `deleteCharacteristicInline()` пустая функция

**Не нужно исправлять (noise):**
- `backend/web/js/admin-settings.js` + `frontend/web/js/admin-settings.js` — 8 TODO-стабов старого UI; весь AmoCRM/МС теперь в plugin UI. Файлы дублированы с минимальными отличиями — legacy, не удалять (используются в старых настройках).
- Маркеры `XXX` в коде — это форматы строк (placeholder-тексты), не технические долги

**Реальный долг:**
- `backend/modules/catalog/models/AnalyticsEvent.php` — "TODO: Создать полную реализацию модели" — класс не используется, безопасно оставить
- `backend/modules/catalog/models/ProductReview.php` — то же
- `UpsellService.php:148` — трекинг кликов по рекомендациям — future feature
- `ImportController.php:309` — уведомления при импорте — future feature

---

## 6. Дубликаты сервисов

Проверены: `RevenueService`, `DeliveryService`, `OrderHistoryService`.

- **RevenueService** — один экземпляр (`backend/shared/services/RevenueService.php`) ✅
- **DeliveryService** — один (`backend/shared/services/DeliveryService.php`) ✅
- **OrderHistoryService** — не существует как класс; логика в `OrderHistory::log()` (static method) ✅ — дублирования нет

---

## 7. RBAC coverage

| Контроллер | Критичные actions | Защита |
|-----------|------------------|--------|
| `OrderController` | `actionCreate`, `actionUpdate` | `requirePermission('createOrder'/'editOrder')` ✅ |
| `ProductController` | `actionFixBrand`, `actionDelete` | `requirePermission('manageProducts')` ✅ |
| `PluginController` | `actionAmocrmSave` | `requirePermission('manageSettings')` ✅ (добавлено `ac2877e`) |
| Все admin controllers | базовый доступ | `BaseAdminController::behaviors()` → `AccessControl` ✅ |

**Не защищены явно** (полагаются на isAdmin fallback):
- `PluginController::actionAmocrmFieldsSave` — добавить `manageSettings` в следующей итерации
- Ряд action в PluginController (telegram/currency save) — аналогично

**order_history.user_role**: заполняется через `OrderHistory::log()` → `authManager->getRolesByUser()` ✅

---

## 8. N+1 queries

Аудит `DashboardController` и `OrderController`:
- `OrderController::actionIndex` — `->with(['creator', 'logist', 'orderItems', 'customer'])` ✅
- `OrderController::actionExport` / `actionExportCsv` — аналогично ✅
- Проблем не найдено

---

## 9. Performance

Lighthouse запустить невозможно без браузера/сервера. Известные оптимизации из предыдущих коммитов:
- Eager loading в Order queries ✅
- `appendTimestamp` в assetManager ✅
- Sitemap автогенерация ✅

Рекомендация для следующего цикла: добавить `EXPLAIN` на топ-5 запросов в OrderController через Yii2 debug toolbar.

---

## 10. Итог коммитов этого sweep

```
ac2877e fix(global): security, integrations health-check, rate limits
5aedd42 fix(rbac): role hierarchy inserts were reversed in migration
133db9d fix(admin/order): moysklad_sync_log query used wrong column names
b5c633f docs(amocrm): architecture doc; improve RBAC canDo fallback
d5f031e feat(amocrm): widget API endpoints, fields tab, CLI command
```
