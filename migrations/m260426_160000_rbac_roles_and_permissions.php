<?php

use yii\db\Migration;

/**
 * Seeds RBAC roles + permissions and adds user_role to order_history.
 *
 * Roles (hierarchy):
 *   admin → manager_editor → manager_creator
 *   customer   (standalone, front-end customers)
 *
 * Permissions:
 *   viewOrders, createOrder, editOrder, deleteOrder
 *   manageProducts, manageSettings, manageUsers, manageBuyouts
 *
 * Mapping from existing admin_user.role:
 *   admin / director → admin
 *   manager          → manager_editor
 *   logist           → manager_creator
 */
class m260426_160000_rbac_roles_and_permissions extends Migration
{
    private array $roles = [
        'customer',
        'manager_creator',
        'manager_editor',
        'admin',
    ];

    private array $permissions = [
        'viewOrders'      => 'Просмотр заказов',
        'createOrder'     => 'Создание заказов',
        'editOrder'       => 'Редактирование заказов',
        'deleteOrder'     => 'Удаление заказов',
        'manageProducts'  => 'Управление каталогом',
        'manageSettings'  => 'Управление настройками',
        'manageUsers'     => 'Управление пользователями',
        'manageBuyouts'   => 'Управление выкупами',
    ];

    // role → list of permissions it directly has
    private array $rolePermissions = [
        'customer'        => [],
        'manager_creator' => ['viewOrders', 'createOrder', 'manageBuyouts'],
        'manager_editor'  => ['editOrder', 'deleteOrder', 'manageProducts'],
        'admin'           => ['manageSettings', 'manageUsers'],
    ];

    // role → parent role (inherits all parent perms)
    private array $roleHierarchy = [
        'manager_editor' => 'manager_creator',
        'admin'          => 'manager_editor',
    ];

    public function safeUp()
    {
        // 1 — add user_role to order_history
        if (!$this->columnExists('order_history', 'user_role')) {
            $this->addColumn('order_history', 'user_role', $this->string(50)->null()->after('user_name'));
        }

        $now = date('Y-m-d H:i:s');

        // 2 — insert permissions
        foreach ($this->permissions as $name => $description) {
            if (!$this->itemExists($name)) {
                $this->insert('auth_item', [
                    'name'        => $name,
                    'type'        => 2, // permission
                    'description' => $description,
                    'created_at'  => time(),
                    'updated_at'  => time(),
                ]);
            }
        }

        // 3 — insert roles
        foreach ($this->roles as $role) {
            if (!$this->itemExists($role)) {
                $this->insert('auth_item', [
                    'name'       => $role,
                    'type'       => 1, // role
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }

        // 4 — assign permissions to roles
        foreach ($this->rolePermissions as $role => $perms) {
            foreach ($perms as $perm) {
                if (!$this->childExists($role, $perm)) {
                    $this->insert('auth_item_child', ['parent' => $role, 'child' => $perm]);
                }
            }
        }

        // 5 — set up role hierarchy
        foreach ($this->roleHierarchy as $child => $parent) {
            if (!$this->childExists($parent, $child)) {
                $this->insert('auth_item_child', ['parent' => $parent, 'child' => $child]);
            }
        }

        // 6 — seed auth_assignment from existing admin_user.role
        $users = (new \yii\db\Query())
            ->select(['id', 'role'])
            ->from('admin_user')
            ->where(['status' => 10])
            ->all();

        $roleMap = [
            'admin'    => 'admin',
            'director' => 'admin',
            'manager'  => 'manager_editor',
            'logist'   => 'manager_creator',
        ];

        foreach ($users as $user) {
            $rbacRole = $roleMap[$user['role']] ?? 'manager_creator';
            if (!$this->assignmentExists((int)$user['id'], $rbacRole)) {
                $this->insert('auth_assignment', [
                    'item_name'  => $rbacRole,
                    'user_id'    => (string)$user['id'],
                    'created_at' => time(),
                ]);
            }
        }
    }

    public function safeDown()
    {
        // Remove auth_item_child entries
        foreach ($this->roleHierarchy as $child => $parent) {
            $this->delete('auth_item_child', ['parent' => $parent, 'child' => $child]);
        }
        foreach ($this->rolePermissions as $role => $perms) {
            foreach ($perms as $perm) {
                $this->delete('auth_item_child', ['parent' => $role, 'child' => $perm]);
            }
        }

        // Remove assignments seeded by this migration
        foreach (array_values($this->roleHierarchy) + array_keys($this->roleHierarchy) as $role) {
            $this->delete('auth_assignment', ['item_name' => $role]);
        }

        foreach ($this->roles as $role) {
            $this->delete('auth_item', ['name' => $role, 'type' => 1]);
        }
        foreach (array_keys($this->permissions) as $perm) {
            $this->delete('auth_item', ['name' => $perm, 'type' => 2]);
        }

        if ($this->columnExists('order_history', 'user_role')) {
            $this->dropColumn('order_history', 'user_role');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $cols = $this->db->getTableSchema($table)?->columnNames ?? [];
        return in_array($column, $cols, true);
    }

    private function itemExists(string $name): bool
    {
        return (bool)(new \yii\db\Query())
            ->from('auth_item')->where(['name' => $name])->exists();
    }

    private function childExists(string $parent, string $child): bool
    {
        return (bool)(new \yii\db\Query())
            ->from('auth_item_child')->where(['parent' => $parent, 'child' => $child])->exists();
    }

    private function assignmentExists(int $userId, string $role): bool
    {
        return (bool)(new \yii\db\Query())
            ->from('auth_assignment')->where(['user_id' => (string)$userId, 'item_name' => $role])->exists();
    }
}
