# Тестирование Trailing Slash Fix

## Быстрый тест в браузере

### 1. Откройте DevTools
- **Chrome/Edge:** `F12` или `Cmd+Option+I` (Mac)
- **Safari:** `Cmd+Option+I`
- **Firefox:** `F12`

### 2. Перейдите на Network Tab
- Включите "Preserve log" (сохранять историю)
- Можно включить "Disable cache" для чистоты

### 3. Тестовые URL

#### ✅ Тест 1: Базовый редирект
```
Откройте: http://localhost:8080/catalog/
Ожидаемое:
  - Status: 301 Moved Permanently
  - Location: http://localhost:8080/catalog
  - URL в адресной строке изменится на /catalog
```

#### ✅ Тест 2: Редирект с query параметрами
```
Откройте: http://localhost:8080/catalog/?gender=male
Ожидаемое:
  - Status: 301 Moved Permanently
  - Location: http://localhost:8080/catalog?gender=male
  - Query параметры сохраняются
```

#### ✅ Тест 3: Редирект с пагинацией
```
Откройте: http://localhost:8080/catalog/?page=2
Ожидаемое:
  - Status: 301 Moved Permanently
  - Location: http://localhost:8080/catalog?page=2
```

#### ✅ Тест 4: URL без trailing slash (без редиректа)
```
Откройте: http://localhost:8080/catalog
Ожидаемое:
  - Status: 200 OK
  - БЕЗ редиректа
  - Страница загружается напрямую
```

#### ✅ Тест 5: Корневой URL (исключение)
```
Откройте: http://localhost:8080/
Ожидаемое:
  - Status: 200 OK
  - БЕЗ редиректа (корневой / остается)
```

---

## Проверка в Network Tab

### Что искать:

1. **Первый запрос** (с trailing slash):
   ```
   Request URL: http://localhost:8080/catalog/
   Status Code: 301 Moved Permanently
   Location: http://localhost:8080/catalog
   ```

2. **Второй запрос** (после редиректа):
   ```
   Request URL: http://localhost:8080/catalog
   Status Code: 200 OK
   Document: index.php
   ```

### Screenshot примера:
```
Name                    Status  Type        Size
catalog/               301     redirect    0 B
catalog                200     document    45 KB
catalog-inline.css     200     stylesheet  12 KB
catalog-card.css       200     stylesheet  8 KB
```

---

## Terminal тест (cURL)

### Базовый редирект
```bash
curl -I http://localhost:8080/catalog/
```

**Ожидаемый вывод:**
```
HTTP/1.1 301 Moved Permanently
Date: Sat, 09 Nov 2025 23:25:00 GMT
Server: Apache
Location: http://localhost:8080/catalog
Content-Type: text/html; charset=UTF-8
```

### С query параметрами
```bash
curl -I "http://localhost:8080/catalog/?page=2"
```

**Ожидаемый вывод:**
```
HTTP/1.1 301 Moved Permanently
Location: http://localhost:8080/catalog?page=2
```

### Следовать редиректу (-L флаг)
```bash
curl -IL http://localhost:8080/catalog/
```

**Ожидаемый вывод:**
```
HTTP/1.1 301 Moved Permanently
Location: http://localhost:8080/catalog

HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
```

---

## Проверка Canonical URL

### В браузере
1. Откройте `http://localhost:8080/catalog`
2. View Page Source (`Cmd+U` или `Ctrl+U`)
3. Найдите тег `<link rel="canonical">`

**Должно быть:**
```html
<link rel="canonical" href="http://localhost:8080/catalog">
```

**НЕ должно быть:**
```html
<link rel="canonical" href="http://localhost:8080/catalog/">
```

### В Terminal
```bash
curl -s http://localhost:8080/catalog | grep canonical
```

**Ожидаемый вывод:**
```html
<link rel="canonical" href="http://localhost:8080/catalog">
```

---

