# 🚀 Инструкция по выгрузке на хостинг

## 📋 Что изменено и готово к деплою

### ✅ Выполненные доработки:
1. **Кнопка "Инструкция"** добавлена рядом с "Реквизиты для оплаты"
2. **Ссылка на политику конфиденциальности** https://sneaker-head.by/policy
3. **База данных переключена на PostgreSQL**
4. **Код очищен** от тестовых файлов и временных данных
5. **10 банков Беларуси** с логотипами и инструкциями

---

## 🗄️ Настройка PostgreSQL базы данных

### На хостинге создать БД:

1. **Зайти в панель управления хостинга** (cPanel, ISPmanager и т.д.)
2. **Создать PostgreSQL базу данных:**
   - Имя БД: `order_management` (или любое другое)
   - Пользователь: создать нового
   - Пароль: сгенерировать надежный

3. **Записать параметры:**
   ```
   Host: localhost (или IP хостинга)
   Port: 5432
   Database: order_management
   Username: ваш_пользователь
   Password: ваш_пароль
   ```

---

## ⚙️ Настройка перед загрузкой

### 1. config/db.php

Открыть файл и указать данные БД:

```php
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'pgsql:host=ВАШИ_ДАННЫЕ_ХОСТИНГА;port=5432;dbname=order_management',
    'username' => 'ВАШ_ПОЛЬЗОВАТЕЛЬ_БД',
    'password' => 'ВАШ_ПАРОЛЬ_БД',
    'charset' => 'utf8',
];
```

**Пример для популярных хостингов:**

**Beget:**
```php
'dsn' => 'pgsql:host=localhost;port=5432;dbname=ваш_логин_order',
'username' => 'ваш_логин_order',
'password' => 'пароль',
```

**Timeweb:**
```php
'dsn' => 'pgsql:host=localhost;dbname=ваша_база',
'username' => 'пользователь',
'password' => 'пароль',
```

### 2. config/web.php

Изменить `cookieValidationKey`:

```php
'cookieValidationKey' => 'СГЕНЕРИРОВАТЬ_НОВЫЙ_КЛЮЧ_32_СИМВОЛА',
```

Сгенерировать ключ:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 3. web/index.php

Файл уже настроен для автоопределения окружения. Для принудительного production:

```php
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'prod');
```

---

## 📦 Загрузка файлов на хостинг

### Способ 1: FTP/SFTP (FileZilla, WinSCP)

1. **Подключиться к хостингу** через FTP клиент
2. **Загрузить ВСЕ файлы** в корневую папку сайта
3. **Важно:** Корень сайта должен указывать на папку `/web`

### Способ 2: Через панель хостинга

1. **Создать архив локально:**
   ```bash
   zip -r site.zip . -x "*.git*" "*.idea*" ".DS_Store" "vendor/*"
   ```

2. **Загрузить** через файловый менеджер хостинга
3. **Распаковать** на сервере

### Структура на хостинге:

```
/home/username/public_html/  (или www/)
├── config/
├── controllers/
├── models/
├── views/
├── web/              ← КОРЕНЬ САЙТА (DocumentRoot)
│   ├── index.php
│   ├── .htaccess
│   ├── uploads/
│   └── assets/
├── runtime/
├── migrations/
├── components/
└── vendor/
```

---

## 🔧 Настройка на хостинге

### 1. Установить зависимости

Подключиться по SSH и выполнить:

```bash
cd /home/username/public_html
composer install --no-dev --optimize-autoloader
```

**Если composer не установлен:**
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php composer.phar install --no-dev --optimize-autoloader
```

### 2. Создать необходимые папки

```bash
mkdir -p runtime/logs
mkdir -p web/uploads/payments
mkdir -p web/assets

