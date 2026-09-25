<?php

use yii\db\Migration;

/**
 * CMP-470: `SeoController::actionAltTexts()`/`actionUpdateImageAlt()` читают/пишут
 * `ProductImage::$alt_text`, но `product_image` никогда не имел такой колонки
 * (см. m250101_000000_create_base_tables.php — только id/product_id/image/is_main/
 * sort_order/created_at). Живым прогоном подтверждено: GET /admin/seo/alt-texts
 * и POST /admin/seo/update-image-alt оба падали 500
 * ("Unknown column 'alt_text' in 'field list'" / UnknownPropertyException на AR,
 * у которой нет такого атрибута) — вся страница управления ALT-текстами была
 * недоступна в принципе. Тот же класс бага, что и остальные схема-дрифты
 * (CMP-410/417): код написан под колонку, которая никогда не создавалась.
 */
class m260925_080000_add_alt_text_to_product_image extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%product_image}}',
            'alt_text',
            $this->string(255)->null()->after('image')->comment('ALT текст для SEO/доступности')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%product_image}}', 'alt_text');
    }
}
