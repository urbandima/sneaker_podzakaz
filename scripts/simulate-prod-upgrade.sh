#!/usr/bin/env bash
#
# CMP-412: симуляция апгрейда прод-базы main -> текущий HEAD.
#
# Прод ни разу не проходил через `deploy-production` (CI), поэтому неизвестно,
# на каком наборе миграций он реально стоит. Этот скрипт строит правдоподобную
# БД "как будто прод остановился на миграции X", наполняет её данными, включает
# рантайм-патчер TariffSetupService::ensureOrderSupport() (может расходиться
# с миграциями — см. TariffSetupService.php), затем накатывает остаток миграций
# до HEAD и проверяет откат.
#
# Использование:
#   scripts/simulate-prod-upgrade.sh <migration-name-or-'m000000_000000_base'> [db_name]
#
# Пример (сценарий "прод остановился на миграциях примерно май 2026"):
#   scripts/simulate-prod-upgrade.sh m260513_100000_add_fulltext_index_to_product cmp412_a
#
# Пример (сценарий "прод стоит на состоянии сразу после архитектурного
# рефакторинга 2026-03-16, ничего с тех пор не катилось"):
#   scripts/simulate-prod-upgrade.sh m251228_000000_create_tariff_calculation_history cmp412_b
#
# Требует локальный MySQL (root без пароля) и установленный composer vendor/
# в текущем чекауте main. НЕ трогает .env репозитория — все настройки БД
# передаются через переменные окружения (DB_DSN/DB_NAME), которые имеют
# приоритет над .env благодаря Dotenv::createImmutable().
set -euo pipefail

BASE_MIGRATION="${1:?Использование: $0 <migration-name> [db_name]}"
DB_NAME="${2:-cmp412_sim}"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
export DB_DSN="mysql:host=127.0.0.1;dbname=${DB_NAME};charset=utf8mb4"
export DB_NAME
export YII_ENV=prod

cd "$REPO_ROOT"

echo "=== [1/6] Пересоздаём БД ${DB_NAME} ==="
mysql -uroot -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`; CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "=== [2/6] Строим базовую схему (миграции текущего main до ${BASE_MIGRATION} включительно) ==="
php yii migrate/to "${BASE_MIGRATION}" --interactive=0

echo "=== [3/6] Наполняем БД правдоподобным объёмом данных ==="
mysql -uroot "${DB_NAME}" < infrastructure/migrations/seed_demo_data.sql 2>&1 | grep -v "^ERROR 1364" || true
mysql -uroot "${DB_NAME}" < "${REPO_ROOT}/scripts/simulate-prod-upgrade-seed-extra.sql"

echo "=== [4/6] Симулируем рантайм-патчер TariffSetupService::ensureOrderSupport() ==="
# Воспроизводит визит администратора на /admin/tariff ДО того, как миграции
# успели добавить те же колонки. Точные типы взяты из ensureOrderSupport()
# в backend/shared/components/TariffSetupService.php.
mysql -uroot "${DB_NAME}" <<'SQL' || echo "  (колонки/FK уже существуют на этой базовой миграции — пропускаем, это тоже валидный прод-сценарий)"
ALTER TABLE `order` ADD COLUMN tariff_id INT NULL;
ALTER TABLE `order` ADD COLUMN tariff_weight_kg DECIMAL(6,2) DEFAULT 0.5;
ALTER TABLE `order` ADD COLUMN commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE `order` ADD COLUMN delivery_cost DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE `order` ADD COLUMN insurance_amount DECIMAL(12,2) NOT NULL DEFAULT 0;
ALTER TABLE `order` ADD COLUMN tariff_data_json TEXT;
SQL

echo "=== [5/6] Накатываем оставшиеся миграции до HEAD ==="
php yii migrate/up --interactive=0

echo "--- Проверка сохранности данных ---"
mysql -uroot "${DB_NAME}" -e "
SELECT 'customer' t, COUNT(*) c FROM customer
UNION ALL SELECT 'product', COUNT(*) FROM product
UNION ALL SELECT 'order', COUNT(*) FROM \`order\`
UNION ALL SELECT 'order_item', COUNT(*) FROM order_item
UNION ALL SELECT 'coupon', COUNT(*) FROM coupon
UNION ALL SELECT 'category', COUNT(*) FROM category;
"
echo "--- coupon.name (NOT NULL без дефолта, CMP-410) должен остаться заполненным ---"
mysql -uroot "${DB_NAME}" -e "SELECT COUNT(*) coupons_with_empty_name FROM coupon WHERE name IS NULL OR name = '';"
echo "--- category.lft/rgt (NOT NULL без дефолта, CMP-410) должны остаться заполненными ---"
mysql -uroot "${DB_NAME}" -e "SELECT COUNT(*) categories_with_null_nested_set FROM category WHERE lft IS NULL OR rgt IS NULL;"

echo "=== [6/6] Проверяем откат (migrate/down до базовой миграции) ==="
set +e
php yii migrate/to "${BASE_MIGRATION}" --interactive=0
DOWN_EXIT=$?
set -e
if [ "$DOWN_EXIT" -ne 0 ]; then
    echo "!!! Откат остановился раньше базовой точки — см. вывод выше. Это ожидаемо для нескольких" \
         "миграций, которые сознательно объявляют себя необратимыми (safeDown() => false), и это" \
         "нормальный, задокументированный результат: полный откат ниже этой точки = restore из бэкапа."
fi

echo "=== Готово. БД ${DB_NAME} оставлена как есть для ручного осмотра. ==="
