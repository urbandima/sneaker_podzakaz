<?php

use yii\db\Migration;

/**
 * Добавляет поле cover_image (обложка/баннер бренда) в таблицу brand.
 */
class m260427_010000_add_cover_image_to_brand extends Migration
{
    public function safeUp()
    {
        // Check if column already exists
        $columns = $this->db->getTableSchema('brand')->columns;
        if (!isset($columns['cover_image'])) {
            $this->addColumn('brand', 'cover_image', $this->string(255)->null()->after('logo_url')->comment('Обложка бренда (баннер)'));
        }
    }

    public function safeDown()
    {
        $columns = $this->db->getTableSchema('brand')->columns;
        if (isset($columns['cover_image'])) {
            $this->dropColumn('brand', 'cover_image');
        }
    }
}
