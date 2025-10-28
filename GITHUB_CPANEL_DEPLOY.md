# 🚀 Деплой через GitHub на cPanel хостинг

## 📋 Преимущества этого метода

- ✅ Автоматическое обновление через `git pull`
- ✅ История всех изменений
- ✅ Легкий откат к предыдущим версиям
- ✅ Работа в команде
- ✅ Безопасное хранение кода

---

## 🎯 Шаг 1: Подготовка проекта для GitHub

### 1.1 Создать db.php на основе примера

```bash
cd /Users/user/CascadeProjects/splitwise
cp config/db-example.php config/db.php
```

Отредактировать `config/db.php` с вашими локальными данными MySQL.

### 1.2 Проверить .gitignore

Файл `.gitignore` уже настроен. Проверьте что там есть:

```
/config/db.php          # НЕ коммитим пароли БД
/web/uploads/*          # НЕ коммитим файлы клиентов
/vendor                 # НЕ коммитим зависимости
/runtime/*              # НЕ коммитим временные файлы
```

### 1.3 Инициализировать Git репозиторий

```bash
cd /Users/user/CascadeProjects/splitwise

# Инициализация (если еще не сделано)
git init

# Добавить все файлы
git add .

# Первый коммит
git commit -m "Initial commit: Order Management System"
```

---

## 🌐 Шаг 2: Создание GitHub репозитория

### 2.1 На GitHub.com

1. **Войти** на https://github.com
2. **Нажать** "New repository" (зеленая кнопка)
3. **Заполнить:**
   - Repository name: `order-management` (или любое имя)
   - Description: "Система управления заказами"
   - Private/Public: выбрать **Private** (для безопасности)
   - **НЕ** создавать README, .gitignore, license
4. **Нажать** "Create repository"

### 2.2 Связать локальный репозиторий с GitHub

Скопировать команды с GitHub и выполнить:

```bash
git remote add origin https://github.com/ваш-username/order-management.git
git branch -M main
git push -u origin main
```

**Или через SSH (рекомендуется):**

```bash
git remote add origin git@github.com:ваш-username/order-management.git
git branch -M main
git push -u origin main
```

---

## 🖥️ Шаг 3: Настройка MySQL на cPanel

### 3.1 Создать базу данных

1. **Войти в cPanel**
2. **Найти** "MySQL® Databases"
3. **Создать новую БД:**
   - Database Name: `order_management` (или другое)
   - Нажать "Create Database"

### 3.2 Создать пользователя БД

1. В том же разделе "MySQL® Databases"
2. **Add New User:**
   - Username: `order_user` (или другое)
   - Password: сгенерировать надежный
   - Нажать "Create User"

### 3.3 Назначить пользователя к БД

1. **Найти** "Add User To Database"
2. **Выбрать** созданного пользователя и БД
3. **Отметить** "ALL PRIVILEGES"
4. **Нажать** "Make Changes"

### 3.4 Записать данные подключения

```
Host: localhost
Database: username_order_management
Username: username_order_user
Password: ваш_пароль
```

Формат обычно: `cpanel_username_dbname`

---

## 📦 Шаг 4: Клонирование на хостинг

### 4.1 Подключиться по SSH

```bash
ssh username@your-domain.com
# или через cPanel → Terminal
```

### 4.2 Перейти в корневую папку

```bash
cd ~/public_html
# или
cd ~/www
# или
cd ~/domains/your-domain.com/public_html
```

### 4.3 Клонировать репозиторий

**Вариант 1: HTTPS (потребуется логин/пароль или токен)**

```bash
git clone https://github.com/ваш-username/order-management.git .
```

**Вариант 2: SSH (нужен SSH ключ)**

```bash
# Сгенерировать ключ на сервере
ssh-keygen -t ed25519 -C "your-email@example.com"

# Показать публичный ключ
cat ~/.ssh/id_ed25519.pub

# Скопировать и добавить в GitHub → Settings → SSH keys

# Клонировать
git clone git@github.com:ваш-username/order-management.git .
```

---

## ⚙️ Шаг 5: Настройка на хостинге

### 5.1 Создать config/db.php

```bash
cd ~/public_html
cp config/db-example.php config/db.php
nano config/db.php
```

Указать данные из Шага 3:

```php
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=username_order_management',
    'username' => 'username_order_user',
    'password' => 'ваш_пароль_из_cpanel',
    'charset' => 'utf8mb4',
];
```

Сохранить: `Ctrl+O`, Enter, `Ctrl+X`

### 5.2 Изменить cookieValidationKey

```bash
nano config/web.php
```

Найти строку с `cookieValidationKey` и заменить на уникальный:

```bash
# Сгенерировать ключ
php -r "echo bin2hex(random_bytes(32));"
```

### 5.3 Установить зависимости

```bash
composer install --no-dev --optimize-autoloader
```

**Если composer не установлен:**

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### 5.4 Создать необходимые папки

```bash
mkdir -p runtime/logs
mkdir -p web/uploads/payments
mkdir -p web/assets

chmod 755 runtime
chmod 755 web/assets
chmod 755 web/uploads
chmod 755 web/uploads/payments
```

### 5.5 Запустить миграции

```bash
php yii migrate
```

Ответить `yes` на все вопросы.

### 5.6 Создать администратора

**SQL запрос через phpMyAdmin:**

```sql
-- Сгенерировать хеш (выполнить в SSH)
-- php -r "echo password_hash('ваш_пароль', PASSWORD_DEFAULT);"

INSERT INTO `user` (username, password_hash, auth_key, role, status, created_at, updated_at)
VALUES (
    'admin',
    '$2y$13$HASH_ВАШЕГО_ПАРОЛЯ',
    'случайная_строка_32_символа',
    'admin',
    10,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);
```

