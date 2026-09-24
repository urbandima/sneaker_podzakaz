<?php

use yii\db\Migration;

/**
 * CMP-451: provider_status_id — VARCHAR(20), но реальные коды статусов CDEK API v2
 * строковые и длиннее (напр. TAKEN_BY_TRANSPORTER_FROM_RECIPIENT_CITY — 41 символ).
 * При 20 символах разные коды CDEK усекаются до одинаковой подстроки и INSERT IGNORE
 * в m260924_150000 тихо теряет коллизирующие статусы. dobropost/europochta/belpochta
 * используют короткие числовые коды и в 64 символа укладываются без изменений.
 */
class m260924_145000_widen_delivery_status_mapping_provider_status_id extends Migration
{
    public function safeUp()
    {
        $this->alterColumn(
            '{{%delivery_status_mapping}}',
            'provider_status_id',
            $this->string(64)->notNull()->comment('ID статуса у провайдера')
        );
    }

    public function safeDown()
    {
        $this->alterColumn(
            '{{%delivery_status_mapping}}',
            'provider_status_id',
            $this->string(20)->notNull()->comment('ID статуса у провайдера')
        );
    }
}
