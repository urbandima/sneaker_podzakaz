#!/bin/bash
# ============================================
# СНИКЕРХЭД - Автоматический бэкап системы
# ============================================
# Использование: ./bin/scripts/backup.sh [full|db|files]
# Рекомендуется добавить в cron:
# 0 3 * * * /var/www/sneaker-head/bin/scripts/backup.sh full >> /var/log/backup.log 2>&1

set -e

# Конфигурация
BACKUP_DIR="${BACKUP_DIR:-/var/backups/sneaker-head}"
APP_DIR="${APP_DIR:-/var/www/sneaker-head}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
DATE=$(date +%Y%m%d_%H%M%S)

# Загружаем переменные окружения
if [ -f "$APP_DIR/.env" ]; then
    export $(grep -v '^#' "$APP_DIR/.env" | xargs)
fi

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') - $1"
}

# Создаем директорию для бэкапов
mkdir -p "$BACKUP_DIR"/{db,files,full}

# ============================================
# Бэкап базы данных
# ============================================
backup_database() {
    log_info "Начинаем бэкап базы данных..."
    
    local BACKUP_FILE="$BACKUP_DIR/db/db_${DATE}.sql.gz"
    
    # Извлекаем параметры подключения из DSN или используем отдельные переменные
    if [ -n "$DB_DSN" ]; then
        DB_HOST=$(echo "$DB_DSN" | sed -n 's/.*host=\([^;]*\).*/\1/p')
        DB_NAME=$(echo "$DB_DSN" | sed -n 's/.*dbname=\([^;]*\).*/\1/p')
    fi
    
    DB_HOST="${DB_HOST:-localhost}"
    DB_NAME="${DB_NAME:-sneaker_db}"
    DB_USERNAME="${DB_USERNAME:-root}"
    DB_PASSWORD="${DB_PASSWORD:-}"
    
    # Создаем дамп с сжатием
    mysqldump \
        --host="$DB_HOST" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-table \
        --complete-insert \
        "$DB_NAME" | gzip > "$BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        local SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        log_info "База данных сохранена: $BACKUP_FILE ($SIZE)"
    else
        log_error "Ошибка при создании бэкапа базы данных!"
        return 1
    fi
}

# ============================================
# Бэкап файлов
# ============================================
backup_files() {
    log_info "Начинаем бэкап файлов..."
    
    local BACKUP_FILE="$BACKUP_DIR/files/files_${DATE}.tar.gz"
    
    # Исключаем временные и ненужные файлы
    tar -czf "$BACKUP_FILE" \
        --exclude="$APP_DIR/runtime/*" \
        --exclude="$APP_DIR/web/assets/*" \
        --exclude="$APP_DIR/vendor/*" \
        --exclude="$APP_DIR/node_modules/*" \
        --exclude="$APP_DIR/.git/*" \
        --exclude="*.log" \
        -C "$(dirname "$APP_DIR")" \
        "$(basename "$APP_DIR")"
    
    if [ $? -eq 0 ]; then
        local SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        log_info "Файлы сохранены: $BACKUP_FILE ($SIZE)"
    else
        log_error "Ошибка при создании бэкапа файлов!"
        return 1
    fi
}

# ============================================
# Полный бэкап
# ============================================
backup_full() {
    log_info "Начинаем полный бэкап..."
    
    backup_database
    backup_files
    
    # Создаем архив с обоими бэкапами
    local FULL_BACKUP="$BACKUP_DIR/full/full_${DATE}.tar.gz"
    local DB_FILE="$BACKUP_DIR/db/db_${DATE}.sql.gz"
    local FILES_FILE="$BACKUP_DIR/files/files_${DATE}.tar.gz"
    
    tar -czf "$FULL_BACKUP" \
        -C "$BACKUP_DIR/db" "db_${DATE}.sql.gz" \
        -C "$BACKUP_DIR/files" "files_${DATE}.tar.gz"
    
    # Удаляем промежуточные файлы
    rm -f "$DB_FILE" "$FILES_FILE"
    
    local SIZE=$(du -h "$FULL_BACKUP" | cut -f1)
    log_info "Полный бэкап создан: $FULL_BACKUP ($SIZE)"
}

# ============================================
# Очистка старых бэкапов
# ============================================
cleanup_old_backups() {
    log_info "Удаляем бэкапы старше $RETENTION_DAYS дней..."
    
    find "$BACKUP_DIR" -type f -name "*.gz" -mtime +$RETENTION_DAYS -delete
    
    local COUNT=$(find "$BACKUP_DIR" -type f -name "*.gz" | wc -l)
    log_info "Осталось бэкапов: $COUNT"
}

# ============================================
# Загрузка в облако (опционально)
# ============================================
upload_to_cloud() {
    if [ -n "$S3_BUCKET" ]; then
        log_info "Загружаем бэкап в S3..."
        aws s3 cp "$1" "s3://$S3_BUCKET/backups/$(basename $1)"
    fi
    
    if [ -n "$RCLONE_REMOTE" ]; then
        log_info "Загружаем бэкап через rclone..."
        rclone copy "$1" "$RCLONE_REMOTE:backups/"
    fi
}

# ============================================
# Проверка целостности
# ============================================
verify_backup() {
    log_info "Проверяем целостность бэкапа..."
    
    if gzip -t "$1" 2>/dev/null; then
        log_info "Бэкап прошел проверку целостности"
        return 0
    else
        log_error "Бэкап поврежден!"
        return 1
    fi
}

# ============================================
# Основная логика
# ============================================
main() {
    log_info "=========================================="
    log_info "СНИКЕРХЭД - Система резервного копирования"
    log_info "=========================================="
    
    case "${1:-full}" in
        db|database)
            backup_database
            ;;
        files)
            backup_files
            ;;
        full)
            backup_full
            ;;
        cleanup)
            cleanup_old_backups
            ;;
        *)
            echo "Использование: $0 [full|db|files|cleanup]"
            exit 1
            ;;
    esac
    
    # Очистка старых бэкапов
    cleanup_old_backups
    
    log_info "Бэкап завершен успешно!"
}

main "$@"