---

## 🔧 Шаг 6: Настройка DocumentRoot в cPanel

### 6.1 Через cPanel → Domains

1. **Найти** "Domains" или "Addon Domains"
2. **Нажать** "Manage" возле вашего домена
3. **Изменить Document Root** на:
   ```
   /home/username/public_html/web
   ```
4. **Сохранить**

### 6.2 Или создать симлинк (альтернатива)

Если нельзя изменить DocumentRoot:

```bash
cd ~/public_html
mv * ../temp_backup/  # Временно переместить файлы
ln -s ~/public_html_git/web/* .
```

---

## 🔄 Шаг 7: Обновление сайта (git pull)

### Когда нужно обновить сайт:

1. **Закоммитить изменения локально:**
   ```bash
   git add .
   git commit -m "Описание изменений"
   git push origin main
   ```

2. **Подключиться к хостингу по SSH:**
   ```bash
   ssh username@your-domain.com
   ```

3. **Перейти в папку проекта:**
   ```bash
   cd ~/public_html
   ```

4. **Подтянуть изменения:**
   ```bash
   git pull origin main
   ```

5. **Если есть новые зависимости:**
   ```bash
   composer install --no-dev
   ```

6. **Если есть новые миграции:**
   ```bash
   php yii migrate
   ```

7. **Очистить cache:**
   ```bash
   rm -rf runtime/cache/*
   ```

---

## 🤖 Автоматизация через Cron (опционально)

### Создать скрипт автообновления

```bash
nano ~/auto-deploy.sh
```

Содержимое:

```bash
#!/bin/bash

cd ~/public_html

# Проверить изменения на GitHub
git fetch origin main

# Если есть изменения
if [ $(git rev-parse HEAD) != $(git rev-parse @{u}) ]; then
    echo "Обнаружены изменения. Обновляем..."
    
    # Подтянуть изменения
    git pull origin main
    
    # Установить зависимости
    composer install --no-dev --optimize-autoloader
    
    # Запустить миграции
    php yii migrate --interactive=0
    
    # Очистить cache
    rm -rf runtime/cache/*
    
    echo "Обновление завершено: $(date)"
else
    echo "Изменений нет: $(date)"
fi
```

Сделать исполняемым:

```bash
chmod +x ~/auto-deploy.sh
```

### Добавить в Cron (cPanel → Cron Jobs)

```
# Каждый день в 3:00 ночи
0 3 * * * /home/username/auto-deploy.sh >> /home/username/deploy.log 2>&1
```

---

## 📝 Рабочий процесс (Workflow)

### Ежедневная работа:

1. **Локально делаете изменения**
2. **Тестируете** на локальном сервере
3. **Коммитите:**
   ```bash
   git add .
   git commit -m "Добавил функцию X"
   git push origin main
   ```
4. **На хостинге обновляете:**
   ```bash
   ssh user@host
   cd ~/public_html
   git pull origin main
   ```

### При критических обновлениях:

```bash
# Откатиться к предыдущей версии
git log --oneline  # Посмотреть историю
git reset --hard COMMIT_HASH
```

---

## 🔒 Безопасность

### ✅ Что защищено через .gitignore:

- `/config/db.php` - пароли БД
- `/web/uploads/*` - файлы клиентов
- `/runtime/*` - логи и cache
- `/vendor` - зависимости (устанавливаются на сервере)

### ⚠️ Что НЕ коммитить:

- Пароли и ключи
- Файлы клиентов
- Логи
- Большие файлы (> 100MB)

### 🔐 Приватный репозиторий:

Обязательно сделайте репозиторий **Private** на GitHub!

---

## ✅ Проверка после деплоя

1. **Открыть сайт:** https://your-domain.com
2. **Войти в админку:** /login
3. **Создать тестовый заказ**
4. **Проверить загрузку чека**
5. **Проверить popup с банками**

---

## 🆘 Решение проблем

### Проблема: "Permission denied" при git pull

**Решение:**
```bash
chmod -R 755 ~/public_html
git config --global --add safe.directory ~/public_html
```

### Проблема: Composer не находится

**Решение:**
```bash
curl -sS https://getcomposer.org/installer | php
alias composer='php ~/composer.phar'
```

### Проблема: Изменения не применяются

**Решение:**
```bash
# Очистить cache
rm -rf runtime/cache/*
rm -rf web/assets/*

# Перезапустить PHP-FPM (если доступно)
killall -9 php-fpm
```

### Проблема: Конфликты при git pull

**Решение:**
```bash
# Сохранить локальные изменения
git stash

# Подтянуть изменения
git pull origin main

# Вернуть локальные изменения
git stash pop
```

---

## 📊 Структура файлов на хостинге

```
/home/username/public_html/
├── config/
│   ├── db.php              ← СОЗДАТЬ ВРУЧНУЮ (не в Git)
│   ├── db-example.php      ← из Git
│   └── web.php             ← из Git
├── web/                     ← КОРЕНЬ САЙТА (DocumentRoot)
│   ├── index.php
│   ├── .htaccess
│   ├── uploads/
│   └── assets/
├── runtime/                 ← создать папку
├── vendor/                  ← composer install
└── migrations/              ← из Git
```

---

## 🎉 Готово!

Теперь у вас:
- ✅ Код в GitHub
- ✅ Автоматическое обновление через `git pull`
- ✅ MySQL база данных
- ✅ cPanel хостинг
- ✅ 10 банков с инструкциями
- ✅ Безопасность настроена

### Следующие шаги:

1. Сделайте первый коммит
2. Push в GitHub
3. Clone на хостинге
4. Настройте и запустите

**Удачи! 🚀**
