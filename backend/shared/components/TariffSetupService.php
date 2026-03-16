<?php

/**
 * TariffSetupService — Сервис автоматической настройки таблиц тарифов
 * 
 * НАЗНАЧЕНИЕ:
 * Гарантирует доступность таблицы тарифов без ручных миграций.
 * Автоматическое создание и обновление схемы БД.
 * 
 * ФУНКЦИИ:
 * - ensureSchema(): проверка и создание всех сущностей
 * - ensureTariffTable(): создание таблицы тарифов
 * - ensureOrderSupport(): поддержка полей в заказах
 * - seedDefaults(): заполнение дефолтными значениями
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * ```php
 * // В конфигурации приложения
 * TariffSetupService::ensureSchema();
 * ```
 * 
 * ОСОБЕННОСТИ:
 * - Автоматическое создание таблицы при отсутствии
 * - Добавление недостающих колонок
 * - Создание индексов
 * - Заполнение дефолтными тарифами
 * - Безопасное обновление схемы
 */
namespace app\backend\shared\components;

use Yii;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\IndexConstraint;
use yii\db\Migration;
use yii\db\Schema;
use yii\db\TableSchema;

/**
 * TariffSetupService - гарантирует доступность таблицы тарифов без ручных миграций.
 */
class TariffSetupService
{
    /**
     * Убедиться, что все сущности, необходимые для тарифов, готовы к работе.
     */
    public static function ensureSchema(): bool
    {
        return self::ensureTariffTable() && self::ensureOrderSupport();
    }

