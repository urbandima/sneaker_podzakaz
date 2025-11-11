#!/bin/bash

# ========================================
# СКРИПТ ДЕПЛОЯ НА zakaz-test.sneaker-head.by
# ========================================

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# SSH данные
SSH_HOST="93.125.99.7"
SSH_PORT="22"
SSH_USER="sneakerh"
SSH_PASS="4R6xu){VWj"
DEPLOY_PATH="/home/sneakerh/zakaz-test.sneaker-head.by"

# База данных
DB_NAME="sneakerh_username_order_management"
DB_USER="sneakerh_username_order_user"
DB_PASS="kefir1kefir"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}ДЕПЛОЙ НА zakaz-test.sneaker-head.by${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Функция для выполнения команд по SSH
ssh_exec() {
    sshpass -p "$SSH_PASS" ssh -o StrictHostKeyChecking=no -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "$1"
}

# Функция для копирования файлов
scp_upload() {
    sshpass -p "$SSH_PASS" scp -o StrictHostKeyChecking=no -P "$SSH_PORT" "$1" "$SSH_USER@$SSH_HOST:$2"
}

# Шаг 1: Проверка наличия sshpass
echo -e "${YELLOW}[1/8] Проверка sshpass...${NC}"
if ! command -v sshpass &> /dev/null; then
    echo -e "${RED}❌ sshpass не установлен${NC}"
    echo -e "${YELLOW}Установка: brew install hudochenkov/sshpass/sshpass${NC}"
    echo ""
    echo -e "${YELLOW}Или используйте альтернативный способ:${NC}"
    echo "ssh sneakerh@93.125.99.7 -p 22"
    echo "cd /home/sneakerh/zakaz-test.sneaker-head.by"
    echo "git pull origin main"
    exit 1
fi
echo -e "${GREEN}✓ sshpass установлен${NC}\n"

# Шаг 2: Проверка подключения к серверу
echo -e "${YELLOW}[2/8] Проверка подключения к серверу...${NC}"
if ssh_exec "echo 'OK'" | grep -q "OK"; then
    echo -e "${GREEN}✓ Подключение успешно${NC}\n"
else
    echo -e "${RED}❌ Ошибка подключения${NC}"
    exit 1
fi

# Шаг 3: Проверка существования директории
echo -e "${YELLOW}[3/8] Проверка директории проекта...${NC}"
if ssh_exec "[ -d '$DEPLOY_PATH' ] && echo 'EXISTS'"; then
    echo -e "${GREEN}✓ Директория существует: $DEPLOY_PATH${NC}\n"
    
    # Проверка наличия .git
    if ssh_exec "[ -d '$DEPLOY_PATH/.git' ] && echo 'GIT_EXISTS'"; then
        echo -e "${GREEN}✓ Git репозиторий найден${NC}\n"
        
        # Шаг 4: Обновление кода из GitHub
        echo -e "${YELLOW}[4/8] Обновление кода из GitHub...${NC}"
        ssh_exec "cd $DEPLOY_PATH && git fetch origin && git reset --hard origin/main"
        echo -e "${GREEN}✓ Код обновлен${NC}\n"
    else
        echo -e "${YELLOW}⚠ Git репозиторий не найден${NC}"
        echo -e "${YELLOW}Клонирование из GitHub...${NC}"
        ssh_exec "rm -rf $DEPLOY_PATH && git clone https://github.com/urbandima/sneaker_podzakaz.git $DEPLOY_PATH"
        echo -e "${GREEN}✓ Репозиторий склонирован${NC}\n"
    fi
else
    echo -e "${YELLOW}⚠ Директория не существует${NC}"
    echo -e "${YELLOW}Создание и клонирование...${NC}"
    ssh_exec "mkdir -p $DEPLOY_PATH && git clone https://github.com/urbandima/sneaker_podzakaz.git $DEPLOY_PATH"
    echo -e "${GREEN}✓ Проект создан${NC}\n"
fi

# Шаг 5: Создание конфигурационного файла db.php
echo -e "${YELLOW}[5/8] Настройка конфигурации базы данных...${NC}"
ssh_exec "cat > $DEPLOY_PATH/config/db.php << 'EOL'
<?php

/**
 * Конфигурация базы данных для zakaz-test.sneaker-head.by
 */

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=$DB_NAME',
    'username' => '$DB_USER',
    'password' => '$DB_PASS',
    'charset' => 'utf8mb4',
    
    // Производительность
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
    
    // Подключение
    'attributes' => [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ],
];
EOL
"
echo -e "${GREEN}✓ config/db.php создан${NC}\n"

# Шаг 6: Создание .env файла
echo -e "${YELLOW}[6/8] Создание .env файла...${NC}"
ssh_exec "cat > $DEPLOY_PATH/.env << 'EOL'
# Environment
YII_ENV=prod
YII_DEBUG=false

# Security
COOKIE_VALIDATION_KEY=$(openssl rand -hex 32)

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASS
DB_CHARSET=utf8mb4

# Email
MAIL_USE_FILE_TRANSPORT=false
MAIL_FROM_EMAIL=noreply@sneaker-head.by
MAIL_FROM_NAME=СникерХэд

# Company
COMPANY_NAME=СникерХэд
COMPANY_PHONE=+375 29 123-45-67
COMPANY_EMAIL=info@sneaker-head.by

# Admin
ADMIN_EMAIL=admin@sneaker-head.by
EOL
"
echo -e "${GREEN}✓ .env создан${NC}\n"

# Шаг 7: Установка зависимостей и миграции
echo -e "${YELLOW}[7/8] Установка зависимостей и обновление БД...${NC}"
ssh_exec "cd $DEPLOY_PATH && composer install --no-dev --optimize-autoloader 2>&1"
echo -e "${GREEN}✓ Composer зависимости установлены${NC}"

ssh_exec "cd $DEPLOY_PATH && php yii migrate --interactive=0 2>&1"
echo -e "${GREEN}✓ Миграции применены${NC}\n"

# Шаг 8: Настройка прав доступа
echo -e "${YELLOW}[8/8] Настройка прав доступа...${NC}"
ssh_exec "cd $DEPLOY_PATH && chmod -R 755 . && chmod -R 777 runtime web/cache web/assets web/uploads"
echo -e "${GREEN}✓ Права доступа настроены${NC}\n"

# Финал
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ ДЕПЛОЙ ЗАВЕРШЕН УСПЕШНО!${NC}"
echo -e "${GREEN}========================================${NC}\n"
echo -e "Сайт доступен по адресу:"
echo -e "${YELLOW}https://zakaz-test.sneaker-head.by${NC}\n"
echo -e "Админ-панель:"
echo -e "${YELLOW}https://zakaz-test.sneaker-head.by/admin${NC}\n"
echo -e "Тестовые логины:"
echo -e "  Логин: ${YELLOW}admin${NC}"
echo -e "  Пароль: ${YELLOW}admin123${NC}\n"
