<?php

use yii\db\Migration;

class m260504_100000_seed_analytics_app_settings extends Migration
{
    private $keys = ['ga4_id', 'metrika_id', 'meta_pixel_id'];

    public function safeUp()
    {
        $now = time();
        foreach ($this->keys as $key) {
            $exists = $this->db->createCommand(
                'SELECT COUNT(*) FROM {{%app_setting}} WHERE section = :s AND `key` = :k',
                [':s' => 'analytics', ':k' => $key]
            )->queryScalar();

            if (!$exists) {
                $this->insert('{{%app_setting}}', [
                    'section'    => 'analytics',
                    'key'        => $key,
                    'value'      => '',
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function safeDown()
    {
        $this->delete('{{%app_setting}}', [
            'section' => 'analytics',
            'key'     => $this->keys,
        ]);
    }
}
