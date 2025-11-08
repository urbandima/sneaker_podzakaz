# ⚡ БЫСТРЫЙ СТАРТ - ДЕПЛОЙ НА ХОСТИНГ

**Время:** 10-15 минут  
**Для кого:** Быстрая выгрузка на zakaz.sneaker-head.by

---

## 🎯 ДВА СЦЕНАРИЯ

### 📦 Сценарий 1: Первый раз (установка с нуля)
### 🔄 Сценарий 2: Обновление (после изменений)

---

## 📦 СЦЕНАРИЙ 1: ПЕРВАЯ УСТАНОВКА

### Шаг 1: Подключитесь к серверу
```bash
ssh sneakerh@vh124.hoster.by
```
**Пароль:** `4R6xu){VWj`

### Шаг 2: Клонируйте проект
```bash
cd /home/sneakerh
git clone https://github.com/urbandima/sneaker_podzakaz.git zakaz.sneaker-head.by
cd zakaz.sneaker-head.by
```

### Шаг 3: Создайте config/db.php
```bash
cat > config/db.php << 'EOF'
<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=sneakerh_zakaz',
    'username' => 'sneakerh_zakaz',
    'password' => 'ВАШ_ПАРОЛЬ_ИЗ_CPANEL',
    'charset' => 'utf8mb4',
];
EOF
```
⚠️ **Замените `ВАШ_ПАРОЛЬ_ИЗ_CPANEL` на реальный пароль из cPanel!**

### Шаг 4: Установите Composer (если его нет)
```bash
curl -sS https://getcomposer.org/installer | php
```

### Шаг 5: Установите зависимости
```bash
php composer.phar install --no-dev --optimize-autoloader
```
⏱️ Займет 2-3 минуты

### Шаг 6: Примените миграции
```bash
php yii migrate --interactive=0
```

### Шаг 7: Настройте права
```bash
chmod 777 runtime/
chmod 777 web/uploads/
chmod 777 web/assets/
```

### Шаг 8: Включите реальную отправку email
```bash
sed -i "s/'useFileTransport' => true,/'useFileTransport' => false,/g" config/web.php
```

### Шаг 9: Создайте скрипт обновления
```bash
cat > /home/sneakerh/update-zakaz.sh << 'UPDATEEOF'
#!/bin/bash
echo "🔄 Обновление zakaz.sneaker-head.by..."
cd /home/sneakerh/zakaz.sneaker-head.by || exit 1
echo "📥 Git pull..."
git pull origin main
echo "📦 Composer..."
php composer.phar install --no-dev --optimize-autoloader
echo "🗄️  Миграции..."
php yii migrate --interactive=0
echo "🧹 Очистка кэша..."
rm -rf runtime/cache/* web/assets/*
echo "✅ Сайт обновлен!"
date
UPDATEEOF

chmod +x /home/sneakerh/update-zakaz.sh
```

### Шаг 10: Проверьте!
Откройте браузер: **http://zakaz.sneaker-head.by**

**Логин:** admin  
**Пароль:** admin123

---

## 🔄 СЦЕНАРИЙ 2: ОБНОВЛЕНИЕ

### Шаг 1: Закоммитьте изменения (на Mac)
```bash
cd /Users/user/CascadeProjects/splitwise
git add .
git commit -m "Описание изменений"
git push origin main
```

### Шаг 2: Обновите сайт (одна команда!)
```bash
ssh sneakerh@vh124.hoster.by "/home/sneakerh/update-zakaz.sh"
```

**Готово!** Сайт обновлен за 30 секунд.

---

## 🛠️ ПОЛЕЗНЫЕ КОМАНДЫ

### Посмотреть логи
```bash
ssh sneakerh@vh124.hoster.by "tail -50 /home/sneakerh/zakaz.sneaker-head.by/runtime/logs/app.log"
```

### Очистить кэш
```bash
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && rm -rf runtime/cache/* web/assets/*"
```

### Проверить статус Git
```bash
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && git status"
```

### Откатить к предыдущей версии
```bash
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && git log --oneline -5"
# Выберите нужный коммит, затем:
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && git reset --hard COMMIT_HASH"
```

---

## ❓ TROUBLESHOOTING

### ❌ Ошибка: "Unable to write to runtime"
```bash
ssh sneakerh@vh124.hoster.by "chmod -R 777 /home/sneakerh/zakaz.sneaker-head.by/runtime/"
```

### ❌ Ошибка: "Database connection failed"
Проверьте config/db.php - правильность пароля и имени БД

### ❌ Ошибка: "Class not found"
```bash
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && php composer.phar dump-autoload"
```

### ❌ Email не отправляются
1. Проверьте: `useFileTransport => false` в config/web.php
2. Настройте SMTP (см. PRODUCTION_READY_CHECKLIST.md)

---

## 📋 ПОСЛЕ УСТАНОВКИ

### ✅ Проверьте работу:
- [ ] Сайт открывается
- [ ] Вход в админку работает
- [ ] Создание заказа работает
- [ ] Публичная ссылка работает
- [ ] Загрузка файлов работает

### 🔐 Смените пароль admin!
После первого входа:
1. Войдите как admin
2. Профиль → Сменить пароль
3. Установите надежный пароль

---

## 🎯 ИТОГО

| Действие | Команда |
|----------|---------|
| **Первая установка** | Шаги 1-10 (один раз) |
| **Обновление** | `ssh ... "/home/sneakerh/update-zakaz.sh"` |
| **Логи** | `ssh ... "tail -50 .../runtime/logs/app.log"` |
| **Кэш** | `ssh ... "rm -rf .../runtime/cache/*"` |

---

✅ **ВСЁ ГОТОВО К ДЕПЛОЮ!**

Начинайте с **Сценария 1**, если устанавливаете впервые.  
Используйте **Сценарий 2** для всех последующих обновлений.
