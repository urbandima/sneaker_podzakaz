<?php

use yii\db\Migration;

class m260420_130000_add_ms_deal_link_to_order extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%order}}', 'ms_deal_link', $this->string(500)->null()->comment('Ссылка на сделку AmoCRM'));
        $this->addColumn('{{%order}}', 'amocrm_source', $this->string(50)->null()->comment('Источник AmoCRM (amocrm, widget и т.д.)'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%order}}', 'amocrm_source');
        $this->dropColumn('{{%order}}', 'ms_deal_link');
    }
}
