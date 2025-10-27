# 🗄️ Запуск миграций на Render.com

**После первого успешного деплоя нужно выполнить миграции вручную**

---

## ✅ Что исправлено

### Проблема:
```
Could not open input file: /var/www/html/yii
```

### Решение:
1. ✅ Убрали `/yii` из `.gitignore`
2. ✅ Добавили файл `yii` в репозиторий
3. ✅ Упростили `docker-entrypoint.sh` (убрали автоматические миграции)

---

## 🚀 Шаги после деплоя

### Шаг 1: Дождаться успешного деплоя

В Render Dashboard → Logs должно быть:
```
========================================
Starting Sneaker Zakaz Application
========================================
Using default port 80
WARNING: Database not found. You may need to run migrations manually.
Starting Apache web server...
========================================
[success] Server started
```

### Шаг 2: Открыть Shell в Render

1. Открыть Render Dashboard
2. Выбрать ваш сервис
3. Нажать **"Shell"** в правом верхнем углу

Или использовать Render CLI:
```bash
render ssh sneaker-zakaz
```

### Шаг 3: Выполнить миграции

В открытом Shell выполнить:

```bash
# Перейти в директорию проекта
cd /var/www/html

# Проверить, что файл yii существует
ls -la yii

# Выполнить миграции
php yii migrate --interactive=0
```

Должно вывести:
```
*** applying m241023_181500_create_users_table
    > create table users ... done (time: 0.123s)
*** applying m241023_181600_create_orders_table
    > create table orders ... done (time: 0.098s)
...
6 migrations applied successfully.
```

### Шаг 4: Проверить базу данных

```bash
# Проверить, что БД создана
ls -lh runtime/database.sqlite

# Должно показать размер файла, например:
# -rw-r--r-- 1 www-data www-data 40K Oct 28 01:05 runtime/database.sqlite
```

---

## 🎯 После миграций

### Проверить работу сайта

1. **Главная страница:**
   ```
   https://ваш-сервис.onrender.com/
   ```
   Должен открыться лендинг

2. **Админка:**
   ```
   https://ваш-сервис.onrender.com/admin
   ```
   Должна открыться форма авторизации

3. **Создать тестовый заказ:**
   - Войти в админку
   - Создать заказ
   - Проверить публичную ссылку

---

## ⚠️ Важно о Render Free Plan

### База данных SQLite

На **Free плане** файлы **не сохраняются** между перезапусками!

Это значит:
- ❌ При каждом деплое БД сбрасывается
- ❌ Все заказы теряются
- ❌ Не подходит для production

### Решения:

#### Вариант 1: Добавить PostgreSQL (Рекомендуется)

1. В Render Dashboard → **New +** → **PostgreSQL**
2. Name: `sneaker-zakaz-db`
3. Plan: **Free**
4. Создать

5. Получить **Internal Database URL**

6. В настройках Web Service добавить переменную:
   ```
   DATABASE_URL=postgresql://user:pass@host:5432/dbname
   ```

7. Обновить `config/db.php`:
   ```php
   <?php
   $databaseUrl = getenv('DATABASE_URL');
   if ($databaseUrl) {
       $db = parse_url($databaseUrl);
       return [
           'class' => 'yii\db\Connection',
           'dsn' => sprintf('pgsql:host=%s;port=%s;dbname=%s',
               $db['host'],
               $db['port'],
               ltrim($db['path'], '/')
           ),
           'username' => $db['user'],
           'password' => $db['pass'],
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

8. Установить pdo_pgsql расширение (уже есть в Dockerfile)

9. Выполнить миграции снова:
   ```bash
   php yii migrate --interactive=0
   ```

#### Вариант 2: Для тестирования

Если это только для тестов:
- SQLite работает нормально
- Просто помните, что данные сбросятся при каждом деплое

#### Вариант 3: Production деплой

Для настоящего production используйте:
- **Ваш хостинг vh124.hoster.by**
- Там файлы сохраняются навсегда
- MySQL база
- Нет ограничений

См. **SERVER_DEPLOY_INSTRUCTIONS.md**

---

## 🔄 Автоматические миграции (опционально)

Если хотите вернуть автоматические миграции при старте:

Отредактировать `docker-entrypoint.sh`:
```bash
# Применить миграции при каждом старте
if [ -f /var/www/html/yii ]; then
    echo "Running migrations..."
    php /var/www/html/yii migrate --interactive=0 || true
fi
```

**Но:** для Render Free это может вызывать проблемы, так как миграции выполняются при каждом перезапуске контейнера.

---

## 📝 Полезные команды в Shell

```bash
# Проверить PHP версию
php -v

# Проверить расширения PHP
php -m

# Посмотреть логи приложения
tail -f runtime/logs/app.log

# Очистить кеш
rm -rf runtime/cache/*

# Проверить права
ls -la runtime/ web/assets/ web/uploads/

# Проверить конфигурацию БД
cat config/db.php

# Запустить Yii консоль
php yii

# Список всех команд Yii
php yii help
```

---

## ✅ Готово!

После выполнения миграций ваше приложение полностью работает на Render.com!

---

## 🎯 Статус

**Commit:** `1bc782d`  
**Изменения:**
- ✅ Файл `yii` добавлен в репозиторий
- ✅ Entrypoint упрощен
- ✅ Убраны автоматические миграции

**Следующий шаг:** 
Дождаться деплоя → Открыть Shell → Выполнить миграции
