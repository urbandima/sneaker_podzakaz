# Инструкция по установке и запуску

## Вариант 1: Полная установка с Composer

### 1. Установка Composer (если нет)

```bash
# macOS
brew install composer

# Или через официальный установщик
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Установка зависимостей

```bash
cd /Users/user/CascadeProjects/splitwise
composer install
```

### 3. Настройка базы данных

#### Создание БД в MySQL

```sql
CREATE DATABASE order_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'order_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON order_management.* TO 'order_user'@'localhost';
FLUSH PRIVILEGES;
```

#### Настройка подключения

Отредактируйте файл `config/db.php`:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=order_management',
    'username' => 'order_user',  // Ваш пользователь
    'password' => 'password',     // Ваш пароль
    'charset' => 'utf8mb4',
];
```

### 4. Применение миграций

```bash
chmod +x yii
php yii migrate
```

### 5. Запуск сервера

```bash
php yii serve --port=8080
```

Откройте в браузере: http://localhost:8080

---

## Вариант 2: Упрощенный запуск (для тестирования без Composer)

### 1. Установка только Yii2 через Composer

```bash
cd /Users/user/CascadeProjects/splitwise
composer require "yiisoft/yii2:~2.0.45"
composer require "yiisoft/yii2-bootstrap5:~2.0.2"
```

### 2. Или скачать архив Yii2

Скачайте с https://www.yiiframework.com/download и распакуйте в папку `vendor`

### 3. Настройка БД и миграции (см. Вариант 1)

---

## Вариант 3: Использование SQLite (без MySQL)

### 1. Измените `config/db.php`:

```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'sqlite:' . __DIR__ . '/../runtime/database.sqlite',
    'charset' => 'utf8',
];
```

### 2. Примените миграции:

```bash
php yii migrate
```

---

## Тестовые данные

После применения миграций будут созданы тестовые пользователи:

| Логин   | Пароль     | Роль           |
|---------|------------|----------------|
| admin   | admin123   | Администратор  |
| manager | manager123 | Менеджер       |
| logist  | logist123  | Логист         |

---

## Структура проекта

```
splitwise/
├── assets/          # Asset bundles
├── config/          # Конфигурационные файлы
├── controllers/     # Контроллеры
├── mail/           # Email шаблоны
├── migrations/     # Миграции БД
├── models/         # Модели
├── runtime/        # Временные файлы
├── views/          # Представления
│   ├── admin/      # Админ-панель
│   ├── layouts/    # Layouts
│   ├── order/      # Публичная часть
│   └── site/       # Авторизация
├── web/            # Публичная директория
│   ├── css/
│   ├── uploads/    # Загруженные файлы
│   └── index.php   # Entry point
└── yii             # Console команды
```

---

## Решение проблем

### Composer не установлен

Установите Composer: https://getcomposer.org/download/

### MySQL не установлен

```bash
brew install mysql
brew services start mysql
```

### Ошибки прав доступа

```bash
chmod -R 777 runtime web/assets web/uploads
```

### Порт 8080 занят

Используйте другой порт:
```bash
php yii serve --port=9090
```

---

## Контакты и поддержка

По всем вопросам обращайтесь к документации Yii2:
- https://www.yiiframework.com/doc/guide/2.0/ru
- https://github.com/yiisoft/yii2
