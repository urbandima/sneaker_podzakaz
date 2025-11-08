# 🔧 ИСПРАВЛЕНИЕ ДЛЯ PHP 7.4

## 🔴 ПРОБЛЕМА
Сервер использует PHP 7.4.33, а проект собран под PHP 8.0+

## ✅ РЕШЕНИЯ

### ВАРИАНТ 1: Переключить PHP на 8.0+ (РЕКОМЕНДУЕТСЯ)

#### Шаг 1: Проверь доступные версии PHP
```bash
ls -la /opt/cpanel/ea-php*/root/usr/bin/php
```

Ищи строки типа:
- `/opt/cpanel/ea-php80/root/usr/bin/php`
- `/opt/cpanel/ea-php81/root/usr/bin/php`
- `/opt/cpanel/ea-php82/root/usr/bin/php`

#### Шаг 2A: Создать постоянный алиас
```bash
echo "alias php='/opt/cpanel/ea-php80/root/usr/bin/php'" >> ~/.bashrc
echo "alias composer='php /home/sneakerh/composer.phar'" >> ~/.bashrc
source ~/.bashrc

# Проверь версию
php -v
# Должно показать: PHP 8.0.x
```

#### Шаг 2B: Или использовать полный путь
```bash
# Установка зависимостей
/opt/cpanel/ea-php80/root/usr/bin/php composer.phar install --no-dev --optimize-autoloader

# Миграции
/opt/cpanel/ea-php80/root/usr/bin/php yii migrate --interactive=0
```

#### Шаг 2C: Через cPanel (самый простой)
1. Войди в **cPanel**
2. Найди **"MultiPHP Manager"**
3. Выбери домен `zakaz.sneaker-head.by`
4. Установи **PHP 8.0** или **PHP 8.1**
5. Нажми **"Apply"**
6. Вернись в SSH и проверь: `php -v`

---

### ВАРИАНТ 2: Адаптировать код под PHP 7.4

Если переключить PHP невозможно, я уже изменил зависимости:

#### Что изменено:
1. **composer.json:**
   - PHP требование: `>=7.4.0` (было `>=8.0.0`)
   - Mailer: `yii2-swiftmailer` (вместо `yii2-symfonymailer`)
   - PhpSpreadsheet: `^1.23` (вместо `^1.29`)

2. **config/web.php:**
   - Класс mailer: `yii\swiftmailer\Mailer`

#### Выполни на своем Mac:
```bash
cd /Users/user/CascadeProjects/splitwise

# Удали старый lock
rm composer.lock

# Пересобери под PHP 7.4
composer update

# Закоммить
git add .
git commit -m "fix: адаптация под PHP 7.4"
git push origin main
```

#### На сервере:
```bash
cd /home/sneakerh/zakaz.sneaker-head.by

# Обнови код
git pull origin main

# Удали старые файлы
rm -rf vendor/ composer.lock

# Установи под PHP 7.4
php composer.phar install --no-dev --optimize-autoloader

# Миграции
php yii migrate --interactive=0

# Права
chmod 777 runtime/ web/uploads/ web/assets/
```

---

## 🎯 РЕКОМЕНДАЦИЯ

**Используй ВАРИАНТ 1** (переключить PHP на 8.0+):
- ✅ Современная версия PHP
- ✅ Лучшая производительность
- ✅ Безопаснее
- ✅ Больше возможностей

**ВАРИАНТ 2** только если:
- ❌ Нет доступа к cPanel
- ❌ На хостинге нет PHP 8.0+
- ❌ Нельзя переключить версию

---

## 📞 ПРОВЕРКА ВЕРСИИ PHP

### Текущая версия:
```bash
php -v
```

### Все доступные версии:
```bash
# Вариант 1
ls -la /opt/cpanel/ea-php*/root/usr/bin/php

# Вариант 2
which php74 php80 php81 php82

# Вариант 3
/usr/local/bin/php -v
/opt/alt/php*/usr/bin/php -v
```

---

## 🔄 ОБНОВЛЕННЫЙ СКРИПТ update-zakaz.sh

Если используешь PHP 8.0 через полный путь, обнови скрипт:

```bash
cat > /home/sneakerh/update-zakaz.sh << 'UPDATEEOF'
#!/bin/bash

# Путь к PHP 8.0
PHP="/opt/cpanel/ea-php80/root/usr/bin/php"

echo "🔄 Обновление zakaz.sneaker-head.by..."
cd /home/sneakerh/zakaz.sneaker-head.by || exit 1

echo "📥 Git pull..."
git pull origin main

echo "📦 Composer..."
$PHP composer.phar install --no-dev --optimize-autoloader

echo "🗄️  Миграции..."
$PHP yii migrate --interactive=0

echo "🧹 Очистка кэша..."
rm -rf runtime/cache/* web/assets/*

echo "✅ Сайт обновлен!"
$PHP -v
date
UPDATEEOF

chmod +x /home/sneakerh/update-zakaz.sh
```

---

## ✅ ПОСЛЕ ИСПРАВЛЕНИЯ

Продолжай с шага 5:
```bash
php composer.phar install --no-dev --optimize-autoloader
php yii migrate --interactive=0
chmod 777 runtime/ web/uploads/ web/assets/
```

Должно работать!
