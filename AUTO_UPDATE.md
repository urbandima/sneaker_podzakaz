# 🔄 АВТООБНОВЛЕНИЕ ИЗ GITHUB

## ⚡ БЫСТРАЯ КОМАНДА (копируй и вставляй)

После каждого `git push` на GitHub выполняй ЭТУ команду на своем компьютере:

```bash
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && git pull origin main && php composer.phar install --no-dev --optimize-autoloader && php yii migrate --interactive=0 && rm -rf runtime/cache/* web/assets/* && echo '✅ Сайт обновлен!'"
```

**Пароль SSH:** `4R6xu){VWj`

---

## 📋 ИЛИ ПО ШАГАМ:

### Шаг 1: Подключитесь к серверу
```bash
ssh sneakerh@vh124.hoster.by
```

### Шаг 2: Обновите из GitHub
```bash
cd /home/sneakerh/zakaz.sneaker-head.by
git pull origin main
php composer.phar install --no-dev --optimize-autoloader
php yii migrate --interactive=0
rm -rf runtime/cache/* web/assets/*
```

### Шаг 3: Выйдите
```bash
exit
```

---

## 🤖 АВТОМАТИЧЕСКИЙ СКРИПТ (создать один раз)

### На СЕРВЕРЕ создайте скрипт:

```bash
ssh sneakerh@vh124.hoster.by
```

Затем:

```bash
cat > /home/sneakerh/update-zakaz.sh << 'EOF'
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
EOF

chmod +x /home/sneakerh/update-zakaz.sh

echo "✅ Скрипт создан!"
exit
```

### Теперь для обновления просто:

```bash
ssh sneakerh@vh124.hoster.by "/home/sneakerh/update-zakaz.sh"
```

---

## 🎯 АЛГОРИТМ РАБОТЫ:

### На локальной машине (ваш Mac):

1. **Вносите изменения** в код
2. **Коммит и push:**
   ```bash
   cd /Users/user/CascadeProjects/splitwise
   git add .
   git commit -m "Описание изменений"
   git push origin main
   ```

3. **Обновите сервер:**
   ```bash
   ssh sneakerh@vh124.hoster.by "/home/sneakerh/update-zakaz.sh"
   ```

4. **Готово!** Сайт обновлен на zakaz.sneaker-head.by

---

## 💡 БОНУС: GitHub Webhook (автоматический деплой)

Чтобы сайт обновлялся АВТОМАТИЧЕСКИ при каждом `git push`:

### Шаг 1: Создайте скрипт webhook на сервере

```bash
ssh sneakerh@vh124.hoster.by

cat > /home/sneakerh/public_html/webhook.php << 'EOF'
<?php
// GitHub Webhook для автообновления

// Секретный токен (измените!)
$secret = 'ваш_секретный_токен_12345';

// Проверка подписи GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
$payload = file_get_contents('php://input');
$hash = 'sha1=' . hash_hmac('sha1', $payload, $secret);

if (!hash_equals($signature, $hash)) {
    http_response_code(403);
    die('Invalid signature');
}

// Логирование
file_put_contents('/home/sneakerh/webhook.log', date('Y-m-d H:i:s') . " - Webhook received\n", FILE_APPEND);

// Выполнение обновления
$output = shell_exec('/home/sneakerh/update-zakaz.sh 2>&1');

// Логирование результата
file_put_contents('/home/sneakerh/webhook.log', $output . "\n", FILE_APPEND);

echo "OK";
?>
EOF

chmod 755 /home/sneakerh/public_html/webhook.php
exit
```

### Шаг 2: Настройте GitHub Webhook

1. Откройте: https://github.com/urbandima/sneaker_podzakaz/settings/hooks
2. **Add webhook**
3. **Payload URL:** `http://sneaker-head.by/webhook.php`
4. **Content type:** `application/json`
5. **Secret:** `ваш_секретный_токен_12345` (тот же что в скрипте)
6. **Events:** Just the push event
7. **Active:** ✅
8. **Add webhook**

**Готово!** Теперь при каждом `git push` сайт обновится автоматически!

---

## 📝 КОМАНДЫ - КРАТКАЯ СПРАВКА

### Обновить сайт вручную:
```bash
ssh sneakerh@vh124.hoster.by "/home/sneakerh/update-zakaz.sh"
```

### Проверить логи webhook:
```bash
ssh sneakerh@vh124.hoster.by
cat /home/sneakerh/webhook.log
```

### Откатить к предыдущей версии:
```bash
ssh sneakerh@vh124.hoster.by
cd /home/sneakerh/zakaz.sneaker-head.by
git log --oneline -5  # Посмотреть последние коммиты
git reset --hard COMMIT_HASH  # Откатить к нужному коммиту
exit
```

---

## ✅ ГОТОВО!

Теперь у вас есть:
- ✅ Команда быстрого обновления
- ✅ Автоматический скрипт
- ✅ (опционально) GitHub Webhook для автодеплоя

🚀 **Работайте и обновляйте легко!**
