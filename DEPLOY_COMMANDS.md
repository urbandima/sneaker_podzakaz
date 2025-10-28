# 🚀 Команды для деплоя zakaz.sneaker-head.by

## ✅ ВСЕ ГОТОВО! Следуйте инструкциям ниже:

---

## 📋 Шаг 1: Push в GitHub (на вашей машине)

```bash
cd /Users/user/CascadeProjects/splitwise

# Создать config/db.php локально для разработки
cp config/db-example.php config/db.php
nano config/db.php  # Укажите ваши локальные данные MySQL

# Инициализировать Git (если еще не сделано)
git init

# Добавить все файлы
git add .

# Первый коммит
git commit -m "Initial commit: Order Management System for zakaz.sneaker-head.by"

# Связать с вашим репозиторием
git remote add origin https://github.com/urbandima/sneaker_podzakaz.git

# Push в GitHub
git branch -M main
git push -u origin main
```

**Если Git спросит логин/пароль:**
- Username: `urbandima`
- Password: используйте **Personal Access Token** (создать в GitHub Settings → Developer settings → Personal access tokens)

---

## 🖥️  Шаг 2: Подключиться к серверу

```bash
ssh sneakerh@vh124.hoster.by
# Пароль: oXaefoh0
```

---

## 📂 Шаг 3: Перейти в папку zakaz

```bash
cd /home/sneakerh/zakaz.sneaker-head.by
```

**Если папки не существует - создать:**
```bash
mkdir -p /home/sneakerh/zakaz.sneaker-head.by
cd /home/sneakerh/zakaz.sneaker-head.by
```

---

## 📥 Шаг 4: Клонировать репозиторий

```bash
git clone https://github.com/urbandima/sneaker_podzakaz.git .
```

**Обратите внимание на точку в конце!** Она означает "в текущую папку".

**Если Git спросит логин/пароль:**
- Username: `urbandima`
- Password: ваш Personal Access Token из GitHub

**Совет:** Чтобы не вводить пароль каждый раз:
```bash
# Сгенерировать SSH ключ на сервере
ssh-keygen -t ed25519 -C "urban.dima@example.com"

# Показать публичный ключ
cat ~/.ssh/id_ed25519.pub

# Скопировать и добавить в GitHub → Settings → SSH keys

# Затем использовать SSH URL вместо HTTPS:
git remote set-url origin git@github.com:urbandima/sneaker_podzakaz.git
```

---

## ⚙️  Шаг 5: Запустить первичную установку

```bash
# Сделать скрипт исполняемым
chmod +x initial-setup.sh

# Запустить установку
./initial-setup.sh
```

**Скрипт автоматически:**
1. ✅ Создаст `config/db.php` с данными БД
2. ✅ Сгенерирует уникальный `cookieValidationKey`
3. ✅ Установит все зависимости через Composer
4. ✅ Создаст необходимые папки с правами
5. ✅ Запустит миграции базы данных
6. ✅ Предложит создать администратора
7. ✅ Очистит кэш

**При создании администратора введите:**
- Username: `admin` (или любой)
- Password: ваш надежный пароль

---

## 🔧 Шаг 6: Настроить DocumentRoot в cPanel

1. **Войти в cPanel:** https://vh124.hoster.by:2083
   - Username: `sneakerh`
   - Password: `oXaefoh0`

2. **Найти:** "Subdomains" или "Domains"

3. **Найти:** `zakaz.sneaker-head.by`

4. **Нажать:** "Manage" или "Edit"

5. **Изменить Document Root на:**
   ```
   /home/sneakerh/zakaz.sneaker-head.by/web
   ```
   
   **ВАЖНО:** Путь должен заканчиваться на `/web`

6. **Сохранить**

---

## ✅ Шаг 7: Проверить сайт

Откройте в браузере:
- **Главная:** http://zakaz.sneaker-head.by
- **Админка:** http://zakaz.sneaker-head.by/login

Войдите с учетными данными администратора, которые создали.

---

## 🔄 Обновление сайта в будущем

### На вашей машине (когда делаете изменения):

```bash
cd /Users/user/CascadeProjects/splitwise

# Добавить изменения
git add .

# Коммит с описанием
git commit -m "Добавил новую функцию X"

# Push в GitHub
git push origin main
```

### На сервере (обновить сайт):

```bash
ssh sneakerh@vh124.hoster.by
cd /home/sneakerh/zakaz.sneaker-head.by

# Запустить скрипт обновления
./update.sh
```

**Скрипт обновления автоматически:**
- Подтянет изменения из GitHub
- Обновит зависимости
- Применит новые миграции
- Очистит кэш

---

## 🆘 Решение проблем

### Проблема: Git не найден на сервере

```bash
# Проверить версию
git --version

# Если нет - запросить установку у хостера
# Или использовать альтернативный метод через FTP
```

### Проблема: Permission denied при выполнении скриптов

```bash
chmod +x initial-setup.sh
chmod +x update.sh
chmod -R 755 runtime web/assets web/uploads
```

### Проблема: Composer не найден

```bash
# Скачать локально
curl -sS https://getcomposer.org/installer | php

# Использовать
php composer.phar install --no-dev --optimize-autoloader
```

### Проблема: Ошибка подключения к БД

Проверьте что:
1. БД создана в cPanel: `sneakerh_username_order_management`
2. Пользователь создан: `sneakerh_username_order_user`
3. Пользователь назначен к БД с ALL PRIVILEGES
4. В `config/db.php` правильные данные

### Проблема: 404 на всех страницах

Проверьте:
1. DocumentRoot указывает на `/home/sneakerh/zakaz.sneaker-head.by/web`
2. Файл `.htaccess` существует в папке `web/`
3. mod_rewrite включен на сервере

---

## 📊 Полезные команды

```bash
# Подключиться к серверу
ssh sneakerh@vh124.hoster.by

# Перейти в папку проекта
cd /home/sneakerh/zakaz.sneaker-head.by

# Посмотреть статус Git
git status

# Посмотреть логи ошибок
tail -n 50 runtime/logs/app.log

# Очистить кэш вручную
rm -rf runtime/cache/* web/assets/*

# Проверить права
ls -la runtime web/assets web/uploads

# Посмотреть текущий коммит
git log --oneline -5

# Откатиться к предыдущей версии
git log --oneline  # найти хеш коммита
git reset --hard HASH_КОММИТА
```

---

## 🎉 Готово!

После выполнения всех шагов ваш сайт будет работать на:
**http://zakaz.sneaker-head.by**

Система включает:
- ✅ 10 банков Беларуси с инструкциями
- ✅ Загрузка чеков до 10МБ
- ✅ Кнопка инструкции у реквизитов
- ✅ Ссылка на политику конфиденциальности
- ✅ Mobile-first дизайн
- ✅ Безопасность (CSRF, XSS, SQL Injection)

**Успехов! 🚀**
