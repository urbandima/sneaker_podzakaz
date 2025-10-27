# 🚀 Развертывание на Render.com

**Платформа:** Render.com  
**Тип:** Docker deployment  
**План:** Free (бесплатно навсегда)

---

## ✅ Что создано

Я создал все необходимые файлы для Render.com:

1. **Dockerfile** - конфигурация Docker контейнера
2. **.dockerignore** - исключения для Docker
3. **render.yaml** - конфигурация Render.com
4. **RENDER_DEPLOYMENT.md** - эта инструкция

---

## 🚀 Шаг 1: Отправить файлы на GitHub (2 минуты)

```bash
cd /Users/user/CascadeProjects/splitwise

# Добавить новые файлы
git add Dockerfile .dockerignore render.yaml RENDER_DEPLOYMENT.md

# Закоммитить
git commit -m "Add Render.com deployment configuration"

# Отправить на GitHub
git push origin main
```

---

## 🌐 Шаг 2: Развернуть на Render.com (5 минут)

### 2.1 Зарегистрироваться

1. Открыть https://render.com/
2. Нажать "Get Started"
3. Войти через GitHub

### 2.2 Создать Web Service

1. На Dashboard нажать **"New +"**
2. Выбрать **"Web Service"**

### 2.3 Подключить репозиторий

1. Нажать **"Connect a repository"**
2. Найти: **urbandima/sneaker_podzakaz**
3. Нажать **"Connect"**

### 2.4 Настроить сервис

Заполнить форму:

```
Name: sneaker-zakaz
Region: Frankfurt (EU Central)
Branch: main
Runtime: Docker
```

**Environment Variables (не обязательно):**
```
YII_ENV = prod
YII_DEBUG = false
```

**Instance Type:**
- Выбрать: **Free** (бесплатно)

### 2.5 Развернуть

1. Нажать **"Create Web Service"**
2. Подождать 3-5 минут (первый деплой)
3. Статус изменится на **"Live"** ✅

### 2.6 Получить URL

Render выдаст URL типа:
```
https://sneaker-zakaz.onrender.com
```

---

## ✅ Проверка работоспособности

### Главная страница
```
https://sneaker-zakaz.onrender.com/
```
Должен открыться лендинг

### Админка
```
https://sneaker-zakaz.onrender.com/admin
```
Должна открыться панель авторизации

### Проверить логи

В Render Dashboard:
1. Открыть ваш сервис
2. Перейти в **"Logs"**
3. Проверить, что нет ошибок

---

## 🔧 Особенности Render.com Free Plan

### ✅ Плюсы:
- Бесплатно навсегда
- Автоматический деплой из GitHub
- SSL сертификат (HTTPS)
- 750 часов в месяц (достаточно для одного сервиса)

### ⚠️ Ограничения:
- **Засыпает** после 15 минут неактивности
- **Холодный старт** ~30 секунд после пробуждения
- 512 MB RAM
- Не подходит для высоконагруженных проектов

### Когда засыпает:
Сервис "засыпает" если нет запросов 15 минут

### Как разбудить:
Просто открыть сайт - подождать 30 секунд

---

## 🔄 Автоматический деплой

После настройки **каждый git push** будет автоматически развертываться!

```bash
# Сделать изменения
git add .
git commit -m "Update something"
git push origin main

# Render.com автоматически:
# 1. Получит обновление из GitHub
# 2. Соберет Docker образ
# 3. Развернет новую версию
# 4. ~3-5 минут
```

---

## 📧 Настройка Email (опционально)

Для отправки Email добавить переменные окружения в Render:

1. В настройках сервиса → **Environment**
2. Добавить:

```
SMTP_HOST = smtp.gmail.com
SMTP_PORT = 587
SMTP_USERNAME = sneakerkultura@gmail.com
SMTP_PASSWORD = ваш_пароль_приложения
SMTP_ENCRYPTION = tls
```

3. Обновить `config/web.php` чтобы использовать эти переменные

---

## 🗄️ База данных

