<?php

use yii\db\Migration;

/**
 * Добавляет недостающие поля в таблицы order и customer
 */
class m240324_fix_missing_fields extends Migration
{
    public function safeUp()
    {
        // Добавляем недостающие поля в таблицу order
        $this->addColumn('{{%order}}', 'delivery_method', $this->string(50)->null()->after('delivery_country'));
        $this->addColumn('{{%order}}', 'delivery_address', $this->text()->null()->after('delivery_method'));
        
        // Добавляем поле status в таблицу customer
        $this->addColumn('{{%customer}}', 'status', $this->tinyInteger(1)->defaultValue(1)->after('email'));
        
        echo "Миграция успешно выполнена.\n";
        return true;
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'delivery_method');
        $this->dropColumn('{{%order}}', 'delivery_address');
        $this->dropColumn('{{%customer}}', 'status');
        
        echo "Миграция откатена.\n";
        return true;
    }
}
