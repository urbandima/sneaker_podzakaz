#!/bin/bash

# ========================================
# Скрипт проверки Schema.org микроразметки
# ========================================

# ИСПОЛЬЗОВАНИЕ:
# ./check-schema-org.sh https://your-domain.com/catalog

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# URL для проверки (можно передать как аргумент)
URL="${1:-http://localhost/catalog}"

echo "==========================================="
echo "🔍 Проверка Schema.org микроразметки"
echo "==========================================="
echo ""
echo "URL: $URL"
echo ""

# Проверка доступности URL
echo "Проверка доступности..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL")

if [ "$HTTP_CODE" != "200" ]; then
    echo -e "${RED}❌ Ошибка: сайт недоступен (HTTP $HTTP_CODE)${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Сайт доступен (HTTP $HTTP_CODE)${NC}"
echo ""

# Получаем HTML
HTML=$(curl -s "$URL")

# Извлекаем JSON-LD схемы
echo "-------------------------------------------"
echo "📊 Поиск JSON-LD схем..."
echo "-------------------------------------------"
echo ""

# Функция для извлечения и проверки JSON-LD
check_jsonld() {
    SCHEMA_TYPE=$1
    
    # Ищем schema по типу
    JSONLD=$(echo "$HTML" | grep -oP '(?<=<script type="application/ld\+json">).*?(?=</script>)' | grep "\"@type\".*\"$SCHEMA_TYPE\"")
    
    if [ -n "$JSONLD" ]; then
        echo -e "${GREEN}✅ $SCHEMA_TYPE обнаружен${NC}"
        
        # Проверяем валидность JSON
        echo "$JSONLD" | python3 -m json.tool > /dev/null 2>&1
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}   → JSON валиден${NC}"
        else
            echo -e "${RED}   → JSON невалиден!${NC}"
        fi
        
        # Показываем ключевые поля
        if [ "$SCHEMA_TYPE" = "ItemList" ]; then
            ITEMS_COUNT=$(echo "$JSONLD" | grep -oP '"numberOfItems":\s*\K\d+')
            if [ -n "$ITEMS_COUNT" ]; then
                echo "   → Количество товаров: $ITEMS_COUNT"
            fi
        fi
        
        if [ "$SCHEMA_TYPE" = "BreadcrumbList" ]; then
            POSITIONS=$(echo "$JSONLD" | grep -c '"position"')
            if [ -n "$POSITIONS" ]; then
                echo "   → Уровней навигации: $POSITIONS"
            fi
        fi
        
        echo ""
        return 0
    else
        echo -e "${RED}❌ $SCHEMA_TYPE не найден${NC}"
        echo ""
        return 1
    fi
}

# Проверяем основные типы схем
ITEMLIST_FOUND=0
BREADCRUMB_FOUND=0
WEBSITE_FOUND=0
PRODUCT_PAGE_FOUND=0

check_jsonld "ItemList" && ITEMLIST_FOUND=1
check_jsonld "Product" && PRODUCT_PAGE_FOUND=1
check_jsonld "BreadcrumbList" && BREADCRUMB_FOUND=1
check_jsonld "WebSite" && WEBSITE_FOUND=1

# Подсчет JSON-LD блоков
echo "-------------------------------------------"
echo "📈 Статистика:"
echo "-------------------------------------------"
echo ""

JSONLD_COUNT=$(echo "$HTML" | grep -c '<script type="application/ld+json">')
echo "Найдено JSON-LD блоков: $JSONLD_COUNT"
echo ""

if [ $JSONLD_COUNT -eq 0 ]; then
    echo -e "${RED}⚠️  JSON-LD схемы не найдены на странице${NC}"
    echo ""
    echo "Возможные причины:"
    echo "1. Код не добавлен в layout"
    echo "2. Схемы не регистрируются в контроллере"
    echo "3. Параметр jsonLdSchemas не передается в view"
    echo ""
    exit 1
fi

# Детальный анализ Product
echo "-------------------------------------------"
echo "🛍️  Анализ Product схемы:"
echo "-------------------------------------------"
echo ""

PRODUCT_COUNT=$(echo "$HTML" | grep -oP '<script type="application/ld\+json">.*?</script>' | grep -c '"@type".*"Product"')

