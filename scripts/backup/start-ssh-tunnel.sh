#!/bin/bash

# Скрипт для запуска SSH туннеля к удаленной MySQL БД
# Позволяет работать локально с удаленной базой данных

# Цвета для вывода
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Параметры подключения
SSH_USER="sneakerh"
SSH_HOST="vh124.hoster.by"
SSH_PORT="22"
SSH_PASS="4R6xu){VWj"
LOCAL_PORT="3306"
REMOTE_PORT="3306"

echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}SSH ТУННЕЛЬ К УДАЛЕННОЙ БД${NC}"

# Проверяем, не запущен ли туннель уже
if lsof -Pi :${LOCAL_PORT} -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    echo -e "${YELLOW}⚠ Порт ${LOCAL_PORT} уже занят${NC}"
    echo ""
    echo "Проверяем активные процессы на порту ${LOCAL_PORT}:"
    lsof -i :${LOCAL_PORT}
    echo ""
    read -p "Завершить существующие процессы и продолжить? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Останавливаем процессы на порту ${LOCAL_PORT}...${NC}"
        lsof -ti :${LOCAL_PORT} | xargs kill -9 2>/dev/null
        sleep 2
    else
        echo -e "${RED}Отменено${NC}"
        exit 1
    fi
fi

# Проверяем наличие sshpass
if ! command -v sshpass &> /dev/null; then
    echo -e "${RED}✗ sshpass не установлен${NC}"
    echo ""
    echo "Установите sshpass для автоматического подключения:"
    echo "  brew install sshpass"
    echo ""
    exit 1
fi

# Проверяем SSH подключение
echo -e "${YELLOW}Проверяем подключение к ${SSH_HOST}...${NC}"

# Запускаем SSH туннель
echo -e "${YELLOW}Запускаем SSH туннель...${NC}"
echo "Локальный порт: ${LOCAL_PORT}"
echo "Удаленный MySQL: ${SSH_HOST}:${REMOTE_PORT}"
echo ""

# -N: не выполнять удаленные команды (только туннель)
# -L: настройка локального порта на удаленный
# -f: работа в фоновом режиме
# -o StrictHostKeyChecking=no: отключаем проверку хоста (первое подключение)
# -o ServerAliveInterval=60: keep-alive каждые 60 секунд
sshpass -p "${SSH_PASS}" ssh -f -N \
    -o StrictHostKeyChecking=no \
    -o ServerAliveInterval=60 \
    -o ServerAliveCountMax=3 \
    -L ${LOCAL_PORT}:localhost:${REMOTE_PORT} \
    -p ${SSH_PORT} ${SSH_USER}@${SSH_HOST}

SSH_EXIT_CODE=$?

# Ждем несколько секунд для установки соединения
sleep 3

# Проверяем, запустился ли туннель
if lsof -Pi :${LOCAL_PORT} -sTCP:LISTEN -t >/dev/null 2>&1 ; then
    # Получаем PID процесса SSH туннеля
    SSH_PID=$(lsof -ti :${LOCAL_PORT} | head -1)
    
    echo ""
    echo -e "${GREEN}✓ SSH туннель успешно запущен!${NC}"
    echo -e "${GREEN}✓ PID процесса: ${SSH_PID}${NC}"
    echo ""
    echo "Теперь вы можете:"
    echo "  • Запустить сайт локально (он будет работать с удаленной БД)"
    echo "  • Проверить подключение: ./check-ssh-tunnel.sh"
    echo "  • Остановить туннель: ./stop-ssh-tunnel.sh"
    echo ""
    echo "MySQL доступна по адресу: 127.0.0.1:${LOCAL_PORT}"
    
    # Сохраняем PID для последующей остановки
    echo ${SSH_PID} > /tmp/ssh-tunnel-mysql.pid
else
    echo -e "${RED}✗ Не удалось запустить туннель${NC}"
    echo "Код выхода SSH: ${SSH_EXIT_CODE}"
    exit 1
fi
