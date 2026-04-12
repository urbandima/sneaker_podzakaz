<?php

use yii\db\Migration;

/**
 * Добавляет недостающие столбцы в таблицу import_source
 */
class m260412_100000_add_missing_columns_to_import_source extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%import_source}}');
        $columns = array_keys($schema->columns);

        if (!in_array('currency_code', $columns)) {
            $this->addColumn('{{%import_source}}', 'currency_code', $this->string(10)->defaultValue('CNY')->comment('Код валюты источника')->after('is_active'));
        }
        if (!in_array('parse_delay_min', $columns)) {
            $this->addColumn('{{%import_source}}', 'parse_delay_min', $this->integer()->defaultValue(1)->comment('Мин. задержка (сек)')->after('currency_code'));
        }
        if (!in_array('parse_delay_max', $columns)) {
            $this->addColumn('{{%import_source}}', 'parse_delay_max', $this->integer()->defaultValue(3)->comment('Макс. задержка (сек)')->after('parse_delay_min'));
        }
        if (!in_array('proxy_enabled', $columns)) {
            $this->addColumn('{{%import_source}}', 'proxy_enabled', $this->boolean()->defaultValue(false)->comment('Использовать прокси')->after('parse_delay_max'));
        }
        if (!in_array('proxy_list', $columns)) {
            $this->addColumn('{{%import_source}}', 'proxy_list', $this->text()->null()->comment('JSON список прокси')->after('proxy_enabled'));
        }
        if (!in_array('proxy_rotation', $columns)) {
            $this->addColumn('{{%import_source}}', 'proxy_rotation', $this->string(20)->defaultValue('round_robin')->comment('Тип ротации прокси')->after('proxy_list'));
        }
        if (!in_array('captcha_service', $columns)) {
            $this->addColumn('{{%import_source}}', 'captcha_service', $this->string(50)->defaultValue('none')->comment('Сервис CAPTCHA')->after('proxy_rotation'));
        }
        if (!in_array('captcha_api_key', $columns)) {
            $this->addColumn('{{%import_source}}', 'captcha_api_key', $this->string(255)->null()->comment('API ключ CAPTCHA')->after('captcha_service'));
        }
        if (!in_array('captcha_fallback_service', $columns)) {
            $this->addColumn('{{%import_source}}', 'captcha_fallback_service', $this->string(50)->null()->comment('Резервный сервис CAPTCHA')->after('captcha_api_key'));
        }
        if (!in_array('captcha_fallback_api_key', $columns)) {
            $this->addColumn('{{%import_source}}', 'captcha_fallback_api_key', $this->string(255)->null()->comment('API ключ резервного сервиса')->after('captcha_fallback_service'));
        }
        if (!in_array('settings', $columns)) {
            $this->addColumn('{{%import_source}}', 'settings', $this->text()->null()->comment('JSON настройки')->after('captcha_fallback_api_key'));
        }
        if (!in_array('last_run_at', $columns)) {
            $this->addColumn('{{%import_source}}', 'last_run_at', $this->timestamp()->null()->comment('Последний запуск'));
        }
        if (!in_array('total_products_parsed', $columns)) {
            $this->addColumn('{{%import_source}}', 'total_products_parsed', $this->integer()->defaultValue(0)->comment('Всего спарсено товаров'));
        }
        if (!in_array('successful_runs', $columns)) {
            $this->addColumn('{{%import_source}}', 'successful_runs', $this->integer()->defaultValue(0)->comment('Успешных запусков'));
        }
        if (!in_array('failed_runs', $columns)) {
            $this->addColumn('{{%import_source}}', 'failed_runs', $this->integer()->defaultValue(0)->comment('Неудачных запусков'));
        }
    }

    public function safeDown()
    {
        $cols = ['failed_runs', 'successful_runs', 'total_products_parsed', 'last_run_at', 'settings',
            'captcha_fallback_api_key', 'captcha_fallback_service', 'captcha_api_key', 'captcha_service',
            'proxy_rotation', 'proxy_list', 'proxy_enabled', 'parse_delay_max', 'parse_delay_min', 'currency_code'];
        foreach ($cols as $col) {
            $this->dropColumn('{{%import_source}}', $col);
        }
    }
}
