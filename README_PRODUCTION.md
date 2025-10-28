# 🚀 Инструкция по установке на production

## ⚠️ БЕЗОПАСНОСТЬ ПРЕЖДЕ ВСЕГО

**Эта инструкция НЕ содержит паролей!**
Все пароли храните отдельно и НИКОГДА не коммитьте в Git.

---

## 📋 ШАГ 1: Сделать репозиторий Private

1. Откройте https://github.com/urbandima/sneaker_podzakaz
2. Settings → General → Danger Zone
3. "Change repository visibility" → **Make private**
4. Подтвердите

**Почему это важно:**
- Защита кода от копирования
- Скрытие структуры приложения
- Безопасность бизнес-логики

---

## 🔐 ШАГ 2: Изменить ВСЕ пароли в cPanel

### 2.1 MySQL пароль:

1. Войдите в cPanel: https://vh124.hoster.by:2083
2. MySQL® Databases → Current Users
3. Найдите: `sneakerh_username_order_user`
4. Change Password → Generate → Save
5. **ЗАПИШИТЕ НОВЫЙ ПАРОЛЬ** (не в Git!)

### 2.2 SSH пароль:

1. В cPanel → Password & Security
2. Change Password
3. **ЗАПИШИТЕ НОВЫЙ ПАРОЛЬ** (не в Git!)

---

## 📂 ШАГ 3: Подключиться к серверу

```bash
ssh sneakerh@vh124.hoster.by
# Введите НОВЫЙ пароль
```

---

## 📥 ШАГ 4: Клонировать репозиторий

```bash
# Перейти в папку
cd /home/sneakerh/zakaz.sneaker-head.by

# Если папка не пуста - очистить (ОСТОРОЖНО!)
# ls -la  # Проверьте что не удалите что-то важное
# rm -rf * .[^.]*

# Клонировать
git clone https://github.com/urbandima/sneaker_podzakaz.git .
```

**Если Git попросит логин/пароль:**
- Username: `urbandima`
- Password: используйте Personal Access Token
  - GitHub → Settings → Developer settings → Personal access tokens
  - Generate new token (classic)
  - Выберите: `repo` (полный доступ к приватным репозиториям)
  - Скопируйте токен

---

## ⚙️ ШАГ 5: Создать config/db.php

```bash
# Создать файл
cp config/db-example.php config/db.php

# Отредактировать
nano config/db.php
```

Вставьте с ВАШИМИ данными:

```php
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=sneakerh_username_order_management',
    'username' => 'sneakerh_username_order_user',
    'password' => 'ВАШ_НОВЫЙ_ПАРОЛЬ_MYSQL', // Тот который создали в шаге 2.1
    'charset' => 'utf8mb4',
];
```

Сохранить: `Ctrl+O`, Enter, `Ctrl+X`

---

## 📦 ШАГ 6: Установить зависимости

```bash
# Проверить composer
composer --version

# Если нет - установить
curl -sS https://getcomposer.org/installer | php

# Установить зависимости
composer install --no-dev --optimize-autoloader
# или если установили локально:
# php composer.phar install --no-dev --optimize-autoloader
```

---

## 📁 ШАГ 7: Создать папки

```bash
mkdir -p runtime/logs
mkdir -p web/uploads/payments
mkdir -p web/assets

chmod 755 runtime
chmod 755 web/assets
chmod 755 web/uploads
chmod 755 web/uploads/payments
```

---

## 🗄️ ШАГ 8: Запустить миграции

```bash
php yii migrate
```

Ответьте `yes` на все вопросы.

---

## 👤 ШАГ 9: Создать администратора

### Вариант 1: SQL через phpMyAdmin

1. Сгенерируйте хеш пароля:
```bash
php -r "echo password_hash('ваш_надежный_пароль', PASSWORD_DEFAULT);"
```

2. В phpMyAdmin выполните:
```sql
INSERT INTO `user` (username, password_hash, auth_key, role, status, created_at, updated_at)
VALUES (
    'admin',
    'ХЕSH_КОТОРЫЙ_ПОЛУЧИЛИ_ВЫШЕ',
    MD5(RAND()),
    'admin',
    10,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
);
```

### Вариант 2: Через Yii консоль (если создана команда)

```bash
php yii user/create-admin
```

---

## 🔧 ШАГ 10: Настроить DocumentRoot в cPanel

1. Войдите в cPanel: https://vh124.hoster.by:2083
2. Domains или Subdomains
3. Найдите: `zakaz.sneaker-head.by`
4. Manage → Document Root
5. Измените на:
   ```
   /home/sneakerh/zakaz.sneaker-head.by/web
   ```
6. Save

---

## 🔒 ШАГ 11: Настроить HTTPS (ОБЯЗАТЕЛЬНО!)

1. В cPanel → SSL/TLS Status
2. Найдите `zakaz.sneaker-head.by`
3. Run AutoSSL (бесплатно)
4. Дождитесь установки сертификата

Или установите Let's Encrypt вручную.

---

## ✅ ШАГ 12: Проверить сайт

Откройте: **https://zakaz.sneaker-head.by** (с HTTPS!)

Должна появиться страница входа.

Войдите:
- Login: `admin`
- Password: тот что создали в шаге 9

---

## 🔄 Обновление в будущем

### На локальной машине:

```bash
git add .
git commit -m "Описание изменений"
git push origin main
```

### На сервере:

```bash
ssh sneakerh@vh124.hoster.by
cd /home/sneakerh/zakaz.sneaker-head.by

git pull origin main
composer install --no-dev --optimize-autoloader
php yii migrate --interactive=0
rm -rf runtime/cache/* web/assets/*
```

---

## 🆘 Помощь

### Git спрашивает пароль при каждом pull?

Настройте SSH ключ:

```bash
# На сервере
ssh-keygen -t ed25519 -C "your-email@example.com"
cat ~/.ssh/id_ed25519.pub

# Скопируйте и добавьте в GitHub → Settings → SSH keys

# Измените remote на SSH
git remote set-url origin git@github.com:urbandima/sneaker_podzakaz.git
```

### Ошибка прав доступа?

```bash
chmod -R 755 runtime web/assets web/uploads
chown -R sneakerh:sneakerh *
```

### Не работает после обновления?

```bash
rm -rf runtime/cache/*
rm -rf web/assets/*
```

---

## 📊 Итоговый чеклист

- [ ] Репозиторий сделан Private
- [ ] MySQL пароль изменён
- [ ] SSH пароль изменён
- [ ] Репозиторий клонирован
- [ ] config/db.php создан с новым паролем
- [ ] Composer зависимости установлены
- [ ] Папки созданы с правами
- [ ] Миграции запущены
- [ ] Администратор создан
- [ ] DocumentRoot настроен на /web
- [ ] HTTPS включен
- [ ] Сайт работает

---

## 🎉 Готово!

После выполнения всех шагов ваш сайт будет:
- ✅ Безопасным (HTTPS, приватный репозиторий, новые пароли)
- ✅ Рабочим на zakaz.sneaker-head.by
- ✅ С 10 банками Беларуси
- ✅ С защитой от XSS, CSRF, SQL Injection
- ✅ С загрузкой файлов до 10МБ

**Успехов! 🚀**
