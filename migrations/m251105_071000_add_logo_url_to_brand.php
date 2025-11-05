<?php

use yii\db\Migration;

/**
 * Добавление поля logo_url в таблицу brand для хранения URL логотипов из Poizon
 */
class m251105_071000_add_logo_url_to_brand extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%brand}}', 'logo_url', $this->string(500)->after('logo')->comment('URL логотипа (из Poizon или внешний)'));
        
        return true;
    }

    public function safeDown()
    {
        $this->dropColumn('{{%brand}}', 'logo_url');
        
        return true;
    }
}
