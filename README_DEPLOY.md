# 🚀 Быстрый старт для Production

## ✅ Что ГОТОВО к выгрузке:

### Функционал
- ✅ Система управления заказами
- ✅ Админ-панель с ролями (admin, manager, logist)
- ✅ Публичный просмотр заказов по токену
- ✅ Загрузка чеков оплаты (до 10МБ)
- ✅ Popup с инструкциями для 10 банков Беларуси
- ✅ Смена пароля для пользователей
- ✅ Статистика и фильтрация заказов
- ✅ Активные/неактивные статусы
- ✅ История изменений заказа

### Безопасность
- ✅ CSRF защита
- ✅ XSS защита (Html::encode)
- ✅ SQL Injection защита (prepared statements)
- ✅ Валидация загрузки файлов (MIME, magic bytes, size)
- ✅ Rate limiting на загрузку
- ✅ Безопасные заголовки в .htaccess
- ✅ Защита скрытых файлов

### UI/UX
- ✅ Mobile-first верстка
- ✅ Адаптивный дизайн
- ✅ Копирование без alert браузера
- ✅ Визуальная обратная связь
- ✅ Логотипы банков (favicon)

---

## ⚠️ ЧТО НУЖНО СДЕЛАТЬ ПЕРЕД ВЫГРУЗКОЙ:

### 1. Изменить cookieValidationKey (КРИТИЧНО!)

Открыть `config/web.php` и заменить:

```php
'cookieValidationKey' => 'your-secret-key-here-change-in-production',
```

Сгенерировать ключ:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

### 2. Проверить систему

```bash
php check-system.php
```

Этот скрипт проверит:
- PHP версию и расширения
- Права доступа к папкам
- Конфигурацию
- Базу данных
- Безопасность

### 3. Подготовить файлы

```bash
# Установить зависимости для production
composer install --no-dev --optimize-autoloader

# Создать архив (опционально)
zip -r splitwise-production.zip . -x "*.git*" "*.idea*" ".DS_Store"
```

---

## 📦 На хостинге выполнить:

### 1. Загрузить файлы

Загрузить все файлы на хостинг через FTP/SFTP.

**Важно:** Корень сайта должен указывать на папку `/web`

### 2. Установить зависимости

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Создать необходимые папки

```bash
mkdir -p database
mkdir -p runtime/logs
mkdir -p web/uploads/payments

chmod 755 runtime
chmod 755 web/assets
chmod 755 web/uploads
chmod 755 database
```

### 4. Запустить миграции

```bash
php yii migrate
```

Это создаст все таблицы и применит все изменения БД.

### 5. Создать администратора

**Вариант 1:** Через консольную команду (если есть)
```bash
php yii create-admin
```

**Вариант 2:** SQL запрос напрямую в БД

```sql
INSERT INTO user (username, password_hash, auth_key, role, status, created_at, updated_at)
VALUES (
    'admin', 
    '$2y$13$hash_вашего_пароля', 
    'случайная_строка_32_символа', 
    'admin', 
    10, 
    strftime('%s', 'now'), 
    strftime('%s', 'now')
);
```

Сгенерировать хеш пароля:
```bash
php -r "echo password_hash('ваш_пароль', PASSWORD_DEFAULT);"
```

### 6. Проверить работу

1. Открыть сайт: `https://your-domain.com`
2. Войти в админку: `https://your-domain.com/login`
3. Создать тестовый заказ
4. Проверить загрузку чека
5. Проверить popup с банками

---

## 📁 Структура проекта

```
/
├── config/          # Конфигурация
├── controllers/     # Контроллеры
├── models/          # Модели данных
├── views/           # Представления
│   ├── admin/      # Админ-панель
│   ├── order/      # Публичные страницы заказов
│   └── site/       # Главная, логин, инструкции
├── web/             # ПУБЛИЧНАЯ ПАПКА (корень сайта)
│   ├── uploads/    # Загруженные файлы
│   ├── assets/     # CSS/JS (генерируется)
│   └── .htaccess   # Настройки Apache
├── runtime/         # Временные файлы и логи
├── database/        # БД SQLite
├── migrations/      # Миграции БД
└── components/      # Компоненты приложения
```

