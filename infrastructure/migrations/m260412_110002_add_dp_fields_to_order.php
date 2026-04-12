<?php

use yii\db\Migration;

/**
 * Добавляет поля Таможня:ДП в таблицу order
 */
class m260412_110002_add_dp_fields_to_order extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'dp_shipment_id',           $this->integer()->null()->comment('ID шипмента в Таможня:ДП'));
        $this->addColumn('{{%order}}', 'dp_track_number',          $this->string(100)->null()->comment('Трек-номер Таможня:ДП'));
        $this->addColumn('{{%order}}', 'dp_status',                $this->string(50)->null()->comment('Текущий статус Таможня:ДП'));
        $this->addColumn('{{%order}}', 'dp_status_date',           'DATETIME NULL COMMENT \'Дата последнего статуса Таможня:ДП\'');
        $this->addColumn('{{%order}}', 'dp_sent_at',               $this->integer()->null()->comment('Unix-timestamp отправки в Таможня:ДП'));
        $this->addColumn('{{%order}}', 'dp_response',              'JSON NULL COMMENT \'Последний ответ API Таможня:ДП\'');
        $this->addColumn('{{%order}}', 'passport_submitted_at',    $this->integer()->null()->comment('Unix-timestamp подачи паспортных данных'));
        $this->addColumn('{{%order}}', 'passport_validated',       $this->tinyInteger(1)->null()->comment('Паспорт прошёл проверку DaData (null=не проверялся)'));
        $this->addColumn('{{%order}}', 'estimated_delivery_date',  'DATE NULL COMMENT \'Расчётная дата доставки\'');
        $this->addColumn('{{%order}}', 'local_delivery_status',    $this->string(50)->null()->comment('Статус локальной доставки (Европочта/Белпочта)'));

        $this->createIndex('idx-order-dp_shipment_id',  '{{%order}}', 'dp_shipment_id');
        $this->createIndex('idx-order-dp_track_number', '{{%order}}', 'dp_track_number');
        $this->createIndex('idx-order-dp_status',       '{{%order}}', 'dp_status');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-order-dp_shipment_id',  '{{%order}}');
        $this->dropIndex('idx-order-dp_track_number', '{{%order}}');
        $this->dropIndex('idx-order-dp_status',       '{{%order}}');

        $this->dropColumn('{{%order}}', 'dp_shipment_id');
        $this->dropColumn('{{%order}}', 'dp_track_number');
        $this->dropColumn('{{%order}}', 'dp_status');
        $this->dropColumn('{{%order}}', 'dp_status_date');
        $this->dropColumn('{{%order}}', 'dp_sent_at');
        $this->dropColumn('{{%order}}', 'dp_response');
        $this->dropColumn('{{%order}}', 'passport_submitted_at');
        $this->dropColumn('{{%order}}', 'passport_validated');
        $this->dropColumn('{{%order}}', 'estimated_delivery_date');
        $this->dropColumn('{{%order}}', 'local_delivery_status');
    }
}
