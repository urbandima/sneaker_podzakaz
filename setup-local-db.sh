#!/bin/bash

# Скрипт автоматической настройки локальной БД для разработки

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}НАСТРОЙКА ЛОКАЛЬНОЙ БД${NC}"
echo -e "${YELLOW}=====================================${NC}"
echo ""

# Проверяем наличие MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}✗ MySQL не установлен${NC}"
    echo ""
    echo "Установите MySQL через Homebrew:"
    echo ""
    echo -e "${YELLOW}brew install mysql${NC}"
    echo ""
    echo "После установки запустите этот скрипт снова"
    exit 1
fi

echo -e "${GREEN}✓ MySQL установлен${NC}"

# Проверяем, запущен ли MySQL
if ! pgrep -x "mysqld" > /dev/null; then
    echo -e "${YELLOW}⚠ MySQL не запущен${NC}"
    echo "Запускаем MySQL..."
    brew services start mysql
    sleep 3
fi

echo -e "${GREEN}✓ MySQL запущен${NC}"
echo ""

# Создаем базу данных
echo "Создаем базу данных..."
echo ""

mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS order_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SELECT 'База данных order_management создана!' as Status;
EOF

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓ База данных успешно создана!${NC}"
    echo ""
    echo "Параметры подключения:"
    echo "  Host: 127.0.0.1"
    echo "  Database: order_management"
    echo "  User: root"
    echo "  Password: (пусто)"
    echo ""
    echo -e "${GREEN}Готово! Теперь сайт должен работать${NC}"
else
    echo -e "${RED}✗ Ошибка при создании базы данных${NC}"
    echo ""
    echo "Попробуйте вручную:"
    echo "  mysql -u root"
    echo "  CREATE DATABASE order_management;"
    exit 1
fi

echo ""
echo "Проверяем соединение с БД..."

mysql -u root -e "USE order_management; SELECT 'Подключение успешно!' as Status;"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✓✓✓ ВСЕ ГОТОВО! ✓✓✓${NC}"
    echo ""
    echo "Теперь вы можете:"
    echo "  • Открыть сайт: http://localhost:8080"
    echo "  • Импортировать данные: mysql -u root order_management < dump.sql"
    echo "  • Запустить миграции: ./yii migrate"
else
    echo ""
    echo -e "${RED}✗ Проблема с подключением к БД${NC}"
fi