---

## 🏦 Банки в системе (10 штук)

1. **Беларусбанк** — belarusbank.by
2. **Приорбанк** — priorbank.by
3. **Альфа-Банк** — alfabank.by
4. **БелВЭБ** — belveb.by
5. **МТБанк** — mtbank.by
6. **БПС-Сбербанк** — bps-sberbank.by
7. **Белгазпромбанк** — bgpb.by
8. **Дабрабыт** — dabrabyt.by
9. **Технобанк** — technobank.by
10. **Идея Банк** — ideabank.by

Все банки имеют:
- Логотипы (загружаются с сайтов банков)
- Пошаговые инструкции оплаты
- Адаптивную верстку

---

## 🔧 Настройка веб-сервера

### Apache (рекомендуется)

`.htaccess` уже настроен. Убедитесь что:
- mod_rewrite включен
- AllowOverride All
- DocumentRoot указывает на `/web`

### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/splitwise/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\.(ht|git|svn) {
        deny all;
    }
}
```

---

## ⚙️ Настройки PHP

Минимальные требования в `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
max_execution_time = 300
```

Обязательные расширения:
- PHP >= 8.0
- pdo_sqlite
- mbstring
- fileinfo
- openssl

---

## 🔐 Пользователи и роли

### Роли в системе:

1. **admin** — полный доступ
   - Создание пользователей
   - Удаление пользователей
   - Управление настройками
   - Управление статусами
   - Все функции менеджера

2. **manager** — менеджер
   - Создание заказов
   - Редактирование заказов
   - Просмотр статистики
   - Назначение логистов

3. **logist** — логист
   - Просмотр своих заказов
   - Изменение статуса своих заказов
   - Ограниченный доступ

---

## 📊 Мониторинг и обслуживание

### Логи
- **Приложение:** `/runtime/logs/app.log`
- **Веб-сервер:** зависит от конфигурации
- **PHP ошибки:** зависит от конфигурации

### Резервное копирование
Регулярно делать backup:
- `database/db.sqlite` — база данных
- `web/uploads/` — загруженные файлы
- `config/` — конфигурация

```bash
# Пример backup скрипта
tar -czf backup-$(date +%Y%m%d).tar.gz database/ web/uploads/ config/
```

---

## 🆘 Решение проблем

### Белый экран
1. Проверить права на `runtime/` и `web/assets/`
2. Посмотреть логи: `runtime/logs/app.log`
3. Временно включить отображение ошибок

### 404 на всех страницах
1. Проверить mod_rewrite (Apache)
2. Проверить `.htaccess` работает
3. Проверить DocumentRoot

### Не загружаются файлы
1. Проверить права: `chmod 755 web/uploads`
2. Проверить лимиты PHP
3. Создать подпапки: `mkdir web/uploads/payments`

---

## 📞 Дополнительная информация

- **Полная инструкция:** `PRODUCTION_DEPLOY.md`
- **Чеклист:** `PRODUCTION_CHECKLIST.txt`
- **Проверка системы:** `php check-system.php`

---

## ✅ Финальный чеклист

- [ ] Изменен cookieValidationKey
- [ ] Запущен check-system.php
- [ ] Файлы загружены на хостинг
- [ ] Установлены зависимости (composer install)
- [ ] Созданы папки с правами
- [ ] Запущены миграции (php yii migrate)
- [ ] Создан администратор
- [ ] Сайт открывается
- [ ] Вход в админку работает
- [ ] Создание заказа работает
- [ ] Загрузка чека работает
- [ ] Popup с банками работает

---

**Система полностью готова к production!** 🚀

При возникновении вопросов обращайтесь к полной документации в `PRODUCTION_DEPLOY.md`
