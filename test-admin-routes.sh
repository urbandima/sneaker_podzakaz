#!/bin/bash

# Скрипт для тестирования всех admin роутов
# Использование: ./test-admin-routes.sh http://localhost

if [ -z "$1" ]; then
    echo "Использование: $0 <base-url>"
    echo "Пример: $0 http://localhost"
    exit 1
fi

BASE_URL=$1
FAILED=0
PASSED=0

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "=========================================="
echo "Тестирование admin роутов"
echo "Base URL: $BASE_URL"
echo "=========================================="
echo ""

test_route() {
    local route=$1
    local name=$2
    
    echo -n "Тестирование $name ($route)... "
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL$route" -L)
    
    if [ "$response" == "200" ] || [ "$response" == "302" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        PASSED=$((PASSED + 1))
    else
        echo -e "${RED}✗ FAIL${NC} (HTTP $response)"
        FAILED=$((FAILED + 1))
    fi
}

# Dashboard
echo "=== Dashboard ==="
test_route "/admin" "Dashboard"
test_route "/admin/profile" "Profile"
test_route "/admin/settings" "Settings"
echo ""

# Orders (новые роуты)
echo "=== Orders (новые роуты) ==="
test_route "/admin/order" "Orders list"
test_route "/admin/order/create" "Create order"
echo ""

# Orders (backward compatibility)
echo "=== Orders (backward compatibility) ==="
test_route "/admin/orders" "Orders list (old URL)"
test_route "/admin/create-order" "Create order (old URL)"
echo ""

# Products (новые роуты)
echo "=== Products (новые роуты) ==="
test_route "/admin/product" "Products list"
echo ""

# Products (backward compatibility)
echo "=== Products (backward compatibility) ==="
test_route "/admin/products" "Products list (old URL)"
echo ""

# Users (новые роуты)
echo "=== Users (новые роуты) ==="
test_route "/admin/user" "Users list"
test_route "/admin/user/create" "Create user"
echo ""

# Users (backward compatibility)
echo "=== Users (backward compatibility) ==="
test_route "/admin/users" "Users list (old URL)"
test_route "/admin/create-user" "Create user (old URL)"
echo ""

# Size Grids
echo "=== Size Grids ==="
test_route "/admin/size-grid" "Size grids list"
test_route "/admin/size-grid/create" "Create size grid"
test_route "/admin/size-grid/guide" "Size guide"
echo ""

# Poizon
echo "=== Poizon Import ==="
test_route "/admin/poizon" "Poizon dashboard"
test_route "/admin/poizon/run" "Run import"
test_route "/admin/poizon/logs" "View logs"
echo ""

# Statistics
echo "=== Statistics ==="
test_route "/admin/statistics" "Statistics"
echo ""

# Characteristics
echo "=== Characteristics ==="
test_route "/admin/characteristic" "Characteristics"
test_route "/admin/characteristic/guide" "Characteristics guide"
echo ""

# Итоги
echo ""
echo "=========================================="
echo "Результаты тестирования:"
echo "=========================================="
echo -e "Пройдено: ${GREEN}$PASSED${NC}"
echo -e "Провалено: ${RED}$FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ ВСЕ ТЕСТЫ ПРОЙДЕНЫ!${NC}"
    exit 0
else
    echo -e "${RED}✗ ЕСТЬ ПРОВАЛЕННЫЕ ТЕСТЫ!${NC}"
    echo ""
    echo "Возможные причины:"
    echo "1. Не обновлен config/web.php"
    echo "2. Контроллеры не загружены на сервер"
    echo "3. Не очищен кеш приложения"
    echo "4. Требуется авторизация (попробуйте войти в админку)"
    exit 1
fi
