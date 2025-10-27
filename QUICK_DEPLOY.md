# ⚡ Быстрое развертывание за 5 минут

## 1️⃣ Добавить секреты в GitHub (2 минуты)

https://github.com/urbandima/sneaker_podzakaz/settings/secrets/actions

Нажать "New repository secret" 4 раза и добавить:

```
SSH_HOST      = vh124.hoster.by
SSH_USERNAME  = sneakerh
SSH_PASSWORD  = (2LsY_tc5E
SSH_PATH      = /home/sneakerh/zakaz.sneaker-head.by
```

---

## 2️⃣ Подключиться к серверу и установить (3 минуты)

```bash
# Подключиться
ssh sneakerh@vh124.hoster.by
# Пароль: (2LsY_tc5E

# Перейти в директорию
cd /home/sneakerh/zakaz.sneaker-head.by

# Клонировать проект
git clone https://github.com/urbandima/sneaker_podzakaz.git .

# Установить зависимости
composer install --no-dev

# Настроить БД (создать через панель хостинга)
nano config/db.php
# Изменить: host, dbname, username, password

# Применить миграции
php yii migrate --interactive=0

# Установить права
chmod -R 777 runtime web/assets web/uploads
```

---

## 3️⃣ Готово! ✅

Открыть: http://zakaz.sneaker-head.by/

---

## 📋 Полная инструкция

См. **SERVER_DEPLOY_INSTRUCTIONS.md**
