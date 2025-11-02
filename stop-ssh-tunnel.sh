#!/bin/bash

# Скрипт для остановки SSH туннеля

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

LOCAL_PORT="3306"
PID_FILE="/tmp/ssh-tunnel-mysql.pid"

echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}ОСТАНОВКА SSH ТУННЕЛЯ${NC}"
echo -e "${YELLOW}=====================================${NC}"

# Пытаемся остановить по сохраненному PID
if [ -f "$PID_FILE" ]; then
    SSH_PID=$(cat $PID_FILE)
    if ps -p $SSH_PID > /dev/null 2>&1; then
        echo -e "${YELLOW}Останавливаем туннель (PID: ${SSH_PID})...${NC}"
        kill $SSH_PID 2>/dev/null
        rm -f $PID_FILE
        echo -e "${GREEN}✓ Туннель остановлен${NC}"
    else
        echo -e "${YELLOW}Процесс с PID ${SSH_PID} не найден${NC}"
        rm -f $PID_FILE
    fi
fi

# Проверяем, остались ли процессы на порту
if lsof -Pi :${LOCAL_PORT} -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${YELLOW}Обнаружены процессы на порту ${LOCAL_PORT}${NC}"
    lsof -i :${LOCAL_PORT}
    echo ""
    read -p "Завершить все процессы на порту ${LOCAL_PORT}? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        lsof -ti :${LOCAL_PORT} | xargs kill -9 2>/dev/null
        echo -e "${GREEN}✓ Все процессы остановлены${NC}"
    fi
else
    echo -e "${GREEN}✓ Порт ${LOCAL_PORT} свободен${NC}"
fi

echo ""
echo "Готово!"
