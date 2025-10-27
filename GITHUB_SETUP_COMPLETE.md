# ✅ Проект успешно выгружен на GitHub!

**Дата:** 27 октября 2025, 23:46  
**Репозиторий:** https://github.com/urbandima/sneaker_podzakaz

---

## 🎉 Что сделано

### 1. Инициализирован Git репозиторий
```bash
✅ git init
✅ git branch -m main
```

### 2. Добавлены все файлы
```bash
✅ git add .
✅ 96 файлов добавлено
✅ 18,658 строк кода
```

### 3. Создан первый коммит
```bash
✅ git commit -m "Initial commit"
```

### 4. Подключен GitHub репозиторий
```bash
✅ git remote add origin https://github.com/urbandima/sneaker_podzakaz.git
```

### 5. Разрешен конфликт слияния
```bash
✅ git pull origin main --allow-unrelated-histories
✅ Конфликт в README.md разрешен
✅ Merge коммит создан
```

### 6. Код отправлен на GitHub
```bash
✅ git push -u origin main
✅ 116 объектов отправлено
✅ 204 KB данных загружено
```

---

## 📂 Что загружено на GitHub

### Основные файлы проекта:
- ✅ Все контроллеры (Admin, Order, Site)
- ✅ Все модели (User, Order, CompanySettings)
- ✅ Все views (admin, order, site, layouts)
- ✅ Миграции базы данных (6 файлов)
- ✅ Конфигурация (web.php, db.php, params.php)
- ✅ Composer зависимости (composer.json)

### Дополнительные файлы:
- ✅ Лендинг (`views/site/index.php`)
- ✅ Договор оферты (`views/site/offer-agreement.php`)
- ✅ Favicon (favicon.ico)
- ✅ GitHub Actions workflow (`.github/workflows/deploy.yml`)
- ✅ Документация (40+ MD файлов)

### Веб-файлы:
- ✅ `.htaccess` (для Apache)
- ✅ `web/index.php` (точка входа)
- ✅ CSS стили (`web/css/site.css`)

---

## 🚀 Следующие шаги: Настройка автоматического деплоя

### Шаг 1: Перейти в настройки репозитория

1. Открыть https://github.com/urbandima/sneaker_podzakaz
2. Перейти в **Settings** (⚙️ вверху)
3. В левом меню выбрать **Secrets and variables** → **Actions**
4. Нажать **New repository secret**

---

### Шаг 2: Добавить секреты для автодеплоя

Добавьте **4 секрета** (один за другим):

#### Секрет 1: SSH_HOST
- **Name:** `SSH_HOST`
- **Value:** Адрес вашего сервера
  ```
  Пример: your-server.com
  Или IP: 123.45.67.89
  ```

#### Секрет 2: SSH_USERNAME
- **Name:** `SSH_USERNAME`
- **Value:** Имя пользователя SSH
  ```
  Пример: root
  Или: username
  ```

#### Секрет 3: SSH_PASSWORD
- **Name:** `SSH_PASSWORD`
- **Value:** Пароль от SSH
  ```
  Ваш SSH пароль
  ⚠️ Никому не показывайте!
  ```

#### Секрет 4: SSH_PATH
- **Name:** `SSH_PATH`
- **Value:** Путь к директории на сервере
  ```
  Пример: /var/www/html
  Или: /home/username/public_html
  ```

---

### Шаг 3: Проверка автодеплоя

После добавления секретов:

1. Вернитесь на главную страницу репозитория
2. Перейдите во вкладку **Actions**
3. При следующем `git push` автоматически запустится деплой
4. Вы увидите процесс выгрузки на сервер в реальном времени

---

## 🔄 Рабочий процесс после настройки

Теперь каждый раз, когда вы делаете изменения:

```bash
# 1. Редактируете код локально
# 2. Сохраняете изменения

# 3. Добавляете в Git
git add .

# 4. Делаете коммит
git commit -m "Описание изменений"

# 5. Отправляете на GitHub
git push

# 6. GitHub автоматически выгружает на сервер! 🚀
```

