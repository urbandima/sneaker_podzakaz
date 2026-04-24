<?php

use yii\db\Migration;

class m260424_200000_add_amocrm_fields_to_order extends Migration
{
    public function up()
    {
        // amocrm_source: which AmoCRM pipeline/source the order came from
        $this->addColumn('{{%order}}', 'amocrm_source',   $this->string(50)->null()->after('source'));
        // amocrm_deal_id: linked AmoCRM deal ID for two-way sync
        $this->addColumn('{{%order}}', 'amocrm_deal_id',  $this->integer()->null()->after('amocrm_source'));
    }

    public function down()
    {
        $this->dropColumn('{{%order}}', 'amocrm_deal_id');
        $this->dropColumn('{{%order}}', 'amocrm_source');
    }
}
