#!/bin/bash

# Скрипт для проверки статуса SSH туннеля и подключения к БД

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

LOCAL_PORT="3306"
DB_NAME="sneakerh_username_order_management"
DB_USER="sneakerh_username_order_user"
DB_PASS="kefir1kefir"

echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}ПРОВЕРКА SSH ТУННЕЛЯ${NC}"
echo -e "${YELLOW}=====================================${NC}"
echo ""

# 1. Проверяем, слушает ли что-то порт 3306
echo -e "${YELLOW}[1/3] Проверка порта ${LOCAL_PORT}...${NC}"
if lsof -Pi :${LOCAL_PORT} -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${GREEN}✓ Порт ${LOCAL_PORT} активен${NC}"
    echo "Процессы на порту:"
    lsof -i :${LOCAL_PORT} | head -5
else
    echo -e "${RED}✗ Порт ${LOCAL_PORT} не прослушивается${NC}"
    echo ""
    echo "Туннель не запущен. Запустите его командой:"
    echo "  ./start-ssh-tunnel.sh"
    exit 1
fi

echo ""

# 2. Проверяем SSH процессы
echo -e "${YELLOW}[2/3] Проверка SSH процессов...${NC}"
SSH_PROCESSES=$(ps aux | grep "ssh.*3306:localhost" | grep -v grep)
if [ -n "$SSH_PROCESSES" ]; then
    echo -e "${GREEN}✓ SSH туннель активен${NC}"
    echo "$SSH_PROCESSES" | head -3
else
    echo -e "${RED}✗ SSH процессы не найдены${NC}"
    echo "Возможно, порт занят другим процессом (не SSH туннелем)"
fi

echo ""

# 3. Проверяем подключение к MySQL
echo -e "${YELLOW}[3/3] Проверка подключения к MySQL...${NC}"

# Проверяем наличие mysql клиента
if ! command -v mysql &> /dev/null; then
    echo -e "${YELLOW}⚠ MySQL клиент не установлен${NC}"
    echo "Для полной проверки установите MySQL клиент:"
    echo "  brew install mysql-client"
    echo ""
    echo -e "${GREEN}Туннель работает, но проверить MySQL подключение не удалось${NC}"
    exit 0
fi

# Пытаемся подключиться к БД
if mysql -h 127.0.0.1 -P ${LOCAL_PORT} -u${DB_USER} -p${DB_PASS} ${DB_NAME} -e "SELECT 1 as test" &> /dev/null; then
    echo -e "${GREEN}✓ Подключение к MySQL успешно!${NC}"
    echo ""
    echo "Информация о БД:"
    mysql -h 127.0.0.1 -P ${LOCAL_PORT} -u${DB_USER} -p${DB_PASS} ${DB_NAME} -e "
        SELECT 
            'База данных' as 'Параметр',
            DATABASE() as 'Значение'
        UNION ALL
        SELECT 
            'Версия MySQL',
            VERSION()
        UNION ALL
        SELECT 
            'Текущий пользователь',
            CURRENT_USER();
    " 2>/dev/null
    
    echo ""
    echo "Список таблиц:"
    mysql -h 127.0.0.1 -P ${LOCAL_PORT} -u${DB_USER} -p${DB_PASS} ${DB_NAME} -e "SHOW TABLES;" 2>/dev/null | head -10
else
    echo -e "${RED}✗ Не удалось подключиться к MySQL${NC}"
    echo ""
    echo "Возможные причины:"
    echo "1. Неверные учетные данные"
    echo "2. БД недоступна на удаленном сервере"
    echo "3. Туннель работает, но MySQL на сервере выключен"
    exit 1
fi

echo ""
echo -e "${GREEN}=====================================${NC}"
echo -e "${GREEN}✓ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ${NC}"
echo -e "${GREEN}=====================================${NC}"
echo ""
echo "Теперь вы можете запустить сайт локально:"
echo "  php yii serve"
echo ""
echo "Для остановки туннеля используйте:"
echo "  ./stop-ssh-tunnel.sh"