---

## ⚙️ Настройка на сервере (одноразово)

После первого push нужно один раз настроить сервер:

### 1. Подключиться к серверу по SSH

```bash
ssh username@your-server.com
# Введите пароль
```

### 2. Перейти в директорию проекта

```bash
cd /var/www/html
# Или cd /home/username/public_html
```

### 3. Инициализировать Git (если еще не сделано)

```bash
git init
git remote add origin https://github.com/urbandima/sneaker_podzakaz.git
git pull origin main
```

### 4. Установить зависимости Composer

```bash
# Установить Composer (если не установлен)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Установить зависимости проекта
composer install --no-dev --optimize-autoloader
```

### 5. Настроить базу данных

Отредактировать `config/db.php`:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=YOUR_DATABASE',
    'username' => 'YOUR_USERNAME',
    'password' => 'YOUR_PASSWORD',
    'charset' => 'utf8',
];
```

Создать базу данных:

```sql
CREATE DATABASE your_database CHARACTER SET utf8 COLLATE utf8_general_ci;
```

Применить миграции:

```bash
php yii migrate --interactive=0
```

### 6. Установить права на папки

```bash
# Права на запись
chmod -R 777 runtime
chmod -R 777 web/assets
chmod -R 777 web/uploads

# Владелец (замените на вашего пользователя веб-сервера)
chown -R www-data:www-data /var/www/html
```

### 7. Настроить веб-сервер

#### Для Apache:

Убедитесь, что DocumentRoot указывает на `/var/www/html/web`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/html/web
    
    <Directory /var/www/html/web>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Перезапустить Apache:

```bash
sudo systemctl restart apache2
```

#### Для Nginx:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

Перезапустить Nginx:

```bash
sudo systemctl restart nginx
```

---

## 🎯 Проверка работы

1. Откройте браузер
2. Перейдите на ваш домен: `http://your-domain.com`
3. Должна открыться главная страница с лендингом
4. Проверьте админку: `http://your-domain.com/admin`

---

## 📝 Полезные команды Git

```bash
# Проверить статус
git status

# Посмотреть изменения
git diff

# Посмотреть историю
git log --oneline

# Отменить последний коммит (сохранив изменения)
git reset --soft HEAD~1

# Получить последние изменения с GitHub
git pull origin main

# Отправить изменения на GitHub
git push origin main

# Посмотреть удаленные репозитории
git remote -v

# Посмотреть все ветки
git branch -a
```

---

## 🔒 Безопасность

### ⚠️ Важно исключить из репозитория:

Эти файлы уже исключены через `.gitignore`:

- ✅ `config/db.php` - пароли БД (НЕ загружен на GitHub)
- ✅ `runtime/*` - временные файлы
- ✅ `web/uploads/*` - загруженные файлы пользователей
- ✅ `vendor/*` - зависимости Composer

### 🔐 Рекомендации:

1. Использовать **Private** репозиторий (рекомендуется)
2. Настроить HTTPS на сервере (Let's Encrypt)
3. Регулярно делать бэкапы базы данных
4. Не хранить пароли в коде (использовать переменные окружения)

---

## 📊 Статистика проекта

```
📁 Файлов загружено: 96
📝 Строк кода: 18,658
💾 Размер: 204 KB
🌳 Ветка: main
🔗 Репозиторий: github.com/urbandima/sneaker_podzakaz
```

---

## ✅ Готово!

Ваш проект **успешно загружен на GitHub** и готов к автоматической синхронизации с сервером!

**Следующий шаг:** Добавьте секреты в GitHub (см. выше) и сделайте тестовый push.

---

**Дата:** 27 октября 2025, 23:46  
**Статус:** ✅ Проект на GitHub, готов к настройке деплоя  
**Ссылка:** https://github.com/urbandima/sneaker_podzakaz
