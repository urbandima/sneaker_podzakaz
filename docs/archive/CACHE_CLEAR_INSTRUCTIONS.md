# Инструкция по очистке кэша браузера

**Проблема:** `/catalog/` и `/catalog` показывают разный дизайн  
**Причина:** Браузер кэширует старую версию страницы  
**Решение:** Очистить кэш браузера

---

## Быстрое решение (Hard Reload)

### Chrome / Edge
**Способ 1 (быстрый):**
```
Cmd + Shift + R   (Mac)
Ctrl + Shift + R  (Windows/Linux)
```

**Способ 2:**
1. Откройте DevTools (`F12` или `Cmd+Option+I`)
2. **Правой кнопкой** на иконку обновления (⟳)
3. Выберите: **"Empty Cache and Hard Reload"**

### Safari
**Hard Reload:**
```
1. Cmd + Option + E  (очистить кэш)
2. Cmd + R            (обновить страницу)
```

Или:
1. Safari → Settings → Advanced
2. ✅ Включите "Show Develop menu"
3. Develop → Empty Caches
4. `Cmd + R`

### Firefox
**Hard Reload:**
```
Cmd + Shift + R   (Mac)
Ctrl + Shift + R  (Windows/Linux)
```

Или:
1. Откройте `about:preferences#privacy`
2. Cookies and Site Data → **Clear Data**
3. ✅ Cached Web Content
4. Clear

---

## Пошаговая инструкция

### Шаг 1: Откройте каталог
```
http://localhost:8080/catalog
```

### Шаг 2: Откройте DevTools
- **Chrome/Edge/Firefox:** `F12`
- **Safari:** `Cmd+Option+I`

### Шаг 3: Откройте Network Tab
1. Перейдите на вкладку **Network**
2. ✅ Включите **"Disable cache"**
3. ✅ Включите **"Preserve log"**

### Шаг 4: Hard Reload
```
Cmd + Shift + R  (Mac)
Ctrl + Shift + R (Windows)
```

### Шаг 5: Проверьте версию CSS
В Network Tab найдите:
```
catalog-inline.css?v=2.4  ← должна быть версия 2.4
catalog-card.css?v=2.2     ← должна быть версия 2.2
```

**Если версия старая (v=2.1, v=2.2, v=2.3):**
- Повторите Hard Reload
- Или очистите кэш полностью (см. ниже)

---

## Полная очистка кэша

### Chrome / Edge

**Вариант 1 (быстрый):**
1. `Cmd+Shift+Delete` (Mac) или `Ctrl+Shift+Delete` (Windows)
2. Time range: **All time**
3. ✅ Cached images and files
4. **Clear data**

**Вариант 2 (через настройки):**
1. Chrome → Settings → Privacy and Security
2. **Clear browsing data**
3. Advanced → Time range: **All time**
4. ✅ Cached images and files
5. ✅ Cookies and other site data (опционально)
6. **Clear data**

### Safari

1. Safari → Settings → Privacy
2. **Manage Website Data...**
3. **Remove All**
4. Подтвердите

Или:
1. Safari → Develop → **Empty Caches**
2. `Cmd + R`

### Firefox

1. `Cmd+Shift+Delete` (Mac) или `Ctrl+Shift+Delete` (Windows)
2. Time range to clear: **Everything**
3. ✅ Cache
4. ✅ Cookies (опционально)
5. **Clear Now**

---

## Проверка результата

### 1. Откройте оба URL

**URL 1:**
```
http://localhost:8080/catalog
```
Должен открыться напрямую (200 OK)

**URL 2:**
```
http://localhost:8080/catalog/
```
Должен редиректиться на `/catalog` (301)

### 2. Проверьте редирект в Network Tab

При переходе на `/catalog/` должно быть:

```
Name           Status  Type      
catalog/       301     redirect  ← редирект
catalog        200     document  ← финальная страница
```

### 3. Проверьте версии CSS

В обоих случаях должны загружаться:
```
catalog-inline.css?v=2.4
catalog-card.css?v=2.2
```

### 4. Проверьте дизайн

**Должно быть одинаково:**
- ✅ Контейнер на всю ширину (100%)
- ✅ Товары в grid (4-6 колонок в зависимости от ширины)
- ✅ Компактные отступы
- ✅ Размеры в одну строку на desktop

---

## Если проблема осталась

### Проверка 1: Версия CSS в HTML

**Откройте страницу и View Source:**
```
Cmd + U  (Mac)
Ctrl + U (Windows)
```