    /**
     * Подготовить таблицу тарифов.
     */
    public static function ensureTariffTable(): bool
    {
        $db = Yii::$app->db;
        $table = '{{%tariff}}';

        try {
            $schema = $db->schema->getTableSchema($table, true);
            $migration = self::createMigration($db);

            if ($schema === null) {
                self::createTable($migration, $table);
                self::createIndexes($migration, $table);
                self::seedDefaults($db, $table);
            } else {
                self::ensureColumns($migration, $schema, $table);
                self::createIndexes($migration, $table);
                self::seedDefaultsIfEmpty($db, $table);
            }

            return true;
        } catch (\Throwable $e) {
            Yii::error('Не удалось подготовить таблицу тарифов: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Убедиться, что таблица заказов поддерживает расчеты тарифов.
     */
    public static function ensureOrderSupport(): bool
    {
        $db = Yii::$app->db;
        $table = '{{%order}}';

        try {
            $schema = $db->schema->getTableSchema($table, true);
            if ($schema === null) {
                return false;
            }

            $migration = self::createMigration($db);
            $columns = [
                'tariff_id' => $migration->integer(),
                'tariff_weight_kg' => $migration->decimal(6, 2)->defaultValue(0.5),
                'commission_amount' => $migration->decimal(12, 2)->notNull()->defaultValue(0),
                'delivery_cost' => $migration->decimal(12, 2)->notNull()->defaultValue(0),
                'insurance_amount' => $migration->decimal(12, 2)->notNull()->defaultValue(0),
                'tariff_data_json' => $migration->text(),
            ];

            foreach ($columns as $name => $definition) {
                if (!isset($schema->columns[$name])) {
                    $migration->addColumn($table, $name, $definition);
                }
            }

            $db->schema->refreshTableSchema($table);
            $schema = $db->schema->getTableSchema($table, true);

            self::ensureOrderTariffForeignKey($migration, $schema, $table);

            return true;
        } catch (\Throwable $e) {
            Yii::error('Не удалось подготовить поддержку тарифов в заказах: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    private static function ensureOrderTariffForeignKey(Migration $migration, TableSchema $schema, string $table): void
    {
        $fkName = 'fk-order-tariff';
        if (isset($schema->foreignKeys[$fkName])) {
            return;
        }

        try {
            $migration->addForeignKey(
                $fkName,
                $table,
                'tariff_id',
                '{{%tariff}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        } catch (\Throwable $e) {
            Yii::warning('Не удалось создать FK для tariff_id: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Создание инстанса миграции с кастомным соединением.
     */
    private static function createMigration(Connection $db): Migration
    {
        return new class($db) extends Migration {
            public function __construct(Connection $db, $config = [])
            {
                $this->db = $db;
                parent::__construct($config);
            }
        };
    }

    /**
     * Полный набор колонок таблицы.
     *
     * @return array<string, mixed>
     */
    private static function columnDefinitions(Migration $migration): array
    {
        return [
            'name' => $migration->string(100)->notNull(),
            'description' => $migration->text(),
            'commission_percent' => $migration->decimal(6, 2)->notNull()->defaultValue(0),
            'commission_fixed' => $migration->decimal(10, 2)->notNull()->defaultValue(0),
            'delivery_cost_per_kg' => $migration->decimal(10, 2)->notNull()->defaultValue(0),
            'insurance_percent' => $migration->decimal(6, 2)->notNull()->defaultValue(0),
            'min_order_amount' => $migration->decimal(12, 2)->notNull()->defaultValue(0),
            'currency' => $migration->string(3)->notNull()->defaultValue('CNY'),
            'exchange_rate' => $migration->decimal(12, 4)->notNull()->defaultValue(1),
            'is_active' => $migration->boolean()->notNull()->defaultValue(true),
            'sort_order' => $migration->integer()->notNull()->defaultValue(100),
            'created_at' => $migration->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $migration->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ];
    }

    private static function createTable(Migration $migration, string $table): void
    {
        $columns = array_merge(
            ['id' => $migration->primaryKey()],
            self::columnDefinitions($migration)
        );

        $migration->createTable($table, $columns);
    }

    private static function ensureColumns(Migration $migration, TableSchema $schema, string $table): void
    {
        $definitions = self::columnDefinitions($migration);

        foreach ($definitions as $name => $definition) {
            if (!isset($schema->columns[$name])) {
                $migration->addColumn($table, $name, $definition);
            }
        }
    }

    private static function createIndexes(Migration $migration, string $table): void
    {
        $db = Yii::$app->db;
        $existing = [];

        try {
            $schemaIndexes = $db->schema->getTableIndexes($table, true);
            foreach ($schemaIndexes as $index) {
                if ($index instanceof IndexConstraint) {
                    $existing[] = $index->name;
                } elseif (is_array($index) && isset($index['name'])) {
                    $existing[] = $index['name'];
                }
            }
        } catch (NotSupportedException $e) {
            Yii::warning('getTableIndexes() не поддерживается драйвером: ' . $e->getMessage(), __METHOD__);
        }

        $indexes = [
            'idx-tariff-sort_order' => 'sort_order',
            'idx-tariff-is_active' => 'is_active',
        ];

        foreach ($indexes as $name => $column) {
            if (in_array($name, $existing, true)) {
                continue;
            }
            try {
                $migration->createIndex($name, $table, $column);
            } catch (\Throwable $e) {
                Yii::warning(sprintf('Не удалось создать индекс %s: %s', $name, $e->getMessage()), __METHOD__);
            }
        }
    }

    private static function seedDefaults(Connection $db, string $table): void
    {
        $defaults = self::defaultTariffs();
        $columns = array_keys($defaults[0]);

        $db->createCommand()
            ->batchInsert($table, $columns, array_map('array_values', $defaults))
            ->execute();
    }

    private static function seedDefaultsIfEmpty(Connection $db, string $table): void
    {
        $count = (int)$db->createCommand(
            sprintf('SELECT COUNT(*) FROM %s', $db->quoteTableName($table))
        )->queryScalar();

        if ($count === 0) {
            self::seedDefaults($db, $table);
        }
    }

    /**
     * Набор начальных тарифов.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function defaultTariffs(): array
    {
        $now = date('Y-m-d H:i:s');
        return [
            [
                'name' => 'Стандарт',
                'description' => 'Стандартный тариф для предзаказов',
                'commission_percent' => 10.00,
                'commission_fixed' => 0,
                'delivery_cost_per_kg' => 15.00,
                'insurance_percent' => 2.00,
                'min_order_amount' => 0,
                'currency' => 'CNY',
                'exchange_rate' => 12.50,
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Экспресс',
                'description' => 'Быстрая доставка с повышенной комиссией',
                'commission_percent' => 15.00,
                'commission_fixed' => 0,
                'delivery_cost_per_kg' => 25.00,
                'insurance_percent' => 3.00,
                'min_order_amount' => 0,
                'currency' => 'CNY',
                'exchange_rate' => 12.50,
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Эконом',
                'description' => 'Экономичный тариф с увеличенным сроком доставки',
                'commission_percent' => 7.00,
                'commission_fixed' => 0,
                'delivery_cost_per_kg' => 10.00,
                'insurance_percent' => 1.00,
                'min_order_amount' => 0,
                'currency' => 'CNY',
                'exchange_rate' => 12.50,
                'is_active' => 1,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
    }
}
