<?php

use yii\db\Migration;

class m251028_131700_remove_accepted_status extends Migration
{
    public function safeUp()
    {
        // Проверяем, есть ли заказы со статусом 'accepted'
        $count = (new \yii\db\Query())
            ->from('{{%order}}')
            ->where(['status' => 'accepted'])
            ->count();

        if ($count > 0) {
            echo "Внимание! Обнаружено {$count} заказов со статусом 'accepted'.\n";
            echo "Переводим их в статус 'paid' (Заказ оплачен)...\n";
            
            // Переводим все заказы со статусом 'accepted' в 'paid'
            $this->update('{{%order}}', ['status' => 'paid'], ['status' => 'accepted']);
            
            echo "Заказы успешно переведены.\n";
        }

        // Удаляем статус из таблицы order_status
        $this->delete('{{%order_status}}', ['key' => 'accepted']);
        
        echo "Статус 'accepted' (Заказ принят) успешно удален.\n";
    }

    public function safeDown()
    {
        // Восстанавливаем статус при откате
        $this->insert('{{%order_status}}', [
            'key' => 'accepted',
            'label' => 'Заказ принят',
            'sort' => 2,
            'logist_available' => 0,
        ]);
    }
}
