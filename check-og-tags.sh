#!/bin/bash

# ========================================
# Скрипт проверки Open Graph метатегов
# ========================================

# ИСПОЛЬЗОВАНИЕ:
# ./check-og-tags.sh https://your-domain.com/catalog

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# URL для проверки (можно передать как аргумент)
URL="${1:-http://localhost/catalog}"

echo "==========================================="
echo "🔍 Проверка Open Graph метатегов"
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

# Функция проверки метатега
check_tag() {
    TAG_NAME=$1
    TAG_TYPE=$2  # "property" or "name"
    
    if [ "$TAG_TYPE" = "property" ]; then
        TAG_VALUE=$(echo "$HTML" | grep -oP "(?<=<meta property=\"$TAG_NAME\" content=\")[^\"]+")
    else
        TAG_VALUE=$(echo "$HTML" | grep -oP "(?<=<meta name=\"$TAG_NAME\" content=\")[^\"]+")
    fi
    
    if [ -n "$TAG_VALUE" ]; then
        echo -e "${GREEN}✅${NC} $TAG_NAME"
        echo "   → $TAG_VALUE"
    else
        echo -e "${RED}❌${NC} $TAG_NAME"
        echo "   → не найден"
    fi
    echo ""
}

echo "-------------------------------------------"
echo "📊 Open Graph метатеги:"
echo "-------------------------------------------"
echo ""

check_tag "og:title" "property"
check_tag "og:description" "property"
check_tag "og:image" "property"
check_tag "og:url" "property"
check_tag "og:type" "property"
check_tag "og:site_name" "property"

echo "-------------------------------------------"
echo "🐦 Twitter Cards метатеги:"
echo "-------------------------------------------"
echo ""

check_tag "twitter:card" "name"
check_tag "twitter:title" "name"
check_tag "twitter:description" "name"
check_tag "twitter:image" "name"
check_tag "twitter:image:alt" "name"

echo "-------------------------------------------"
echo "🛍️  Product-specific метатеги:"
echo "-------------------------------------------"
echo ""

check_tag "product:price:amount" "property"
check_tag "product:price:currency" "property"
check_tag "product:availability" "property"
check_tag "product:condition" "property"
check_tag "product:brand" "property"

echo "-------------------------------------------"
echo "📈 Статистика:"
echo "-------------------------------------------"
echo ""

OG_COUNT=$(echo "$HTML" | grep -c '<meta property="og:')
TWITTER_COUNT=$(echo "$HTML" | grep -c '<meta name="twitter:')
PRODUCT_COUNT=$(echo "$HTML" | grep -c '<meta property="product:')

echo "Open Graph тегов: $OG_COUNT"
echo "Twitter Cards тегов: $TWITTER_COUNT"
echo "Product тегов: $PRODUCT_COUNT"
echo ""

# Проверка изображения
echo "-------------------------------------------"
echo "🖼️  Проверка изображения:"
echo "-------------------------------------------"
echo ""

IMAGE_URL=$(echo "$HTML" | grep -oP '(?<=<meta property="og:image" content=")[^"]+')

if [ -n "$IMAGE_URL" ]; then
    echo "URL изображения: $IMAGE_URL"
    
    # Проверяем доступность изображения
    IMG_HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$IMAGE_URL")
    
    if [ "$IMG_HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ Изображение доступно${NC}"
        
        # Получаем размер изображения
        IMG_SIZE=$(curl -sI "$IMAGE_URL" | grep -i content-length | awk '{print $2}' | tr -d '\r')
        if [ -n "$IMG_SIZE" ]; then
            IMG_SIZE_MB=$(echo "scale=2; $IMG_SIZE / 1048576" | bc)
            echo "Размер: $IMG_SIZE_MB МБ"
            
            # Проверяем, не слишком ли большой файл
            if (( $(echo "$IMG_SIZE_MB > 5" | bc -l) )); then
                echo -e "${YELLOW}⚠️  Предупреждение: размер больше 5 МБ${NC}"
            fi
        fi
    else
        echo -e "${RED}❌ Изображение недоступно (HTTP $IMG_HTTP_CODE)${NC}"
    fi
else
    echo -e "${RED}❌ URL изображения не найден${NC}"
fi

echo ""
echo "==========================================="
echo "✅ Проверка завершена"
echo "==========================================="
echo ""
echo "🔗 Для дальнейшего тестирования:"
echo ""
echo "Facebook Debugger:"
echo "https://developers.facebook.com/tools/debug/?q=$URL"
echo ""
echo "Twitter Card Validator:"
echo "https://cards-dev.twitter.com/validator"
echo ""
echo "Open Graph Checker:"
echo "https://www.opengraph.xyz/?url=$URL"
echo ""
