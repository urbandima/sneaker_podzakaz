# 🚀 Инструкция по развертыванию на сервере vh124.hoster.by

**Дата:** 28 октября 2025  
**Сервер:** vh124.hoster.by  
**Домен:** zakaz.sneaker-head.by

---

## 📋 Данные сервера

```
SSH Host:     vh124.hoster.by
Username:     sneakerh
Password:     (2LsY_tc5E
Port:         22
Path:         /home/sneakerh/zakaz.sneaker-head.by
IP:           93.125.99.7
Test URL:     http://sneakerh.vh124.hosterby.com/
```

---

## 🔐 Шаг 1: Добавить секреты в GitHub (3 минуты)

### 1.1 Перейти в настройки репозитория

https://github.com/urbandima/sneaker_podzakaz/settings/secrets/actions

### 1.2 Нажать "New repository secret"

### 1.3 Добавить 4 секрета:

#### Секрет 1: SSH_HOST
```
Name:  SSH_HOST
Value: vh124.hoster.by
```

#### Секрет 2: SSH_USERNAME
```
Name:  SSH_USERNAME
Value: sneakerh
```

#### Секрет 3: SSH_PASSWORD
```
Name:  SSH_PASSWORD
Value: (2LsY_tc5E
```

#### Секрет 4: SSH_PATH
```
Name:  SSH_PATH
Value: /home/sneakerh/zakaz.sneaker-head.by
```

---

## 🖥️ Шаг 2: Первоначальная настройка сервера (15 минут)

### 2.1 Подключиться к серверу

```bash
ssh sneakerh@vh124.hoster.by
# Введите пароль: (2LsY_tc5E
```

### 2.2 Перейти в директорию сайта

```bash
cd /home/sneakerh/zakaz.sneaker-head.by
pwd
# Должно вывести: /home/sneakerh/zakaz.sneaker-head.by
```

### 2.3 Очистить директорию (если есть старые файлы)

```bash
# ОСТОРОЖНО! Удалит все файлы в директории
rm -rf *
rm -rf .htaccess
```

### 2.4 Клонировать проект из GitHub

```bash
git clone https://github.com/urbandima/sneaker_podzakaz.git .
```

### 2.5 Проверить, что файлы скопированы

```bash
ls -la
# Должны увидеть: controllers/, models/, views/, web/, config/ и т.д.
```

---

## 📦 Шаг 3: Установить зависимости (5 минут)

### 3.1 Проверить наличие Composer

```bash
composer --version
```

Если Composer не установлен:

```bash
cd ~
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 3.2 Установить зависимости проекта

```bash
cd /home/sneakerh/zakaz.sneaker-head.by
composer install --no-dev --optimize-autoloader
```

Это займет 2-3 минуты.

---

## 🗄️ Шаг 4: Настроить базу данных (10 минут)

### 4.1 Создать базу данных

Через панель управления хостингом (обычно ISPmanager или cPanel):

1. Перейти в "Базы данных" → "Создать базу данных"
2. Имя БД: `sneakerh_zakaz`
3. Пользователь: `sneakerh_zakaz`
4. Пароль: `сгенерировать_сильный_пароль`
5. Права: ВСЕ

### 4.2 Настроить подключение к БД

```bash
cd /home/sneakerh/zakaz.sneaker-head.by
nano config/db.php
```

Изменить на:

```php
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=sneakerh_zakaz',
    'username' => 'sneakerh_zakaz',
    'password' => 'ваш_пароль_от_бд',
    'charset' => 'utf8',

    // Дополнительные настройки для продакшена
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
```

Сохранить: `Ctrl+O`, `Enter`, выйти: `Ctrl+X`

### 4.3 Применить миграции

```bash
cd /home/sneakerh/zakaz.sneaker-head.by
php yii migrate --interactive=0
```

Должно вывести:
```
*** applying m241023_181500_create_users_table
    > create table users ... done (time: 0.123s)
...
6 migrations applied successfully.
```

---

## 🔧 Шаг 5: Настроить права доступа (2 минуты)

```bash
cd /home/sneakerh/zakaz.sneaker-head.by

# Права на директории для записи
chmod -R 777 runtime
chmod -R 777 web/assets
chmod -R 777 web/uploads

