# 🚀 Инструкция по настройке автоматической выгрузки на хостинг через GitHub

## 📋 Что нужно сделать

### 1. Инициализация Git репозитория

```bash
cd /Users/user/CascadeProjects/splitwise

# Инициализировать репозиторий (если еще не сделано)
git init

# Добавить все файлы
git add .

# Сделать первый коммит
git commit -m "Initial commit: Splitwise project with landing page"
```

---

### 2. Создание репозитория на GitHub

1. Перейти на https://github.com/new
2. Создать новый репозиторий:
   - **Название:** `splitwise` (или любое другое)
   - **Описание:** "Splitwise - Order management system with landing page"
   - **Видимость:** Private (рекомендуется для безопасности)
   - **НЕ добавлять:** README, .gitignore, license (у нас уже есть)

3. После создания скопировать URL репозитория:
   ```
   https://github.com/YOUR_USERNAME/splitwise.git
   ```

---

### 3. Подключение локального репозитория к GitHub

```bash
# Добавить удаленный репозиторий
git remote add origin https://github.com/YOUR_USERNAME/splitwise.git

# Проверить, что добавлен
git remote -v

# Отправить код на GitHub
git branch -M main
git push -u origin main
```

---

### 4. Настройка автоматической выгрузки на хостинг

#### Вариант A: GitHub Actions (рекомендуется)

1. Создать файл `.github/workflows/deploy.yml` (уже создан ниже)

2. Добавить секреты в GitHub:
   - Перейти в Settings → Secrets and variables → Actions
   - Добавить следующие секреты:
     - `SSH_HOST` - адрес вашего хостинга (например: `your-server.com`)
     - `SSH_USERNAME` - имя пользователя SSH
     - `SSH_PASSWORD` - пароль SSH (или лучше использовать SSH ключ)
     - `SSH_PATH` - путь к директории на хостинге (например: `/var/www/html`)

3. После push в main ветку, код автоматически выгрузится на хостинг

---

#### Вариант B: Git hook на хостинге

Если хостинг поддерживает Git:

```bash
# На хостинге
cd /var/www/html
git init
git remote add origin https://github.com/YOUR_USERNAME/splitwise.git

# Создать скрипт для автоматического обновления
cat > /var/www/html/update.sh << 'EOF'
#!/bin/bash
cd /var/www/html
git pull origin main
composer install --no-dev
php yii migrate --interactive=0
EOF

chmod +x /var/www/html/update.sh
```

Затем настроить Webhook в GitHub:
- Settings → Webhooks → Add webhook
- Payload URL: `https://your-server.com/webhook.php`
- Content type: `application/json`
- Events: Just the push event

---

#### Вариант C: FTP/SFTP деплой

```bash
# Установить lftp
brew install lftp  # для Mac
# или
sudo apt-get install lftp  # для Linux

# Создать скрипт деплоя
cat > deploy.sh << 'EOF'
#!/bin/bash
lftp -u USERNAME,PASSWORD sftp://your-server.com << FTPEOF
mirror -R --delete --verbose /Users/user/CascadeProjects/splitwise /var/www/html
bye
FTPEOF
EOF

chmod +x deploy.sh

# Запускать вручную после каждого коммита
./deploy.sh
```

---

### 5. Структура проекта для деплоя

Убедитесь, что на хостинге:

```
/var/www/html/
├── assets/
├── commands/
├── components/
├── config/
├── controllers/
├── migrations/
├── models/
├── runtime/          ← должна быть доступна на запись
├── vendor/           ← запустить composer install
├── views/
├── web/
│   ├── assets/       ← должна быть доступна на запись
│   ├── uploads/      ← должна быть доступна на запись
│   ├── css/
│   └── index.php
├── .htaccess
└── composer.json
```

---

### 6. Настройка прав доступа на хостинге

