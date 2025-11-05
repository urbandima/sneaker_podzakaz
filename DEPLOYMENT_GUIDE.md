# 🚀 Полное руководство по переносу на GitHub и Production хостинг

**Дата:** 05.11.2025  
**Проект:** Система управления заказами кроссовок  
**Стэк:** Yii2, PHP 7.4+, MySQL 5.7+

---

## 📋 Содержание

1. [Подготовка к деплою](#подготовка-к-деплою)
2. [Перенос на GitHub](#перенос-на-github)
3. [Настройка Production сервера](#настройка-production-сервера)
4. [Первый деплой](#первый-деплой)
5. [Обновления после изменений](#обновления-после-изменений)
6. [Возможные проблемы и решения](#возможные-проблемы-и-решения)

---

## 1. Подготовка к деплою

### ✅ Что уже исправлено (05.11.2025)

- ✅ Создан `.env.example` с шаблоном переменных окружения
- ✅ `cookieValidationKey` перенесён в переменные окружения
- ✅ `useFileTransport` (email) перенесён в переменные окружения
- ✅ `.gitignore` настроен для исключения секретных данных
- ✅ Закомментирован незаконченный код (TODO в CatalogController)
- ✅ Проект готов к production

### 🔴 Критически важно ПЕРЕД загрузкой на GitHub

#### 1.1 Создать .env файл локально

```bash
cd /Users/user/CascadeProjects/splitwise
cp .env.example .env
```

#### 1.2 Заполнить .env для локальной разработки

```bash
nano .env
```

**Минимальная конфигурация:**

```env
# Environment
YII_ENV=dev
YII_DEBUG=true

# Security - СГЕНЕРИРУЙТЕ НОВЫЙ КЛЮЧ!
COOKIE_VALIDATION_KEY=ВАШ_УНИКАЛЬНЫЙ_КЛЮЧ_64_СИМВОЛА

# Database (ваши локальные данные)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=order_management
DB_USERNAME=root
DB_PASSWORD=

# Email (для локальной разработки сохраняем файлы)
MAIL_USE_FILE_TRANSPORT=true
```

**Генерация COOKIE_VALIDATION_KEY:**

```bash
# Выполните одну из команд:
openssl rand -hex 32
# ИЛИ
php -r "echo bin2hex(random_bytes(32));"
```

#### 1.3 Проверить .gitignore

Убедитесь, что `.env` в `.gitignore` (уже добавлено):

```bash
grep "^\.env$" .gitignore
# Должно вывести: .env
```

#### 1.4 Проверить что config/db.php не в Git

```bash
git status config/db.php
# Должно быть: fatal: pathspec 'config/db.php' did not match any files
```

❌ **Если config/db.php в Git - УДАЛИТЕ ЕГО ИЗ РЕПОЗИТОРИЯ:**

```bash
git rm --cached config/db.php
git commit -m "security: удалён config/db.php из репозитория (теперь использует .env)"
```

---

## 2. Перенос на GitHub

### 2.1 Создать приватный репозиторий на GitHub

1. Откройте https://github.com/new
2. **Repository name:** `sneaker-order-system` (или своё имя)
3. **Description:** `Система управления заказами кроссовок на Yii2`
4. **Visibility:** ⚠️ **ОБЯЗАТЕЛЬНО Private** (проект содержит бизнес-логику)
5. **НЕ** инициализируйте с README, .gitignore, license (уже есть локально)
6. Нажмите **Create repository**

### 2.2 Подключить локальный репозиторий к GitHub

#### Если у вас ещё нет Git репозитория локально:

```bash
cd /Users/user/CascadeProjects/splitwise

# Инициализация
git init

# Создать .gitignore если нет (уже должен быть)
# ...

# Добавить все файлы
git add .

# Первый коммит
git commit -m "Initial commit: Yii2 Order Management System"

# Подключить GitHub (замените YOUR_USERNAME и REPO_NAME)
git remote add origin https://github.com/YOUR_USERNAME/sneaker-order-system.git

# Отправить код
git branch -M main
git push -u origin main
```

#### Если репозиторий уже есть:

```bash
cd /Users/user/CascadeProjects/splitwise

# Проверить статус
git status

# Добавить изменения
git add .
git commit -m "fix: подготовка к production деплою (env vars, security)"

# Добавить remote (если ещё не добавлен)
git remote add origin https://github.com/YOUR_USERNAME/sneaker-order-system.git

# ИЛИ обновить существующий
git remote set-url origin https://github.com/YOUR_USERNAME/sneaker-order-system.git

# Отправить
git push -u origin main
```

### 2.3 Проверить что загрузилось

Откройте репозиторий на GitHub и убедитесь:

- ✅ Есть `.env.example` (шаблон)
- ✅ **НЕТ** `.env` (секретные данные)
- ✅ **НЕТ** `config/db.php` (секретные данные)
- ✅ Есть `.gitignore`
- ✅ Есть все остальные файлы (controllers, models, views и т.д.)

---

## 3. Настройка Production сервера

### 3.1 Требования к хостингу

#### Минимальные требования:

- **PHP:** 7.4 или выше (рекомендуется 8.0+)
- **MySQL:** 5.7+ или MariaDB 10.3+
- **Composer:** Версия 2.x
- **SSH доступ:** Обязательно для деплоя через Git
- **Расширения PHP:**
  - `pdo`
  - `pdo_mysql`
  - `mbstring`
  - `fileinfo`
  - `openssl`
  - `zip`
  - `curl`

#### Проверить на сервере:

```bash
# SSH подключение (ваши данные)
ssh username@your-server.com

# Проверить PHP
php -v
# Должно быть: PHP 7.4.x или выше

# Проверить расширения
php -m | grep -E 'pdo|mbstring|fileinfo|openssl'

# Проверить Composer
composer --version
# Если нет - установите: curl -sS https://getcomposer.org/installer | php
```

### 3.2 Создать MySQL базу данных (через cPanel или CLI)

#### Через cPanel:

1. Войдите в cPanel → **MySQL Databases**
2. Создайте базу: `username_sneaker_orders`
3. Создайте пользователя: `username_sneaker_user`
4. Установите пароль (сгенерируйте сложный)
5. Добавьте пользователя к базе с **ALL PRIVILEGES**
6. **Запишите данные:**
   - **Хост:** `localhost` (обычно)
   - **База:** `username_sneaker_orders`
   - **Юзер:** `username_sneaker_user`
   - **Пароль:** (ваш пароль)

#### Через CLI (если есть root доступ):

```bash
mysql -u root -p

CREATE DATABASE sneaker_orders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sneaker_user'@'localhost' IDENTIFIED BY 'СЛОЖНЫЙ_ПАРОЛЬ';
GRANT ALL PRIVILEGES ON sneaker_orders.* TO 'sneaker_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.3 Настроить Document Root

**Важно:** Document Root должен указывать на папку `/web` вашего проекта!

#### Для Apache (.htaccess уже настроен):

Структура на сервере:

```
/home/username/
├── public_html/             ← Document Root (НЕ сюда!)
├── sneaker-orders/          ← Клонируем проект сюда
│   ├── web/                 ← Document Root должен быть ЗДЕСЬ
│   ├── config/
│   ├── controllers/
│   └── ...
```

**Вариант 1: Симлинк (рекомендуется)**

```bash
# Удалить public_html
rm -rf ~/public_html

# Создать симлинк на web
ln -s ~/sneaker-orders/web ~/public_html
```

**Вариант 2: Изменить Document Root в cPanel**

1. cPanel → **Domains** → ваш домен
2. **Document Root:** измените на `/home/username/sneaker-orders/web`
3. Сохраните

#### Для Nginx:

Создайте конфиг (или используйте `nginx.conf.example` из проекта):

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /home/username/sneaker-orders/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        try_files $uri =404;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## 4. Первый деплой

### 4.1 Клонировать репозиторий на сервер

```bash
# SSH подключение
ssh username@your-server.com

# Перейти в домашнюю директорию
cd ~

# Клонировать (замените URL на ваш)
git clone https://github.com/YOUR_USERNAME/sneaker-order-system.git sneaker-orders

# Перейти в проект
cd sneaker-orders
```

### 4.2 Создать .env файл на сервере

```bash
# Скопировать шаблон
cp .env.example .env

# Редактировать
nano .env
```

**Production конфигурация:**

```env
# ===== PRODUCTION ENVIRONMENT =====

# Environment
YII_ENV=prod
YII_DEBUG=false

# Security - СГЕНЕРИРУЙТЕ НОВЫЙ УНИКАЛЬНЫЙ КЛЮЧ!
COOKIE_VALIDATION_KEY=ДРУГОЙ_КЛЮЧ_НЕ_ТАКОЙ_КАК_НА_ЛОКАЛЬНОЙ_МАШИНЕ_64_СИМВОЛА

# Database (данные из cPanel)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=username_sneaker_orders
DB_USERNAME=username_sneaker_user
DB_PASSWORD=ваш_пароль_из_cPanel
DB_CHARSET=utf8mb4

# Database Performance
DB_SCHEMA_CACHE=true
DB_SCHEMA_CACHE_DURATION=3600
DB_TIMEOUT=5

# Email - РЕАЛЬНАЯ ОТПРАВКА
MAIL_USE_FILE_TRANSPORT=false
MAIL_FROM_EMAIL=noreply@your-domain.com
MAIL_FROM_NAME=СникерКультура

# SMTP Settings (замените на ваши)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_ENCRYPTION=tls

# Poizon API (если используете)
POIZON_API_URL=https://api.poizon-parser.com/v1
POIZON_API_KEY=your_key_if_needed

# Currency
CNY_TO_BYN_RATE=0.45

# Company Details (обновите реальными данными)
COMPANY_NAME=ООО "СникерКультура"
COMPANY_UNP=ваш_УНП
COMPANY_ADDRESS=ваш_адрес
COMPANY_PHONE=ваш_телефон
COMPANY_EMAIL=info@your-domain.com

# Admin
ADMIN_EMAIL=admin@your-domain.com
```

**Сохраните:** `Ctrl+O`, `Enter`, `Ctrl+X`

### 4.3 Установить зависимости Composer

```bash
cd ~/sneaker-orders

# Если composer установлен глобально:
composer install --no-dev --optimize-autoloader

# Если только composer.phar:
php composer.phar install --no-dev --optimize-autoloader
```

**Флаги:**
- `--no-dev` - не устанавливать dev-зависимости (Gii, Debug)
- `--optimize-autoloader` - оптимизация для production

### 4.4 Применить миграции базы данных

```bash
php yii migrate --interactive=0
```

Должно вывести список миграций и применить их. Ожидайте 30-40+ миграций.

### 4.5 Настроить права доступа

```bash
# Права на директории для записи
chmod 777 runtime/
chmod 777 web/uploads/
chmod 777 web/assets/

# ИЛИ более безопасно (если знаете пользователя веб-сервера)
chown -R www-data:www-data runtime/ web/uploads/ web/assets/
chmod -R 775 runtime/ web/uploads/ web/assets/
```

### 4.6 Проверить работоспособность

Откройте браузер:

```
http://your-domain.com
```

**Должно открыться:**
- Главная страница / Логин
- Страница входа `/login`

**Войдите:**
- Логин: `admin`
- Пароль: `admin123`

**⚠️ ОБЯЗАТЕЛЬНО СРАЗУ СМЕНИТЕ ПАРОЛЬ АДМИНА!**

---

## 5. Обновления после изменений

### 5.1 Создать скрипт обновления на сервере

```bash
nano ~/update-project.sh
```

**Содержимое скрипта:**

```bash
#!/bin/bash
set -e

PROJECT_DIR=~/sneaker-orders
LOG_FILE=~/deployment.log

echo "====================================" | tee -a $LOG_FILE
echo "🚀 Deployment started: $(date)" | tee -a $LOG_FILE
echo "====================================" | tee -a $LOG_FILE

cd $PROJECT_DIR || exit 1

# 1. Бекап базы данных
echo "📦 Creating database backup..." | tee -a $LOG_FILE
mysqldump -u USERNAME -pPASSWORD DATABASE > ~/backups/db_$(date +%Y%m%d_%H%M%S).sql

# 2. Git pull
echo "📥 Pulling latest changes..." | tee -a $LOG_FILE
git pull origin main

# 3. Composer
echo "📦 Installing dependencies..." | tee -a $LOG_FILE
composer install --no-dev --optimize-autoloader

# 4. Миграции
echo "🗄️  Running migrations..." | tee -a $LOG_FILE
php yii migrate --interactive=0

# 5. Очистка кеша
echo "🧹 Clearing cache..." | tee -a $LOG_FILE
rm -rf runtime/cache/*
rm -rf web/assets/*

# 6. Права доступа
echo "🔐 Setting permissions..." | tee -a $LOG_FILE
chmod 777 runtime/ web/uploads/ web/assets/

echo "====================================" | tee -a $LOG_FILE
echo "✅ Deployment completed: $(date)" | tee -a $LOG_FILE
echo "====================================" | tee -a $LOG_FILE
```

**Замените:**
- `USERNAME` - на пользователя MySQL
- `PASSWORD` - на пароль
- `DATABASE` - на имя БД

**Сделайте исполняемым:**

```bash
chmod +x ~/update-project.sh

# Создайте директорию для бекапов
mkdir -p ~/backups
```

### 5.2 Процесс обновления

#### С локальной машины:

```bash
# 1. Внести изменения в код
cd /Users/user/CascadeProjects/splitwise

# 2. Закоммитить
git add .
git commit -m "feat: добавлена новая функция"

# 3. Отправить на GitHub
git push origin main

# 4. Обновить production (по SSH)
ssh username@your-server.com "~/update-project.sh"
```

#### Или одной командой:

```bash
# Из локальной машины
cd /Users/user/CascadeProjects/splitwise
git add . && git commit -m "update" && git push origin main && ssh username@your-server.com "~/update-project.sh"
```

---

## 6. Возможные проблемы и решения

### 6.1 "Class not found" после git pull

**Причина:** Composer не обновил autoload

**Решение:**

```bash
cd ~/sneaker-orders
composer dump-autoload --optimize
```

### 6.2 "Database connection failed"

**Проверки:**

```bash
# 1. Проверить .env
cat .env | grep DB_

# 2. Проверить подключение вручную
mysql -h localhost -u username_sneaker_user -p
# Ввести пароль, должно подключиться

# 3. Проверить права пользователя
mysql -u root -p
SHOW GRANTS FOR 'username_sneaker_user'@'localhost';
```

**Если MySQL отклоняет подключение:**

```sql
-- В MySQL
GRANT ALL PRIVILEGES ON database_name.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

### 6.3 "Unable to write to runtime" / "Permission denied"

**Решение:**

```bash
cd ~/sneaker-orders

# Вариант 1 (простой)
chmod -R 777 runtime/ web/uploads/ web/assets/

# Вариант 2 (безопаснее, узнайте пользователя веб-сервера)
ps aux | grep nginx  # или apache
# Предположим, это www-data
sudo chown -R www-data:www-data runtime/ web/uploads/ web/assets/
chmod -R 775 runtime/ web/uploads/ web/assets/
```

### 6.4 "404 Not Found" на всех страницах кроме главной

**Причина:** mod_rewrite не работает или Document Root неправильный

**Решение для Apache:**

```bash
# 1. Проверить .htaccess
ls -la web/.htaccess
# Должен существовать

# 2. Включить mod_rewrite (если root доступ)
sudo a2enmod rewrite
sudo systemctl restart apache2

# 3. Проверить AllowOverride в Apache конфиге
# /etc/apache2/sites-available/your-site.conf
<Directory /home/username/sneaker-orders/web>
    AllowOverride All
</Directory>
```

**Решение для Nginx:**

```bash
# Проверить конфиг nginx
cat /etc/nginx/sites-available/your-site

# Должен быть try_files
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

### 6.5 Email не отправляются

**Проверки:**

```bash
# 1. Проверить .env
cat .env | grep MAIL_

# MAIL_USE_FILE_TRANSPORT должно быть false для production

# 2. Проверить логи
tail -f runtime/logs/app.log

# 3. Проверить SMTP данные
# Для Gmail нужен App Password: https://myaccount.google.com/apppasswords
```

**Тест отправки:**

```bash
php yii test/email admin@example.com
# Если такой команды нет, проверьте логи после действия в админке
```

### 6.6 Сайт показывает "An internal server error occurred"

**Причина:** PHP ошибка + production mode (не показывает детали)

**Временно включите отладку:**

```bash
nano .env

# Измените
YII_DEBUG=true

# Перезагрузите страницу, увидите детали ошибки
# ЗАТЕМ ВЕРНИТЕ ОБРАТНО:
YII_DEBUG=false
```

**Или смотрите логи:**

```bash
tail -f runtime/logs/app.log
```

### 6.7 "The file "vendor/autoload.php" does not exist"

**Причина:** Composer зависимости не установлены

**Решение:**

```bash
cd ~/sneaker-orders
composer install --no-dev --optimize-autoloader
```

### 6.8 Git pull требует логин/пароль (Private repo)

**Решение:** Настройте SSH ключ или Personal Access Token

**Вариант 1: SSH ключ (рекомендуется)**

```bash
# На сервере
ssh-keygen -t ed25519 -C "your-email@example.com"
# Нажимайте Enter (без пароля для автоматизации)

# Скопируйте публичный ключ
cat ~/.ssh/id_ed25519.pub

# Добавьте в GitHub:
# Settings → SSH and GPG keys → New SSH key → вставьте ключ

# Измените remote на SSH
cd ~/sneaker-orders
git remote set-url origin git@github.com:YOUR_USERNAME/sneaker-order-system.git
```

**Вариант 2: Personal Access Token**

```bash
# На GitHub: Settings → Developer settings → Personal access tokens → Generate new token
# Выберите права: repo (полный доступ к приватным репозиториям)

# Используйте вместо пароля при git pull
git pull https://YOUR_USERNAME:YOUR_TOKEN@github.com/YOUR_USERNAME/sneaker-order-system.git
```

---

## 7. Чек-лист безопасности Production

### Перед запуском:

- [ ] `.env` файл содержит `YII_ENV=prod` и `YII_DEBUG=false`
- [ ] `COOKIE_VALIDATION_KEY` уникален и отличается от локального
- [ ] Пароли БД сложные (минимум 16 символов, mixed case, цифры, спецсимволы)
- [ ] `MAIL_USE_FILE_TRANSPORT=false` (реальная отправка email)
- [ ] Пароль админа изменён с дефолтного `admin123`
- [ ] Создан регулярный бекап БД (cron: `mysqldump ...`)
- [ ] SSL сертификат установлен (Let's Encrypt, CloudFlare)
- [ ] Права доступа: runtime/uploads/assets не 777, а 775 (если возможно)
- [ ] `.env` и `config/db.php` **НЕ в Git** (проверено)
- [ ] Обновлены `COMPANY_*` данные в `.env` на реальные
- [ ] `ADMIN_EMAIL` указан реальный для уведомлений
- [ ] Настроен мониторинг (Uptime Robot, New Relic и т.д.)
- [ ] Проверены логи ошибок: `runtime/logs/app.log`

---

## 8. Полезные команды

### Логи

```bash
# Смотреть логи в реальном времени
tail -f ~/sneaker-orders/runtime/logs/app.log

# Последние 50 строк
tail -n 50 ~/sneaker-orders/runtime/logs/app.log

# Ошибки за сегодня
grep "$(date +%Y-%m-%d)" ~/sneaker-orders/runtime/logs/app.log | grep ERROR
```

### Очистка кеша

```bash
cd ~/sneaker-orders
rm -rf runtime/cache/*
rm -rf web/assets/*
```

### Бекап базы данных

```bash
# Создать бекап
mysqldump -u USER -pPASS DATABASE > backup_$(date +%Y%m%d).sql

# Восстановить из бекапа
mysql -u USER -pPASS DATABASE < backup_20251105.sql
```

### Мониторинг дискового пространства

```bash
# Общее использование
df -h

# Размер директорий проекта
du -sh ~/sneaker-orders/*

# Крупнейшие файлы
du -ah ~/sneaker-orders/ | sort -rh | head -n 20
```

---

## 9. Итоговый чеклист деплоя

### Локальная машина:

- [x] Создан `.env` локально с `YII_ENV=dev`
- [x] Сгенерирован `COOKIE_VALIDATION_KEY`
- [x] `.env` добавлен в `.gitignore` (уже есть)
- [x] `config/db.php` удалён из Git (если был)
- [x] Весь код закоммичен
- [x] Push на GitHub выполнен

### Production сервер:

- [ ] Репозиторий склонирован в `~/sneaker-orders`
- [ ] Создан `.env` с production настройками
- [ ] Сгенерирован **ДРУГОЙ** `COOKIE_VALIDATION_KEY` для production
- [ ] База данных создана в MySQL
- [ ] `composer install --no-dev --optimize-autoloader` выполнен
- [ ] `php yii migrate --interactive=0` выполнен
- [ ] Права 777/775 на runtime, uploads, assets
- [ ] Document Root указывает на `/web`
- [ ] Сайт открывается в браузере
- [ ] Логин работает
- [ ] Пароль админа изменён
- [ ] Email отправляются (проверено)
- [ ] Создан скрипт обновления `update-project.sh`
- [ ] Настроен SSL (HTTPS)
- [ ] Проверены логи на ошибки

---

## 10. Контакты поддержки

### В случае проблем:

1. **Логи ошибок:** `tail -f runtime/logs/app.log`
2. **Yii2 документация:** https://www.yiiframework.com/doc/guide/2.0/en
3. **GitHub Issues:** (создайте issue в своём репозитории)
4. **Хостинг поддержка:** (если проблемы с сервером)

---

**✅ Проект готов к production! Успешного деплоя!** 🚀