# Проверить права
ls -la runtime
ls -la web/assets
ls -la web/uploads
```

---

## 🌐 Шаг 6: Настроить веб-сервер (5 минут)

### 6.1 Проверить, что DocumentRoot указывает на /web

В ISPmanager или панели управления хостингом:

1. Перейти в "Веб-сайты" → "zakaz.sneaker-head.by"
2. Найти "Корневая папка" или "DocumentRoot"
3. Должно быть: `/home/sneakerh/zakaz.sneaker-head.by/web`

Если нет, изменить на `/home/sneakerh/zakaz.sneaker-head.by/web`

### 6.2 Проверить .htaccess

```bash
cat /home/sneakerh/zakaz.sneaker-head.by/web/.htaccess
```

Должно быть:

```apache
RewriteEngine on
# If a directory or a file exists, use it directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
# Otherwise forward it to index.php
RewriteRule . index.php
```

### 6.3 Перезапустить веб-сервер (если есть права)

```bash
sudo systemctl restart apache2
# или
sudo service apache2 restart
```

---

## 📧 Шаг 7: Настроить Email (5 минут)

### 7.1 Открыть конфигурацию

```bash
nano /home/sneakerh/zakaz.sneaker-head.by/config/web.php
```

### 7.2 Найти секцию 'mailer' и настроить SMTP

```php
'mailer' => [
    'class' => 'yii\swiftmailer\Mailer',
    'useFileTransport' => false, // Отключить для production
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => 'smtp.gmail.com',  // Или SMTP вашего хостинга
        'username' => 'sneakerkultura@gmail.com',
        'password' => 'ваш_пароль_приложения',
        'port' => '587',
        'encryption' => 'tls',
    ],
],
```

Сохранить: `Ctrl+O`, `Enter`, выйти: `Ctrl+X`

---

## 🏢 Шаг 8: Настроить компанию в админке (3 минуты)

### 8.1 Открыть админку

```
http://zakaz.sneaker-head.by/admin
```

Логин: `admin` (создается при миграциях)  
Пароль: нужно будет создать или восстановить

### 8.2 Перейти в "Настройки компании"

Заполнить:
- Название компании: СНИКЕРХЭД
- УНП: ваш УНП
- Адрес: ваш адрес
- Телефон: +375 44 700-90-01
- Email: sneakerkultura@gmail.com
- Банк: ваш банк
- БИК: ваш БИК
- Расчетный счет: ваш счет

---

## ✅ Шаг 9: Проверить работу (5 минут)

### 9.1 Проверить главную страницу

```
http://zakaz.sneaker-head.by/
```

Должен открыться лендинг с "Закажем любую пару оригинальной обуви для вас"

### 9.2 Проверить админку

```
http://zakaz.sneaker-head.by/admin
```

Должна открыться админ-панель

### 9.3 Создать тестовый заказ

1. В админке нажать "Создать заказ"
2. Заполнить данные
3. Сохранить
4. Проверить, что пришел email

### 9.4 Открыть ссылку клиента

Скопировать публичную ссылку из заказа, например:
```
http://zakaz.sneaker-head.by/order/view?token=abc123
```

Должна открыться страница с реквизитами

---

## 🔄 Шаг 10: Проверить автодеплой (3 минуты)

### 10.1 Сделать тестовое изменение

На локальном компьютере:

```bash
cd /Users/user/CascadeProjects/splitwise

# Изменить что-нибудь, например, добавить комментарий
echo "// Test deployment" >> views/site/index.php

git add .
git commit -m "Test: проверка автодеплоя"
git push origin main
```

### 10.2 Проверить GitHub Actions

1. Открыть: https://github.com/urbandima/sneaker_podzakaz/actions
2. Должен появиться новый workflow "Deploy to Hosting"
3. Дождаться завершения (зеленая галочка)

### 10.3 Проверить на сервере

```bash
ssh sneakerh@vh124.hoster.by
cd /home/sneakerh/zakaz.sneaker-head.by
git log --oneline -1
# Должен показать последний коммит
```

---

## 🎯 Готово!

### Что работает:

- ✅ Сайт доступен по адресу zakaz.sneaker-head.by
- ✅ Главная страница с лендингом
- ✅ Админ-панель /admin
- ✅ Создание заказов
- ✅ Страница клиента с реквизитами
- ✅ Email-уведомления
- ✅ Автоматический деплой через GitHub

### Рабочий процесс:

```bash
# 1. Вносите изменения локально
# 2. Коммитите
git add .
git commit -m "Описание изменений"

# 3. Отправляете на GitHub
git push

# 4. GitHub автоматически обновляет сервер! 🚀
```

---

## 🆘 Решение проблем

### Ошибка: "Permission denied"

```bash
chmod -R 777 runtime web/assets web/uploads
```

### Ошибка: "Database connection failed"

Проверить config/db.php:
```bash
nano config/db.php
```

### Ошибка: "500 Internal Server Error"

Проверить логи:
```bash
tail -f /home/sneakerh/public_html/runtime/logs/app.log
```

### Сайт не открывается

Проверить DocumentRoot:
```bash
# Должно быть: /home/sneakerh/public_html/web
```

### Email не отправляется

Проверить SMTP настройки в config/web.php

---

## 📞 Контакты поддержки

**Хостинг:** vh124.hoster.by  
**Email:** sneakerkultura@gmail.com  
**Telegram:** @sneakerheadbyweb_bot

---

## 📝 Важные команды

```bash
# Подключиться к серверу
ssh sneakerh@vh124.hoster.by

# Обновить код с GitHub
cd /home/sneakerh/zakaz.sneaker-head.by && git pull origin main

# Установить зависимости
composer install --no-dev

# Применить миграции
php yii migrate

# Очистить кеш
rm -rf runtime/cache/*

# Посмотреть логи
tail -f runtime/logs/app.log

# Проверить права
ls -la runtime web/assets web/uploads

# Перезапустить PHP-FPM (если есть права)
sudo systemctl restart php-fpm
```

---

## 🎉 Статус

**Сервер:** vh124.hoster.by  
**Домен:** zakaz.sneaker-head.by  
**Статус:** ✅ Готов к развертыванию  
**Дата:** 28 октября 2025

Следуйте инструкциям выше шаг за шагом!