```bash
# Установить владельца (замените www-data на вашего пользователя)
sudo chown -R www-data:www-data /var/www/html

# Права на директории
sudo chmod -R 755 /var/www/html

# Права на папки для записи
sudo chmod -R 777 /var/www/html/runtime
sudo chmod -R 777 /var/www/html/web/assets
sudo chmod -R 777 /var/www/html/web/uploads
```

---

### 7. Конфигурация веб-сервера

#### Apache (.htaccess уже настроен)

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

#### Nginx

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

---

### 8. Установка зависимостей на хостинге

```bash
# Перейти в корневую директорию проекта
cd /var/www/html

# Установить Composer (если еще не установлен)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Установить зависимости
composer install --no-dev --optimize-autoloader

# Применить миграции базы данных
php yii migrate --interactive=0
```

---

### 9. Настройка базы данных на хостинге

Отредактируйте `config/db.php`:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=your_database',
    'username' => 'your_username',
    'password' => 'your_password',
    'charset' => 'utf8',
];
```

Создайте базу данных:

```sql
CREATE DATABASE your_database CHARACTER SET utf8 COLLATE utf8_general_ci;
```

---

### 10. Проверка работоспособности

```bash
# Проверить логи ошибок
tail -f /var/www/html/runtime/logs/app.log

# Проверить статус веб-сервера
sudo systemctl status apache2  # для Apache
sudo systemctl status nginx    # для Nginx

# Проверить PHP
php -v

# Проверить права
ls -la /var/www/html/runtime
ls -la /var/www/html/web/assets
```

---

### 11. Рабочий процесс после настройки

```bash
# 1. Вносите изменения в код локально
# 2. Тестируете локально
# 3. Коммитите изменения
git add .
git commit -m "Описание изменений"

# 4. Отправляете на GitHub
git push origin main

# 5. GitHub автоматически выгружает на хостинг (если настроен GitHub Actions)
# или запускаете скрипт деплоя вручную
```

---

## 📝 Полезные команды Git

```bash
# Проверить статус
git status

# Добавить конкретный файл
git add filename.php

# Добавить все измененные файлы
git add .

# Коммит с сообщением
git commit -m "Описание изменений"

# Отправить на GitHub
git push origin main

# Получить последние изменения с GitHub
git pull origin main

# Посмотреть историю коммитов
git log --oneline

# Отменить последний коммит (сохранив изменения)
git reset --soft HEAD~1

# Посмотреть изменения в файле
git diff filename.php
```

---

## 🔒 Безопасность

### Важно исключить из репозитория:

1. **config/db.php** - содержит пароли БД
2. **web/uploads/** - загруженные файлы пользователей
3. **runtime/** - временные файлы
4. **vendor/** - зависимости (устанавливаются через composer)
5. **.env** файлы с секретами

Это уже настроено в `.gitignore`

### Рекомендации:

1. Использовать переменные окружения для секретов
2. Настроить HTTPS на хостинге (Let's Encrypt)
3. Регулярно делать бэкапы базы данных
4. Использовать private репозиторий на GitHub

---

## 🆘 Решение проблем

### Ошибка: Permission denied

```bash
sudo chmod -R 777 /var/www/html/runtime
sudo chmod -R 777 /var/www/html/web/assets
```

### Ошибка: 500 Internal Server Error

```bash
# Проверить логи
tail -f /var/www/html/runtime/logs/app.log
tail -f /var/log/apache2/error.log  # или /var/log/nginx/error.log
```

### Ошибка: Cannot connect to database

```bash
# Проверить настройки в config/db.php
# Проверить, что MySQL запущен
sudo systemctl status mysql
```

---

## ✅ Готово!

После выполнения всех шагов:
- ✅ Код находится на GitHub
- ✅ Автоматическая выгрузка на хостинг настроена
- ✅ Сайт доступен по вашему домену
- ✅ Все изменения синхронизируются автоматически

**Дата:** 27 октября 2025  
**Статус:** Готово к развертыванию