## Чек-лист тестирования

### Редиректы
- [ ] `/catalog/` → 301 → `/catalog` ✅
- [ ] `/catalog/?page=2` → 301 → `/catalog?page=2` ✅
- [ ] `/catalog/?gender=male` → 301 → `/catalog?gender=male` ✅
- [ ] `/catalog` → 200 OK (без редиректа) ✅
- [ ] `/` → 200 OK (корневой, без редиректа) ✅

### Canonical URL
- [ ] Canonical всегда без trailing slash ✅
- [ ] Canonical корректен для `/catalog` ✅
- [ ] Canonical корректен для `/catalog?page=2` ✅

### Дизайн/Стили
- [ ] `/catalog` показывает корректный дизайн ✅
- [ ] `/catalog/` редиректит и показывает тот же дизайн ✅
- [ ] CSS загружается корректно ✅
- [ ] Нет ошибок в Console ✅

### SEO
- [ ] 301 редирект (постоянный, не 302) ✅
- [ ] Location header корректный ✅
- [ ] Query параметры сохраняются ✅

---

## Troubleshooting

### Проблема: Редирект не работает

**Проверьте .htaccess:**
```bash
cat web/.htaccess | grep -A 5 "trailing slash"
```

Должно быть:
```apache
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]
```

**Проверьте mod_rewrite включен:**
```bash
apache2ctl -M | grep rewrite
# Должно быть: rewrite_module (shared)
```

### Проблема: Редирект есть, но не 301

**Проверьте флаги в .htaccess:**
- `R=301` — должен быть указан явно
- `L` — last rule, останавливает обработку

### Проблема: Дизайн все еще разный

**Очистите кэш браузера:**
- Chrome: `Cmd+Shift+R` (hard reload)
- Safari: `Cmd+Option+E` + `Cmd+R`
- Firefox: `Cmd+Shift+R`

**Проверьте версию CSS:**
```html
<!-- Должна быть v=2.2 -->
<link href="/css/catalog-inline.css?v=2.2">
```

### Проблема: 404 ошибка после редиректа

**Проверьте роут в config/web.php:**
```php
'catalog' => 'catalog/index',
```

**Проверьте права на файлы:**
```bash
ls -la web/.htaccess
# Должно быть: -rw-r--r--
```

---

## Advanced: Логирование редиректов

### Добавить логирование в .htaccess
```apache
RewriteLog "/tmp/rewrite.log"
RewriteLogLevel 3
```

**Посмотреть лог:**
```bash
tail -f /tmp/rewrite.log
```

### PHP логирование (в контроллере)
```php
Yii::info("Redirect from trailing slash: {$url} -> {$cleanUrl}", 'seo');
```

**Посмотреть лог:**
```bash
tail -f runtime/logs/app.log | grep seo
```

---

## SEO валидаторы

### Google Search Console
1. Добавьте оба URL:
   - `http://localhost:8080/catalog`
   - `http://localhost:8080/catalog/`
2. Проверьте, что второй редиректится

### Screaming Frog
1. Crawl сайт
2. Фильтр: Status Code = 301
3. Должны увидеть редиректы с trailing slash

### Redirect Checker
Онлайн инструменты:
- https://httpstatus.io/
- https://www.redirect-checker.org/

Введите: `http://localhost:8080/catalog/`  
Ожидаемое: 301 → `/catalog`

---

## Метрики успеха

### Performance
- ✅ Редирект выполняется за < 10ms
- ✅ Только один редирект (не цепочка)
- ✅ Нет лишних запросов

### SEO
- ✅ 301 код (не 302)
- ✅ Canonical без trailing slash
- ✅ Нет дублирования контента

### UX
- ✅ Мгновенный редирект
- ✅ Прозрачно для пользователя
- ✅ Сохранение query параметров

---

**Создано:** 2025-11-09  
**Версия:** 1.0  
**Статус:** Production Ready
