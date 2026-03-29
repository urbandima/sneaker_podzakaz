#!/bin/bash

# ===============================================
# DEPLOYMENT SCRIPT FOR PRODUCTION
# ===============================================
# Использование: ./deploy-production.sh [environment]
# Пример: ./deploy-production.sh staging

set -e  # Остановить при ошибке

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функции для вывода
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Получаем окружение
    exit 1
fi

# 2. Бэкап текущей версии
echo "� Создание бэкапа..."
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Бэкап файлов
tar -czf "$BACKUP_DIR/files.tar.gz" --exclude='node_modules' --exclude='vendor' --exclude='runtime' --exclude='backups' .

# Бэкап базы данных
mysqldump --defaults-file=.env.production sneakerhead > "$BACKUP_DIR/database.sql"

echo "✅ Бэкап создан: $BACKUP_DIR"

# 3. Обновление кода
echo "� Обновление кода..."
git pull origin main

# 4. Установка зависимостей
echo "� Установка зависимостей..."
composer install --no-dev --optimize-autoloader
npm ci --production

if [ $? -eq 0 ]; then
    log_success "Зависимости установлены"
else
    log_error "Ошибка установки зависимостей"
    exit 1
fi

# 4. Применение миграций
log_info "🔄 Применяем миграции..."
php yii migrate/up --interactive=0

if [ $? -eq 0 ]; then
    log_success "Миграции применены"
else
    log_error "Ошибка применения миграций"
    exit 1
fi

# 5. Индексация Elasticsearch (только для production)
if [ "$ENVIRONMENT" = "production" ]; then
    log_info "🔍 Индексируем товары в Elasticsearch..."
    php yii elasticsearch/reindex
    
    if [ $? -eq 0 ]; then
        log_success "Elasticsearch индексация завершена"
    else
        log_warning "Ошибка индексации Elasticsearch (продолжаем)"
    fi
fi

# 6. Очистка кеша
log_info "🗑️  Очищаем кеш..."
php yii cache/flush-all

if [ $? -eq 0 ]; then
    log_success "Кеш очищен"
else
    log_warning "Ошибка очистки кеша (продолжаем)"
fi

# 7. Установка прав доступа
log_info "🔐 Устанавливаем права доступа..."
chown -R www-data:www-data /var/www/sneakerhead
chmod -R 755 /var/www/sneakerhead
chmod -R 775 /var/www/sneakerhead/runtime
chmod -R 775 /var/www/sneakerhead/frontend/web/assets

log_success "Права доступа установлены"

# 8. Перезапуск сервисов
log_info "🔄 Перезапускаем сервисы..."

# PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    systemctl reload php8.1-fpm
    log_success "PHP-FPM перезапущен"
else
    log_warning "PHP-FPM не запущен"
fi

# Nginx
if systemctl is-active --quiet nginx; then
    systemctl reload nginx
    log_success "Nginx перезапущен"
else
    log_warning "Nginx не запущен"
fi

# Redis
if systemctl is-active --quiet redis; then
    systemctl restart redis
    log_success "Redis перезапущен"
else
    log_warning "Redis не запущен"
fi

# 9. Проверка работоспособности
log_info "🧪 Проверяем работоспособность..."

# Проверка PHP
if php -v > /dev/null 2>&1; then
    log_success "PHP работает"
else
    log_error "PHP не работает"
    exit 1
fi

# Проверка MySQL
if mysql -u $DB_USER -p$DB_PASSWORD -e "SELECT 1" > /dev/null 2>&1; then
    log_success "MySQL работает"
else
    log_error "MySQL не работает"
    exit 1
fi

# Проверка Redis
if redis-cli ping > /dev/null 2>&1; then
    log_success "Redis работает"
else
    log_warning "Redis не работает"
fi

# Проверка Elasticsearch
if curl -s http://localhost:9200 > /dev/null 2>&1; then
    log_success "Elasticsearch работает"
else
    log_warning "Elasticsearch не работает"
fi

# 10. Завершение
echo "=============================================="
log_success "🎉 Деплой успешно завершён для окружения: $ENVIRONMENT"

# Отправка уведомления (опционально)
if [ "$ENVIRONMENT" = "production" ]; then
    log_info "📧 Отправляем уведомление..."
    # curl -X POST "$SLACK_WEBHOOK" -d "{'text': 'Deployment to production completed successfully'}"
fi

exit 0
