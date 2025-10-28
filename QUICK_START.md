# ⚡ Быстрый старт - Шпаргалка

## 🎯 Первый раз (Локально)

```bash
# 1. Создать config/db.php
cp config/db-example.php config/db.php

# 2. Отредактировать config/db.php с вашими данными MySQL

# 3. Изменить cookieValidationKey в config/web.php
php -r "echo bin2hex(random_bytes(32));"

# 4. Установить зависимости
composer install

# 5. Запустить миграции
php yii migrate

# 6. Запустить сервер
php yii serve --port=8080
```

---

## 📤 Push в GitHub (Первый раз)

```bash
# 1. Создать репозиторий на GitHub.com (Private!)

# 2. Локально
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/USERNAME/REPO.git
git push -u origin main
```

---

## 🚀 Деплой на cPanel (Первый раз)

### В cPanel создать MySQL БД:
```
Database: username_order_management
User: username_order_user
Password: [сгенерировать]
```

### SSH на хостинг:

```bash
# 1. Подключиться
ssh user@your-domain.com

# 2. Клонировать
cd ~/public_html
git clone https://github.com/USERNAME/REPO.git .

# 3. Создать config/db.php
cp config/db-example.php config/db.php
nano config/db.php
# Вставить данные БД из cPanel

# 4. Изменить cookieValidationKey
nano config/web.php
# Заменить на новый ключ

# 5. Установить composer
curl -sS https://getcomposer.org/installer | php

# 6. Установить зависимости
php composer.phar install --no-dev --optimize-autoloader

# 7. Создать папки
mkdir -p runtime/logs web/uploads/payments web/assets
chmod 755 runtime web/assets web/uploads

# 8. Миграции
php yii migrate

# 9. Создать админа (phpMyAdmin SQL)
# Сгенерировать хеш: php -r "echo password_hash('пароль', PASSWORD_DEFAULT);"
```

### В cPanel → Domains:
```
Document Root: /home/username/public_html/web
```

---

## 🔄 Обновление сайта (каждый раз)

### Локально:
```bash
git add .
git commit -m "Описание изменений"
git push origin main
```

### На хостинге:
```bash
ssh user@your-domain.com
cd ~/public_html
git pull origin main
composer install --no-dev  # если были изменения в composer.json
php yii migrate  # если есть новые миграции
rm -rf runtime/cache/*  # очистить кэш
```

---

## 📝 Частые команды

### Git:
```bash
git status                    # Проверить изменения
git log --oneline            # История коммитов
git diff                     # Посмотреть что изменилось
git reset --hard HEAD        # Откатить все изменения
git pull origin main         # Подтянуть изменения
```

### Composer:
```bash
composer install             # Установить зависимости
composer update             # Обновить зависимости
composer dump-autoload      # Обновить autoload
```

### Yii:
```bash
php yii migrate             # Запустить миграции
php yii migrate/down        # Откатить последнюю миграцию
php yii cache/flush-all     # Очистить весь кэш
php yii serve              # Запустить dev сервер
```

---

## 🆘 Проблемы

### Git конфликт:
```bash
git stash                    # Сохранить изменения
git pull origin main         # Подтянуть
git stash pop               # Вернуть изменения
```

### Сломалось после обновления:
```bash
git log --oneline           # Найти рабочий коммит
git reset --hard HASH       # Откатиться
```

### Не работает после pull:
```bash
rm -rf runtime/cache/*
rm -rf web/assets/*
chmod -R 755 runtime web/assets web/uploads
```

---

## 🔐 Безопасность

### ❌ НЕ коммитить в Git:
- `config/db.php` (пароли!)
- `web/uploads/*` (файлы клиентов)
- `/vendor` (установится на сервере)
- `/runtime/*` (временные файлы)

### ✅ Эти файлы уже в .gitignore

---

## 📞 Нужна полная инструкция?

**Смотрите [GITHUB_CPANEL_DEPLOY.md](GITHUB_CPANEL_DEPLOY.md)**
