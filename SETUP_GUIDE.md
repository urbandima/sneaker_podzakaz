# 🎯 Пошаговая инструкция для zakaz.sneaker-head.by

## Что нужно сделать прямо сейчас:

### ✅ Шаг 1: Соберите данные

Заполните файл **DEPLOY_INFO.txt** следующими данными:

#### 1. SSH доступ к хостингу:
- **Username** - логин для входа в cPanel
- **Host** - обычно `sneaker-head.by` или IP адрес
- **Password** - пароль от cPanel

#### 2. MySQL база данных (создать в cPanel):
- Зайти в **cPanel → MySQL® Databases**
- Создать БД: `username_zakaz` (или любое имя)
- Создать пользователя: `username_zakazuser`
- Назначить пользователя к БД (ALL PRIVILEGES)
- Записать: имя БД, пользователя, пароль

#### 3. Путь к папке на сервере:
```bash
# Подключитесь по SSH и выполните:
pwd
# Обычно это:
# /home/username/domains/zakaz.sneaker-head.by/public_html
# или
# /home/username/zakaz
```

#### 4. GitHub:
- Создайте **Private** репозиторий на github.com
- Скопируйте URL (например: `https://github.com/username/order-system.git`)

---

## 📋 Пример заполнения DEPLOY_INFO.txt

```
SSH Host: sneaker-head.by
SSH Username: sneaker123
SSH Port: 22
SSH Password: ваш_пароль_cpanel

Путь к корневой папке: /home/sneaker123/zakaz
DocumentRoot: /home/sneaker123/zakaz/web

Database Host: localhost
Database Name: sneaker123_zakaz
Database Username: sneaker123_zakazuser
Database Password: сложный_пароль_123

GitHub Repository URL: https://github.com/yourusername/order-system.git
GitHub Username: yourusername
GitHub Token: ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxx

Нужно ли создать администратора? Да
Username: admin
Password: admin_password_123
```

---

## 🚀 После заполнения данных

### Вариант 1: Я создам автоматический скрипт

Просто отправьте мне заполненный DEPLOY_INFO.txt (можно скопировать текст), и я:

1. Создам настроенный скрипт `deploy.sh`
2. Создам скрипт первичной установки `initial-setup.sh`
3. Создам файл `config/db.php` для сервера

Вам останется только:
```bash
chmod +x initial-setup.sh
./initial-setup.sh
```

### Вариант 2: Ручная установка

Если хотите сами - следуйте инструкции ниже.

---

## 📖 Подробная ручная установка

### 1. Создайте GitHub репозиторий

```bash
# На локальной машине
cd /Users/user/CascadeProjects/splitwise

# Создайте config/db.php для локальной разработки
cp config/db-example.php config/db.php
nano config/db.php  # Укажите ваши локальные данные MySQL

# Инициализируйте Git (если еще не сделано)
git init
git add .
git commit -m "Initial commit: Order Management System"

# Создайте репозиторий на GitHub.com (Private!)
# Затем свяжите:
git remote add origin https://github.com/ВАШЕ_ИМЯ/order-system.git
git branch -M main
git push -u origin main
```

### 2. Настройте MySQL в cPanel

1. Войдите в **cPanel**
2. Найдите **MySQL® Databases**
3. **Create New Database**: `username_zakaz`
4. **Add New User**: создайте пользователя с паролем
5. **Add User To Database**: назначьте ALL PRIVILEGES
6. **Запишите данные подключения**

### 3. Подключитесь к серверу и клонируйте

```bash
# SSH подключение
ssh username@sneaker-head.by

# Определите путь к папке zakaz
cd domains/zakaz.sneaker-head.by/public_html
# или
cd ~/zakaz
# или
cd public_html/zakaz

# Запомните путь
pwd

# Клонируйте репозиторий
git clone https://github.com/ВАШЕ_ИМЯ/order-system.git .

# Если Git спросит логин/пароль:
# - Username: ваш GitHub username
# - Password: Personal Access Token (создать в GitHub Settings)
```

### 4. Создайте config/db.php на сервере

```bash
# На сервере
cp config/db-example.php config/db.php
nano config/db.php
```

