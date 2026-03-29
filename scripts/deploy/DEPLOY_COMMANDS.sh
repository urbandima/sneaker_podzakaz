#!/bin/bash

###############################################################################
# 🚀 БЫСТРЫЙ ДЕПЛОЙ НА zakaz.sneaker-head.by
# Выполняй команды по порядку
###############################################################################

echo "════════════════════════════════════════════════════════"
echo "  🚀 ДЕПЛОЙ НА PRODUCTION"
echo "════════════════════════════════════════════════════════"
echo ""

# ═══════════════════════════════════════════════════════════
# ВАРИАНТ 1: ПЕРВОНАЧАЛЬНАЯ УСТАНОВКА (ПЕРВЫЙ РАЗ)
# ═══════════════════════════════════════════════════════════

echo "📌 ВАРИАНТ 1: Первоначальная установка"
echo ""
echo "1️⃣  Подключитесь к серверу:"
echo "    ssh sneakerh@vh124.hoster.by"
echo "    Пароль: 4R6xu){VWj"
echo ""

echo "2️⃣  Клонируйте репозиторий:"
cat << 'EOF'
cd /home/sneakerh
git clone https://github.com/urbandima/sneaker_podzakaz.git zakaz.sneaker-head.by
cd zakaz.sneaker-head.by
EOF
echo ""

echo "3️⃣  Создайте config/db.php:"
cat << 'EOF'
cat > config/db.php << 'DBEOF'
<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=sneakerh_zakaz',
    'username' => 'sneakerh_zakaz',
    'password' => 'ВАШ_ПАРОЛЬ_ИЗ_CPANEL',
    'charset' => 'utf8mb4',
];
DBEOF
EOF
echo ""

echo "4️⃣  Установите Composer (если нет):"
cat << 'EOF'
curl -sS https://getcomposer.org/installer | php
EOF
echo ""

echo "5️⃣  Установите зависимости:"
cat << 'EOF'
php composer.phar install --no-dev --optimize-autoloader
EOF
echo ""

echo "6️⃣  Примените миграции:"
cat << 'EOF'
php yii migrate --interactive=0
EOF
echo ""

echo "7️⃣  Настройте права доступа:"
cat << 'EOF'
chmod 777 runtime/
chmod 777 web/uploads/
chmod 777 web/assets/
EOF
echo ""

echo "8️⃣  Отключите useFileTransport (для реальной отправки email):"
cat << 'EOF'
sed -i "s/'useFileTransport' => true,/'useFileTransport' => false,/g" config/web.php
EOF
echo ""

echo "9️⃣  Создайте скрипт автообновления:"
cat << 'EOF'
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
echo "✅ Скрипт обновления создан!"
EOF
echo ""

echo "🔟  Проверьте сайт:"
echo "    http://zakaz.sneaker-head.by"
echo "    Логин: admin / admin123"
echo ""

# ═══════════════════════════════════════════════════════════
# ВАРИАНТ 2: ОБНОВЛЕНИЕ (ПОСЛЕ ИЗМЕНЕНИЙ)
# ═══════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════"
echo "📌 ВАРИАНТ 2: Обновление существующего сайта"
echo "════════════════════════════════════════════════════════"
echo ""

echo "1️⃣  Закоммитьте изменения локально:"
cat << 'EOF'
cd /Users/user/CascadeProjects/splitwise
git add .
git commit -m "Описание изменений"
git push origin main
EOF
echo ""

echo "2️⃣  Обновите сайт одной командой:"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "/home/sneakerh/update-zakaz.sh"
EOF
echo ""

echo "ИЛИ более длинная версия (без скрипта):"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && git pull origin main && php composer.phar install --no-dev --optimize-autoloader && php yii migrate --interactive=0 && rm -rf runtime/cache/* web/assets/* && echo '✅ Сайт обновлен!'"
EOF
echo ""

# ═══════════════════════════════════════════════════════════
# ПОЛЕЗНЫЕ КОМАНДЫ
# ═══════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════"
echo "🛠️  ПОЛЕЗНЫЕ КОМАНДЫ"
echo "════════════════════════════════════════════════════════"
echo ""

echo "📊 Посмотреть логи:"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "tail -100 /home/sneakerh/zakaz.sneaker-head.by/runtime/logs/app.log"
EOF
echo ""

echo "🗄️  Проверить статус БД:"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && php yii migrate"
EOF
echo ""

echo "🔄 Откатить к предыдущей версии:"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && git log --oneline -5"
# Затем:
# git reset --hard COMMIT_HASH
EOF
echo ""

echo "🧹 Очистить кэш:"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && rm -rf runtime/cache/* web/assets/*"
EOF
echo ""

echo "📦 Переустановить зависимости:"
cat << 'EOF'
ssh sneakerh@vh124.hoster.by "cd /home/sneakerh/zakaz.sneaker-head.by && rm -rf vendor/ && php composer.phar install --no-dev --optimize-autoloader"
EOF
echo ""

# ═══════════════════════════════════════════════════════════
# ИТОГ
# ═══════════════════════════════════════════════════════════

echo ""
echo "════════════════════════════════════════════════════════"
echo "  ✅ ВСЕ КОМАНДЫ ГОТОВЫ К ИСПОЛЬЗОВАНИЮ"
echo "════════════════════════════════════════════════════════"
echo ""
echo "🎯 Следующий шаг:"
echo "   Скопируйте нужные команды и выполните их в терминале"
echo ""
echo "📖 Полная документация:"
echo "   - PRODUCTION_READY_CHECKLIST.md"
echo "   - AUTO_UPDATE.md"
echo "   - README.md"
echo ""
