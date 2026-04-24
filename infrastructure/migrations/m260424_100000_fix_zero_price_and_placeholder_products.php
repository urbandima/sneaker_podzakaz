<?php

use yii\db\Migration;

class m260424_100000_fix_zero_price_and_placeholder_products extends Migration
{
    public function up()
    {
        // A13: deactivate active products with zero/null price
        $this->execute("
            UPDATE product
            SET is_active = 0
            WHERE is_active = 1
              AND (price IS NULL OR price <= 0)
        ");

        // A14: deactivate active products with placeholder or too-short names
        $this->execute("
            UPDATE product
            SET is_active = 0
            WHERE is_active = 1
              AND (
                TRIM(name) = ''
                OR CHAR_LENGTH(TRIM(name)) < 3
                OR LOWER(TRIM(name)) = 'товар'
              )
        ");
    }

    public function down()
    {
        // Not reversible — product activation is a manual action
        echo "Warning: this migration cannot be reversed automatically.\n";
    }
}
