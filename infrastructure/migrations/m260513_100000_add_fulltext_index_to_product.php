<?php

class m260513_100000_add_fulltext_index_to_product extends \yii\db\Migration
{
    public function safeUp()
    {
        $this->execute('ALTER TABLE {{%product}} ADD FULLTEXT INDEX ft_search (`name`, `description`, `brand_name`, `model_name`)');
    }

    public function safeDown()
    {
        $this->execute('ALTER TABLE {{%product}} DROP INDEX ft_search');
    }
}
