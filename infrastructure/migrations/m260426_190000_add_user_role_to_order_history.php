<?php

use yii\db\Migration;

class m260426_190000_add_user_role_to_order_history extends Migration
{
    public function safeUp()
    {
        // Колонка уже добавляется миграцией m260426_160000_rbac_roles_and_permissions (и order_history
        // не содержит user_name — after() указывал на несуществующую колонку).
        $schema = $this->db->schema->getTableSchema('{{%order_history}}', true);
        if ($schema !== null && in_array('user_role', $schema->columnNames, true)) {
            echo "    > skipped: order_history.user_role уже создана миграцией m260426_160000_rbac_roles_and_permissions\n";
            return true;
        }

        $this->addColumn('order_history', 'user_role', $this->string(255)->null());
    }

    public function safeDown()
    {
        // Не удаляем: колонка принадлежит m260426_160000_rbac_roles_and_permissions.
    }
}