if [ $PRODUCT_COUNT -gt 0 ]; then
    echo -e "${GREEN}✅ Найдено товаров: $PRODUCT_COUNT${NC}"
    
    # Проверяем обязательные поля Product
    PRODUCTS_JSON=$(echo "$HTML" | grep -oP '(?<=<script type="application/ld\+json">).*?(?=</script>)' | grep '"@type".*"Product"')
    
    # Проверка наличия обязательных полей
    echo ""
    echo "Проверка обязательных полей:"
    
    if echo "$PRODUCTS_JSON" | grep -q '"name"'; then
        echo -e "${GREEN}✅ name${NC}"
    else
        echo -e "${RED}❌ name${NC}"
    fi
    
    if echo "$PRODUCTS_JSON" | grep -q '"sku"'; then
        echo -e "${GREEN}✅ sku${NC}"
    else
        echo -e "${YELLOW}⚠️  sku (рекомендуется)${NC}"
    fi
    
    if echo "$PRODUCTS_JSON" | grep -q '"image"'; then
        echo -e "${GREEN}✅ image${NC}"
    else
        echo -e "${RED}❌ image${NC}"
    fi
    
    if echo "$PRODUCTS_JSON" | grep -q '"brand"'; then
        echo -e "${GREEN}✅ brand${NC}"
    else
        echo -e "${YELLOW}⚠️  brand (рекомендуется)${NC}"
    fi
    
    if echo "$PRODUCTS_JSON" | grep -q '"offers"'; then
        echo -e "${GREEN}✅ offers${NC}"
        
        # Проверяем поля Offers
        if echo "$PRODUCTS_JSON" | grep -q '"price"'; then
            echo -e "${GREEN}   ✅ price${NC}"
        fi
        if echo "$PRODUCTS_JSON" | grep -q '"priceCurrency"'; then
            echo -e "${GREEN}   ✅ priceCurrency${NC}"
        fi
        if echo "$PRODUCTS_JSON" | grep -q '"availability"'; then
            echo -e "${GREEN}   ✅ availability${NC}"
        fi
    else
        echo -e "${RED}❌ offers${NC}"
    fi
    
    if echo "$PRODUCTS_JSON" | grep -q '"aggregateRating"'; then
        echo -e "${GREEN}✅ aggregateRating (опционально)${NC}"
    fi
    
    if echo "$PRODUCTS_JSON" | grep -q '"description"'; then
        echo -e "${GREEN}✅ description (опционально)${NC}"
    fi
    
else
    echo -e "${YELLOW}⚠️  Product схемы не найдены${NC}"
fi

echo ""

# Итоговая оценка
echo "==========================================="
echo "✅ Итоговая оценка"
echo "==========================================="
echo ""

SCORE=0
MAX_SCORE=3

if [ $ITEMLIST_FOUND -eq 1 ]; then
    SCORE=$((SCORE + 1))
fi

if [ $BREADCRUMB_FOUND -eq 1 ]; then
    SCORE=$((SCORE + 1))
fi

if [ $PRODUCT_COUNT -gt 0 ]; then
    SCORE=$((SCORE + 1))
fi

echo "Оценка: $SCORE/$MAX_SCORE"
echo ""

if [ $SCORE -eq $MAX_SCORE ]; then
    echo -e "${GREEN}🎉 Отлично! Все схемы работают корректно.${NC}"
elif [ $SCORE -ge 2 ]; then
    echo -e "${YELLOW}⚠️  Хорошо, но есть что улучшить.${NC}"
else
    echo -e "${RED}❌ Требуется доработка микроразметки.${NC}"
fi

echo ""
echo "==========================================="
echo "🔗 Рекомендуемые инструменты тестирования:"
echo "==========================================="
echo ""
echo "1. Google Rich Results Test:"
echo "   https://search.google.com/test/rich-results?url=$URL"
echo ""
echo "2. Schema.org Validator:"
echo "   https://validator.schema.org/"
echo ""
echo "3. Google Search Console:"
echo "   https://search.google.com/search-console"
echo ""
echo "4. Извлечь JSON-LD для ручной проверки:"
echo "   curl -s '$URL' | grep -oP '(?<=<script type=\"application/ld\+json\">).*?(?=</script>)' | python3 -m json.tool"
echo ""

exit 0