chmod 755 runtime
chmod 755 web/assets
chmod 755 web/uploads
chmod 755 web/uploads/payments
```

### 3. Настроить DocumentRoot

**В панели хостинга** указать корень сайта на папку `/web`

**Для cPanel:**
- Domains → Manage → Document Root → `/public_html/web`

**Для ISPmanager:**
- WWW домены → Изменить → Папка → `/www/имя_сайта/web`

**Для Nginx вручную:**
```nginx
server {
    server_name your-domain.com;
    root /home/username/public_html/web;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$args;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### 4. Запустить миграции

```bash
cd /home/username/public_html
php yii migrate
```

Ответить `yes` на все вопросы. Это создаст все таблицы в PostgreSQL.

### 5. Создать администратора

**Вариант 1: SQL запрос**

Подключиться к PostgreSQL через phpPgAdmin или psql:

```sql
-- Сгенерировать хеш пароля (выполнить в PHP)
-- php -r "echo password_hash('ваш_пароль', PASSWORD_DEFAULT);"

INSERT INTO "user" (username, password_hash, auth_key, role, status, created_at, updated_at)
VALUES (
    'admin',
    '$2y$13$ХЕSH_ПАРОЛЯ',
    'случайная_строка_32_символа',
    'admin',
    10,
    EXTRACT(EPOCH FROM NOW()),
    EXTRACT(EPOCH FROM NOW())
);
```

**Вариант 2: Консольная команда (если создана)**

```bash
php yii create-admin
```

---

## 🔐 Финальная проверка безопасности

### 1. Проверить что изменено:

- [x] `cookieValidationKey` изменен
- [x] База данных PostgreSQL настроена
- [x] `YII_DEBUG = false` (или автоопределение)
- [x] Права на папки установлены

### 2. Проверить .htaccess

Файл `web/.htaccess` должен содержать:

```apache
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php

# Security headers (уже добавлено)
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

Options -Indexes

<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. Настроить лимиты PHP

В `php.ini` или `.user.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 300
```

---

## ✅ Проверка после деплоя

### 1. Открыть сайт
```
https://your-domain.com
```

### 2. Проверить админку
```
https://your-domain.com/login
```

### 3. Создать тестовый заказ
- Войти в админку
- Создать заказ
- Проверить что токен работает

### 4. Проверить загрузку чека
- Открыть публичную ссылку заказа
- Загрузить чек (до 10МБ)
- Проверить что файл сохранился в `web/uploads/payments/`

### 5. Проверить popup с банками
- Нажать кнопку "Инструкция" у реквизитов
- Убедиться что открывается popup с 10 банками
- Проверить что логотипы загружаются

### 6. Проверить политику конфиденциальности
- При загрузке чека должна быть ссылка на https://sneaker-head.by/policy

---

## 🆘 Решение проблем

### Проблема: Белый экран

**Решение:**
1. Проверить логи PHP (обычно в папке `logs/` хостинга)
2. Временно включить debug:
   ```php
   define('YII_DEBUG', true);
   ```
3. Проверить права на `runtime/` и `web/assets/`

### Проблема: 500 Internal Server Error

**Решение:**
1. Проверить `.htaccess` существует в `/web`
2. Убедиться что mod_rewrite включен
3. Проверить логи веб-сервера

### Проблема: Не работает БД

**Решение:**
1. Проверить данные подключения в `config/db.php`
2. Убедиться что PostgreSQL расширение PHP установлено:
   ```bash
   php -m | grep pgsql
   ```
3. Проверить что миграции выполнены:
   ```bash
   php yii migrate/history
   ```

### Проблема: Не загружаются файлы

**Решение:**
1. Проверить права:
   ```bash
   chmod 755 web/uploads
   chmod 755 web/uploads/payments
   ```
2. Создать папку если не существует:
   ```bash
   mkdir -p web/uploads/payments
   ```
3. Проверить лимиты PHP

### Проблема: 404 на все страницы

**Решение:**
1. Проверить что DocumentRoot указывает на `/web`
2. Проверить `.htaccess` в `/web`
3. Убедиться что mod_rewrite работает

---

## 📊 Список банков в системе

**10 банков с логотипами и инструкциями:**

1. Беларусбанк
2. Приорбанк
3. Альфа-Банк
4. БелВЭБ
5. МТБанк
6. БПС-Сбербанк
7. Белгазпромбанк
8. Дабрабыт
9. Технобанк
10. Идея Банк

---

## 🔄 Обновление системы

### Для обновления на хостинге:

1. **Сделать backup:**
   ```bash
   pg_dump order_management > backup.sql
   tar -czf backup-uploads.tar.gz web/uploads/
   ```

2. **Загрузить новые файлы**

3. **Запустить миграции:**
   ```bash
   php yii migrate
   ```

4. **Очистить cache:**
   ```bash
   rm -rf runtime/cache/*
   ```

---

## 📞 Техническая информация

### Требования к хостингу:

- **PHP:** >= 8.0
- **PostgreSQL:** >= 12.0
- **Расширения PHP:**
  - pdo_pgsql
  - mbstring
  - intl
  - fileinfo
  - openssl

### Рекомендуемые хостинги для РБ:

1. **Beget** - поддержка PHP 8.x и PostgreSQL
2. **Timeweb** - полная поддержка Yii2
3. **HostingByBY** - белорусский хостинг
4. **RuCloud** - VPS с полным доступом

---

## ✅ Финальный чеклист

Перед объявлением сайта готовым:

- [ ] База данных PostgreSQL создана и настроена
- [ ] config/db.php содержит правильные данные
- [ ] cookieValidationKey изменен на уникальный
- [ ] composer install выполнен
- [ ] Папки созданы с правильными правами
- [ ] Миграции запущены (php yii migrate)
- [ ] Администратор создан
- [ ] DocumentRoot указывает на /web
- [ ] Сайт открывается без ошибок
- [ ] Админка работает (вход/выход)
- [ ] Создание заказа работает
- [ ] Публичная ссылка заказа работает
- [ ] Загрузка чека работает (до 10МБ)
- [ ] Popup с банками открывается
- [ ] Ссылка на политику работает (https://sneaker-head.by/policy)
- [ ] Все 10 банков отображаются с логотипами
- [ ] Mobile версия работает корректно

---

**Система готова к production!** 🚀

При возникновении проблем обращайтесь к разделу "Решение проблем" или в техподдержку хостинга.