### Текущая конфигурация: SQLite

Проект использует SQLite (файл `runtime/database.sqlite`)

**Проблема:** На Render Free файлы **не сохраняются** между перезапусками

**Решения:**

### Вариант 1: Добавить PostgreSQL (Рекомендуется)

1. В Render Dashboard нажать **"New +"**
2. Выбрать **"PostgreSQL"**
3. **Name:** sneaker-zakaz-db
4. **Plan:** Free
5. Создать
6. Получить **Internal Database URL**

7. В настройках Web Service добавить переменную:
```
DATABASE_URL = postgresql://user:pass@host:5432/dbname
```

8. Обновить `config/db.php`:
```php
<?php
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl) {
    $dbConfig = parse_url($databaseUrl);
    return [
        'class' => 'yii\db\Connection',
        'dsn' => sprintf('pgsql:host=%s;port=%s;dbname=%s',
            $dbConfig['host'],
            $dbConfig['port'],
            ltrim($dbConfig['path'], '/')
        ),
        'username' => $dbConfig['user'],
        'password' => $dbConfig['pass'],
        'charset' => 'utf8',
    ];
}

// Fallback to SQLite
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'sqlite:' . __DIR__ . '/../runtime/database.sqlite',
    'charset' => 'utf8',
];
```

### Вариант 2: Использовать SQLite (проще для тестов)

SQLite работает, но база **сбрасывается** при каждом деплое

Для тестирования это нормально!

---

## 🐛 Решение проблем

### Ошибка: "Application error"

Проверить логи:
1. Render Dashboard → ваш сервис
2. **Logs** tab
3. Найти ошибку

### Ошибка: "Health check failed"

Подождать 2-3 минуты - первый запуск медленный

### Ошибка: "Out of memory"

Free план: 512 MB RAM
- Уменьшить composer зависимости
- Добавить `--no-dev` флаг

### Сайт не открывается

1. Проверить статус: должен быть **"Live"** ✅
2. Подождать 30 секунд (холодный старт)
3. Проверить логи

---

## 📊 Мониторинг

### В Render Dashboard:

- **Metrics** - CPU, Memory, Response Time
- **Logs** - логи приложения
- **Events** - история деплоев

### Держать сервис активным (опционально)

Использовать cron или UptimeRobot:
```
https://uptimerobot.com/
```

Пинговать каждые 10 минут:
```
https://sneaker-zakaz.onrender.com/
```

---

## 💰 Upgrade на платный план (опционально)

Если нужно избежать засыпания:

**Starter Plan: $7/месяц**
- Не засыпает
- 512 MB RAM
- 0.5 CPU

**Standard Plan: $25/месяц**
- Не засыпает
- 2 GB RAM
- 1 CPU

---

## ✅ Чек-лист развертывания

- [ ] Создан Dockerfile
- [ ] Создан .dockerignore
- [ ] Создан render.yaml
- [ ] Файлы закоммичены и отправлены на GitHub
- [ ] Зарегистрирован на Render.com
- [ ] Создан Web Service
- [ ] Подключен GitHub репозиторий
- [ ] Выбран Free план
- [ ] Деплой завершен (статус "Live")
- [ ] Сайт открывается по URL
- [ ] Админка доступна
- [ ] Можно создать заказ

---

## 🎯 Готово!

После выполнения всех шагов ваш проект будет доступен по адресу:

```
https://sneaker-zakaz.onrender.com
```

**Автодеплой:** каждый `git push` автоматически обновляет сайт!

---

## 🔄 Дальнейшие действия

### Для тестирования:
Render.com подходит идеально! ✅

### Для production:
Рекомендую развернуть на **vh124.hoster.by** (ваш хостинг)
- Не засыпает
- Быстрее
- Надежнее
- У вас уже оплачено

См. **SERVER_DEPLOY_INSTRUCTIONS.md**

---

**Время развертывания:** 10 минут  
**Стоимость:** Бесплатно  
**Автодеплой:** Да ✅
