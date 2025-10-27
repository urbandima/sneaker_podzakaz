# 🧪 Локальное тестирование проекта

**Время установки:** 5-10 минут  
**Требования:** PHP 7.4+, MySQL, Composer

---

## ⚡ Быстрый запуск

### Шаг 1: Проверить требования (1 минута)

```bash
# Проверить PHP
php -v
# Должно быть: PHP 7.4 или выше

# Проверить Composer
composer --version

# Проверить MySQL
mysql --version
```

---

### Шаг 2: Создать базу данных (2 минуты)

```bash
# Запустить MySQL
mysql -u root -p

# В консоли MySQL:
CREATE DATABASE splitwise_test CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'splitwise_user'@'localhost' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON splitwise_test.* TO 'splitwise_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### Шаг 3: Настроить подключение к БД (1 минута)

```bash
cd /Users/user/CascadeProjects/splitwise

# Создать config/db.php (если еще нет)
cat > config/db.php << 'EOF'
<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=splitwise_test',
    'username' => 'splitwise_user',
    'password' => 'test_password',
    'charset' => 'utf8',
];
EOF
```

---

### Шаг 4: Установить зависимости (2 минуты)

```bash
cd /Users/user/CascadeProjects/splitwise

# Установить зависимости Composer
composer install
```

---

### Шаг 5: Применить миграции (1 минута)

```bash
cd /Users/user/CascadeProjects/splitwise

# Применить миграции
php yii migrate --interactive=0
```

Должно вывести:
```
*** applying m241023_181500_create_users_table
    > create table users ... done
*** applying m241023_181600_create_orders_table
    > create table orders ... done
...
6 migrations applied successfully.
```

---

### Шаг 6: Создать права на папки (30 секунд)

```bash
cd /Users/user/CascadeProjects/splitwise

chmod -R 777 runtime
chmod -R 777 web/assets
chmod -R 777 web/uploads
```

---

### Шаг 7: Запустить локальный сервер (30 секунд)

```bash
cd /Users/user/CascadeProjects/splitwise

# Запустить встроенный PHP сервер
php yii serve --port=8080
```

Должно вывести:
```
Server started on http://localhost:8080/
Document root is "/Users/user/CascadeProjects/splitwise/web"
Press Ctrl-C to stop.
```

---

### Шаг 8: Открыть в браузере ✅

Открыть:
```
http://localhost:8080/
```

Должна открыться главная страница с лендингом! 🎉

---

## 🔐 Доступ к админке

### Создать администратора

```bash
# В новом терминале
cd /Users/user/CascadeProjects/splitwise

# Создать пользователя через консоль
php yii
```

Или вручную через MySQL:

```sql
USE splitwise_test;

