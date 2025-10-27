# 🚀 Варианты развертывания PHP Yii2 приложения

**Проблема:** Netlify **НЕ ПОДДЕРЖИВАЕТ PHP** - только статические сайты (HTML/CSS/JS)

**Ваш проект:** Yii2 PHP фреймворк - требует PHP runtime и базу данных

---

## ❌ Почему не работает на Netlify

Netlify предназначен для:
- ✅ Статических HTML сайтов
- ✅ React/Vue/Angular приложений (статическая сборка)
- ✅ JAMstack сайтов
- ❌ **НЕ для PHP приложений**

Ваш проект использует:
- PHP 7.4+ (серверный язык)
- MySQL/SQLite (база данных)
- Yii2 фреймворк (динамическая обработка)

---

## ✅ Правильные варианты для PHP

### 🎯 Вариант 1: Ваш хостинг vh124.hoster.by (РЕКОМЕНДУЕТСЯ)

**Плюсы:**
- ✅ У вас уже есть доступ
- ✅ Поддержка PHP
- ✅ Поддержка MySQL
- ✅ Готовая инструкция (SERVER_DEPLOY_INSTRUCTIONS.md)
- ✅ Бесплатно (у вас уже оплачено)

**Как развернуть:**
См. файл **SERVER_DEPLOY_INSTRUCTIONS.md**

---

### 🆓 Вариант 2: Railway.app (БЕСПЛАТНЫЙ для тестирования)

**Плюсы:**
- ✅ Бесплатный план: $5 кредитов/месяц
- ✅ Поддержка PHP
- ✅ Автоматический деплой из GitHub
- ✅ Встроенная PostgreSQL
- ✅ Простая настройка

**Инструкция:**

#### 1. Зарегистрироваться на Railway
https://railway.app/

#### 2. Создать новый проект
- Нажать "New Project"
- Выбрать "Deploy from GitHub repo"
- Выбрать: urbandima/sneaker_podzakaz

#### 3. Добавить переменные окружения
В настройках проекта добавить:
```
APP_ENV=production
YII_DEBUG=false
YII_ENV=prod
```

#### 4. Добавить Nixpacks buildpack
Создать файл `nixpacks.toml` в корне:
```toml
[phases.setup]
nixPkgs = ['php82', 'php82Packages.composer']

[phases.build]
cmds = ['composer install --no-dev --optimize-autoloader']

[start]
cmd = 'php -S 0.0.0.0:$PORT -t web'
```

#### 5. Деплой
Railway автоматически развернет приложение!

---

### 🆓 Вариант 3: Render.com (БЕСПЛАТНЫЙ)

**Плюсы:**
- ✅ Бесплатный план навсегда
- ✅ Поддержка PHP
- ✅ Автоматический деплой из GitHub
- ✅ Встроенная PostgreSQL (бесплатно)
- ✅ SSL сертификат

**Минусы:**
- ⚠️ Засыпает после 15 минут неактивности
- ⚠️ Медленный старт после пробуждения

**Инструкция:**

#### 1. Зарегистрироваться на Render
https://render.com/

#### 2. Создать Web Service
- Dashboard → "New +"
- "Web Service"
- Connect GitHub: urbandima/sneaker_podzakaz

#### 3. Настроить
```
Name: sneaker-zakaz
Environment: Docker
Branch: main
```

#### 4. Создать Dockerfile (если нет)
```dockerfile
FROM php:8.1-apache

# Установить зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql

# Установить Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Копировать проект
COPY . /var/www/html

# Установить зависимости
RUN composer install --no-dev --optimize-autoloader

# Настроить права
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 runtime web/assets web/uploads

# Настроить Apache
RUN a2enmod rewrite
COPY .docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Порт
EXPOSE 80

CMD ["apache2-foreground"]
```

#### 5. Деплой
Render автоматически развернет!

---

### 🆓 Вариант 4: InfinityFree (БЕСПЛАТНЫЙ PHP хостинг)

**Плюсы:**
- ✅ Полностью бесплатно
- ✅ PHP 8.1
- ✅ MySQL
- ✅ Без ограничений по времени
- ✅ cPanel

**Минусы:**
- ⚠️ Реклама на бесплатном плане
- ⚠️ Ограничения по трафику

