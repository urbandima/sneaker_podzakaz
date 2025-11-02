#!/bin/bash

# Скрипт полной настройки проекта с тестовыми данными

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}=====================================${NC}"
echo -e "${YELLOW}ПОЛНАЯ НАСТРОЙКА ПРОЕКТА${NC}"
echo -e "${YELLOW}=====================================${NC}"
echo ""

# Проверяем MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}✗ MySQL не установлен${NC}"
    echo ""
    echo "Установите MySQL:"
    echo "  brew install mysql"
    echo "  brew services start mysql"
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
echo -e "${YELLOW}Шаг 1: Создание базы данных...${NC}"
mysql -u root << EOF
CREATE DATABASE IF NOT EXISTS order_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SELECT 'База данных order_management готова!' as Status;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ База данных создана${NC}"
else
    echo -e "${RED}✗ Ошибка создания БД${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Шаг 2: Запуск миграций...${NC}"
./yii migrate --interactive=0

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Миграции выполнены${NC}"
else
    echo -e "${RED}✗ Ошибка миграций${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Шаг 3: Проверка данных...${NC}"
mysql -u root order_management << EOF
SELECT 'Бренды:' as Info, COUNT(*) as Count FROM brand;
SELECT 'Категории:' as Info, COUNT(*) as Count FROM category;
SELECT 'Товары:' as Info, COUNT(*) as Count FROM product;
SELECT 'Стили:' as Info, COUNT(*) as Count FROM style;
SELECT 'Технологии:' as Info, COUNT(*) as Count FROM technology;
EOF

echo ""
echo -e "${GREEN}✓✓✓ ВСЕ ГОТОВО! ✓✓✓${NC}"
echo ""
echo "Что было сделано:"
echo "  ✓ Создана база данных order_management"
echo "  ✓ Выполнены все миграции (таблицы созданы)"
echo "  ✓ Добавлено 8 брендов (Nike, Adidas, New Balance...)"
echo "  ✓ Добавлено 4 категории (Кроссовки, Ботинки...)"
echo "  ✓ Добавлено 10 тестовых товаров с новыми полями"
echo "  ✓ Добавлено 7 стилей"
echo "  ✓ Добавлено 5 технологий"
echo ""
echo "Теперь вы можете:"
echo "  • Открыть сайт: http://localhost:8080/catalog"
echo "  • Протестировать фильтры"
echo "  • Протестировать корзину"
echo "  • Протестировать избранное"
echo "  • Протестировать поиск"
echo ""
echo -e "${YELLOW}Запустите локальный сервер:${NC}"
echo "  php -S localhost:8080 -t web"
echo ""