INSERT INTO users (username, password_hash, email, role, status, created_at, updated_at) 
VALUES (
    'admin',
    '$2y$13$QZ8Z3Z3Z3Z3Z3Z3Z3Z3Z3uO', -- пароль: admin123 (нужно будет сгенерировать хеш)
    'admin@test.com',
    'admin',
    10,
    NOW(),
    NOW()
);
```

### Открыть админку

```
http://localhost:8080/admin
```

Логин: `admin`  
Пароль: `admin123`

---

## 📧 Настроить Email для тестирования

### Вариант 1: Использовать файлы (по умолчанию)

В `config/web.php` уже настроено:

```php
'mailer' => [
    'class' => 'yii\swiftmailer\Mailer',
    'useFileTransport' => true, // Email сохраняются в runtime/mail/
],
```

Письма будут сохраняться в:
```
/Users/user/CascadeProjects/splitwise/runtime/mail/
```

### Вариант 2: Использовать Mailtrap.io (бесплатно)

1. Зарегистрироваться на https://mailtrap.io/
2. Получить SMTP данные
3. Изменить `config/web.php`:

```php
'mailer' => [
    'class' => 'yii\swiftmailer\Mailer',
    'useFileTransport' => false,
    'transport' => [
        'class' => 'Swift_SmtpTransport',
        'host' => 'sandbox.smtp.mailtrap.io',
        'username' => 'your_username',
        'password' => 'your_password',
        'port' => '2525',
        'encryption' => 'tls',
    ],
],
```

---

## 🧪 Тестирование функций

### 1. Проверить главную страницу

```
http://localhost:8080/
```

Должен открыться лендинг с:
- Заголовком "Закажем любую пару оригинальной обуви для вас"
- Кнопкой "Заказать в Telegram"
- Секциями "Как это работает"

### 2. Проверить админку

```
http://localhost:8080/admin
```

- Авторизоваться
- Должна открыться панель с заказами

### 3. Создать тестовый заказ

В админке:
1. Нажать "Создать заказ"
2. Заполнить:
   - ФИО клиента: Иван Иванов
   - Email: test@test.com
   - Телефон: +375291234567
   - Товар: Nike Air Max
   - Размер: 42
   - Цена: 500 BYN
3. Сохранить

### 4. Проверить страницу клиента

Скопировать публичную ссылку из админки, например:
```
http://localhost:8080/order/view?token=abc123def456
```

Должна открыться страница с:
- ✅ Информацией о заказе
- ✅ Реквизитами для оплаты
- ✅ Кнопками копирования
- ✅ Формой загрузки чека
- ✅ Кнопкой поддержки Telegram

### 5. Проверить загрузку чека

На странице клиента:
1. Выбрать файл (любое изображение)
2. Согласиться с офертой
3. Нажать "Отправить подтверждение"
4. Должно появиться сообщение об успехе

### 6. Проверить Email

Проверить папку:
```bash
ls -la runtime/mail/
cat runtime/mail/*.eml
```

Должны быть письма:
- Уведомление клиенту о создании заказа
- Уведомление менеджеру о загрузке чека

---

## 🎨 Проверить дизайн

### Главная страница
- ✅ Минималистичный стиль
- ✅ Белый фон
- ✅ Черные акценты
- ✅ Favicon отображается

### Страница клиента
- ✅ Чистый дизайн
- ✅ Sticky реквизиты
- ✅ Кнопки копирования работают
- ✅ Telegram кнопка в хедере
- ✅ Модальное окно истории

### Адаптивность
- ✅ Открыть на мобильном (через dev tools)
- ✅ Проверить все элементы

---

## 🐛 Отладка

### Если ошибка "Database connection failed"

Проверить `config/db.php`:
```bash
cat config/db.php
```

Проверить, что БД создана:
```bash
mysql -u root -p -e "SHOW DATABASES LIKE 'splitwise_test';"
```

### Если ошибка 404

Убедиться, что запущен сервер:
```bash
php yii serve --port=8080
```

И открываете правильный URL:
```
http://localhost:8080/
```

### Если ошибка "Permission denied"

```bash
chmod -R 777 runtime
chmod -R 777 web/assets
chmod -R 777 web/uploads
```

### Посмотреть логи

```bash
tail -f runtime/logs/app.log
```

---

## 📊 Тестовые данные

### Создать тестовые заказы

```bash
cd /Users/user/CascadeProjects/splitwise

# Создать несколько заказов для тестирования
php yii
```

Или через MySQL:

```sql
USE splitwise_test;

INSERT INTO orders (client_name, client_email, client_phone, status, created_at, updated_at, public_token)
VALUES 
('Тест 1', 'test1@test.com', '+375291111111', 1, NOW(), NOW(), MD5(RAND())),
('Тест 2', 'test2@test.com', '+375292222222', 2, NOW(), NOW(), MD5(RAND())),
('Тест 3', 'test3@test.com', '+375293333333', 3, NOW(), NOW(), MD5(RAND()));
```

---

## ✅ Чек-лист тестирования

### Главная страница
- [ ] Открывается по http://localhost:8080/
- [ ] Лендинг отображается корректно
- [ ] Favicon отображается
- [ ] Кнопка Telegram работает
- [ ] Адаптивность работает

### Админка
- [ ] Открывается /admin
- [ ] Авторизация работает
- [ ] Список заказов отображается
- [ ] Можно создать заказ
- [ ] Можно редактировать заказ
- [ ] Статистика отображается
- [ ] Настройки компании работают

### Страница клиента
- [ ] Открывается по токену
- [ ] Информация о заказе отображается
- [ ] Реквизиты отображаются
- [ ] Кнопки копирования работают
- [ ] Форма загрузки чека работает
- [ ] Telegram кнопка в хедере
- [ ] История изменений работает
- [ ] Cookie баннер появляется
- [ ] Адаптивность работает

### Email
- [ ] Письмо клиенту создается
- [ ] Письмо менеджеру создается
- [ ] Ссылки в письмах правильные

### База данных
- [ ] Миграции применены
- [ ] Таблицы созданы
- [ ] Данные сохраняются
- [ ] История заказов ведется

---

## 🚀 После успешного тестирования

Если все работает локально, можно развертывать на production!

### Отправить изменения на GitHub

```bash
cd /Users/user/CascadeProjects/splitwise

git add .
git commit -m "Tested locally, ready for production"
git push origin main
```

### Развернуть на сервере

Следовать инструкции из **SERVER_DEPLOY_INSTRUCTIONS.md**

---

## 🛑 Остановить локальный сервер

В терминале, где запущен сервер:
```
Ctrl + C
```

---

## 📝 Полезные команды

```bash
# Запустить сервер
php yii serve --port=8080

# Остановить сервер
Ctrl + C

# Очистить кеш
rm -rf runtime/cache/*

# Посмотреть логи
tail -f runtime/logs/app.log

# Применить миграции
php yii migrate

# Откатить миграции
php yii migrate/down

# Пересоздать БД
php yii migrate/fresh

# Проверить роуты
php yii help
```

---

## 🎯 Готово!

После локального тестирования вы будете уверены, что проект работает, и можете смело развертывать на production сервере!

**Время тестирования:** 10-15 минут  
**Результат:** Полностью проверенное приложение готовое к production ✅