**Инструкция:**

#### 1. Зарегистрироваться
https://infinityfree.net/

#### 2. Создать аккаунт
- Выбрать домен (например: zakaz.infinityfreeapp.com)
- Подтвердить email

#### 3. Загрузить через FTP
```bash
# Использовать FileZilla или команду
ftp your-ftp-host
# Загрузить все файлы в htdocs/
```

#### 4. Создать БД через cPanel
- Перейти в MySQL Databases
- Создать базу данных
- Настроить config/db.php

#### 5. Применить миграции
Через SSH или phpMyAdmin импортировать структуру БД

---

### 💰 Вариант 5: Heroku (ПЛАТНЫЙ, но с trial)

**Плюсы:**
- ✅ Надежный
- ✅ Масштабируемый
- ✅ Простой деплой

**Минусы:**
- ❌ Больше нет бесплатного плана
- ❌ $5-$7/месяц минимум

**Инструкция:**

#### 1. Установить Heroku CLI
```bash
brew install heroku
heroku login
```

#### 2. Создать приложение
```bash
cd /Users/user/CascadeProjects/splitwise
heroku create sneaker-zakaz
```

#### 3. Добавить buildpack PHP
```bash
heroku buildpacks:add heroku/php
```

#### 4. Добавить PostgreSQL
```bash
heroku addons:create heroku-postgresql:mini
```

#### 5. Деплой
```bash
git push heroku main
```

---

## 🎯 Рекомендация

### Для тестирования:
**Railway.app** или **Render.com** - быстро и бесплатно

### Для production:
**Ваш хостинг vh124.hoster.by** - надежно и уже оплачено

---

## 🚀 Быстрое развертывание на Railway.app

### Шаг 1: Создать nixpacks.toml

```bash
cd /Users/user/CascadeProjects/splitwise

cat > nixpacks.toml << 'EOF'
[phases.setup]
nixPkgs = ['php82', 'php82Packages.composer']

[phases.build]
cmds = ['composer install --no-dev --optimize-autoloader']

[start]
cmd = 'php -S 0.0.0.0:$PORT -t web'
EOF
```

### Шаг 2: Закоммитить
```bash
git add nixpacks.toml
git commit -m "Add Railway.app configuration"
git push origin main
```

### Шаг 3: Развернуть на Railway
1. Зайти на https://railway.app/
2. "New Project" → "Deploy from GitHub"
3. Выбрать: urbandima/sneaker_podzakaz
4. Подождать деплоя
5. Получить URL: https://sneaker-zakaz.up.railway.app/

---

## 📊 Сравнение вариантов

| Платформа | Цена | PHP | MySQL | Авто-деплой | Время работы |
|-----------|------|-----|-------|-------------|--------------|
| **vh124.hoster.by** | Оплачено | ✅ | ✅ | ✅ (GitHub) | ∞ |
| **Railway.app** | $5 кредит | ✅ | ✅ | ✅ | До $5 |
| **Render.com** | Бесплатно | ✅ | ✅ | ✅ | Засыпает |
| **InfinityFree** | Бесплатно | ✅ | ✅ | ❌ | ∞ |
| **Heroku** | От $5/мес | ✅ | ✅ | ✅ | ∞ |
| **Netlify** | ❌ | ❌ | ❌ | ❌ | N/A |

---

## ✅ Что делать прямо сейчас

### Вариант A: Быстрое тестирование (5 минут)
1. Открыть https://railway.app/
2. Подключить GitHub
3. Развернуть проект
4. Получить тестовый URL

### Вариант B: Production развертывание (15 минут)
1. Открыть **SERVER_DEPLOY_INSTRUCTIONS.md**
2. Следовать инструкциям для vh124.hoster.by
3. Развернуть на zakaz.sneaker-head.by

---

## 🆘 Нужна помощь?

**Для Railway.app:** Я могу создать нужные конфигурационные файлы

**Для вашего хостинга:** Следуйте SERVER_DEPLOY_INSTRUCTIONS.md

**Локальное тестирование:** Уже работает на http://localhost:8080/

---

**Рекомендация:** Используйте **Railway.app** для быстрого тестирования, затем разверните на **vh124.hoster.by** для production.