**Найдите строки:**
```html
<link href="/css/catalog-inline.css?v=2.4" rel="stylesheet">
<link href="/css/catalog-card.css?v=2.2" rel="stylesheet">
```

**Если версия старая** → перезапустите PHP сервер:
```bash
# Остановите сервер (Ctrl+C)
# Запустите заново
php -S localhost:8080 -t web/
```

### Проверка 2: Редирект работает

**В Terminal:**
```bash
curl -I http://localhost:8080/catalog/
```

**Должно быть:**
```
HTTP/1.1 301 Moved Permanently
Location: http://localhost:8080/catalog
```

**Если нет редиректа:**
1. Проверьте `web/.htaccess` (должно быть правило на строке 7-9)
2. Перезапустите сервер

### Проверка 3: .htaccess работает

**PHP dev server НЕ использует .htaccess!**

Редирект работает через **PHP контроллер** в этом случае.

**Проверьте** `controllers/CatalogController.php`:
```php
// Должно быть в actionIndex():
if ($url !== '/' && !empty($pathInfo) && substr($pathInfo, -1) === '/') {
    return $this->redirect($cleanUrl, 301);
}
```

---

## Автоматическая очистка (для разработки)

### Chrome DevTools

**Постоянно отключить кэш:**
1. Откройте DevTools (`F12`)
2. Network Tab
3. ✅ **Disable cache**
4. Оставьте DevTools открытыми

**Теперь при открытых DevTools кэш не используется!**

### Safari

1. Safari → Develop
2. ✅ **Disable Caches**

### Firefox

1. `F12` → Settings (шестеренка)
2. Advanced Settings
3. ✅ **Disable HTTP Cache (when toolbox is open)**

---

## Почему это происходит?

### Кэширование браузера

Браузер кэширует:
1. **HTML страницу** (`/catalog`, `/catalog/`)
2. **CSS файлы** (`catalog-inline.css`)
3. **JavaScript файлы**
4. **Изображения**

### Агрессивное кэширование

Настройки в `.htaccess`:
```apache
# CSS и JavaScript - 1 месяц
ExpiresByType text/css "access plus 1 month"
```

**Решение:**
- Версионирование CSS: `catalog-inline.css?v=2.4`
- При изменении версии браузер загружает новый файл

### Почему `/catalog/` и `/catalog` могут кэшироваться отдельно?

Браузер воспринимает их как **разные URL**:
- `/catalog` → кэш A
- `/catalog/` → кэш B (старый)

**После редиректа:**
- `/catalog/` → 301 → `/catalog` → кэш A (новый)

Но старый кэш B может оставаться!

---

## Профилактика

### 1. Используйте версионирование

**При каждом изменении CSS:**
```php
// Увеличивайте версию
$this->registerCssFile('@web/css/catalog-inline.css?v=2.5');
```

### 2. Работайте с открытыми DevTools

**Постоянно отключенный кэш:**
- DevTools → Network → ✅ Disable cache

### 3. Используйте Incognito/Private режим

**Для проверки:**
```
Cmd + Shift + N  (Chrome)
Cmd + Shift + P  (Safari)
Cmd + Shift + P  (Firefox)
```

Приватный режим не использует старый кэш!

---

## Итоговый чек-лист

- [ ] **Hard Reload** (`Cmd+Shift+R`)
- [ ] **Проверить версию CSS** (должна быть v=2.4)
- [ ] **Проверить редирект** (`/catalog/` → 301 → `/catalog`)
- [ ] **Полная очистка кэша** (если Hard Reload не помог)
- [ ] **Перезапустить сервер** (если версия CSS не обновилась)
- [ ] **Disable cache в DevTools** (для дальнейшей работы)

---

## Быстрая команда для Terminal

**Проверить все сразу:**
```bash
echo "=== Проверка редиректа ==="
curl -I http://localhost:8080/catalog/

echo -e "\n=== Версия CSS на /catalog ==="
curl -s http://localhost:8080/catalog | grep -o 'catalog-inline.css?v=[0-9.]*'

echo -e "\n=== Версия CSS на /catalog/ (после редиректа) ==="
curl -sL http://localhost:8080/catalog/ | grep -o 'catalog-inline.css?v=[0-9.]*'
```

**Должно быть:**
```
301 Moved Permanently
Location: http://localhost:8080/catalog

catalog-inline.css?v=2.4
catalog-inline.css?v=2.4
```

---

**Создано:** 2025-11-09  
**Текущая версия CSS:** v=2.4  
**Статус:** Ready to use