Вставьте:
```php
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=username_zakaz',
    'username' => 'username_zakazuser',
    'password' => 'ваш_пароль_из_cpanel',
    'charset' => 'utf8mb4',
];
```

Сохраните: `Ctrl+O`, Enter, `Ctrl+X`

### 5. Измените cookieValidationKey

```bash
# Сгенерируйте ключ
php -r "echo bin2hex(random_bytes(32));"

# Откройте файл
nano config/web.php

# Найдите строку с cookieValidationKey и замените на сгенерированный
```

### 6. Установите Composer (если нет)

```bash
# Проверка
composer --version

# Если нет - установите
curl -sS https://getcomposer.org/installer | php
# Используйте: php composer.phar вместо composer
```

### 7. Установите зависимости

```bash
composer install --no-dev --optimize-autoloader
# или если установили локально:
php composer.phar install --no-dev --optimize-autoloader
```

### 8. Создайте необходимые папки

```bash
mkdir -p runtime/logs
mkdir -p web/uploads/payments
mkdir -p web/assets

chmod 755 runtime
chmod 755 web/uploads
chmod 755 web/assets
chmod 755 web/uploads/payments
```

### 9. Запустите миграции

```bash
php yii migrate
```

Ответьте `yes` на все вопросы.

### 10. Создайте администратора

**Способ 1: Через SQL (phpMyAdmin)**

```sql
-- Сначала сгенерируйте хеш пароля в SSH:
-- php -r "echo password_hash('ваш_пароль', PASSWORD_DEFAULT);"

INSERT INTO `user` (username, password_hash, auth_key, role, status, created_at, updated_at)
VALUES (
    'admin',
    '$2y$13$HASH_КОТОРЫЙ_ПОЛУЧИЛИ_ВЫШЕ',
    MD5(RAND()),
    'admin',
    10,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);
```

**Способ 2: Через консольную команду (если создана)**

```bash
php yii create-admin
```

### 11. Настройте DocumentRoot в cPanel

1. Войдите в **cPanel**
2. Найдите **Domains** или **Subdomains**
3. Нажмите **Manage** возле `zakaz.sneaker-head.by`
4. Измените **Document Root** на:
   ```
   /home/username/zakaz/web
   ```
   (путь должен заканчиваться на `/web`)
5. Сохраните

### 12. Проверьте сайт

Откройте: **http://zakaz.sneaker-head.by**

Должна появиться страница входа.

---

## 🔄 Обновление сайта в будущем

### На локальной машине:

```bash
# Делаете изменения в коде
# Затем:
git add .
git commit -m "Описание изменений"
git push origin main
```

### На сервере:

```bash
ssh username@sneaker-head.by
cd /путь/к/zakaz
git pull origin main
composer install --no-dev
php yii migrate --interactive=0
rm -rf runtime/cache/*
```

### Или используйте скрипт:

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## ⚡ Быстрые команды

```bash
# Подключиться к серверу
ssh username@sneaker-head.by

# Перейти в папку проекта
cd /путь/к/zakaz

# Подтянуть изменения
git pull origin main

# Очистить кэш
rm -rf runtime/cache/* web/assets/*

# Посмотреть логи ошибок
tail -n 50 runtime/logs/app.log
```

---

## 🆘 Помощь

### Не получается подключиться по SSH?

```bash
# Попробуйте другой порт
ssh -p 2222 username@sneaker-head.by

# Или укажите полный путь к ключу
ssh -i ~/.ssh/id_rsa username@sneaker-head.by
```

### Git спрашивает пароль?

Создайте **Personal Access Token** на GitHub:
1. GitHub → Settings → Developer settings → Personal access tokens
2. Generate new token (classic)
3. Выберите права: repo (все подпункты)
4. Скопируйте токен
5. Используйте вместо пароля при git clone

### Ошибка прав доступа?

```bash
chmod -R 755 runtime web/assets web/uploads
chown -R username:username *
```

---

## 📞 Следующий шаг

**Заполните DEPLOY_INFO.txt и отправьте мне**, я создам автоматические скрипты специально для вашего хостинга.

Или следуйте ручной инструкции выше.

**Успехов! 🚀**
