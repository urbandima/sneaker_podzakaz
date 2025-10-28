<?php

use yii\db\Migration;

class m251028_124700_add_canceled_status extends Migration
{
    public function safeUp()
    {
        // Проверим, что таблица существует
        if ($this->db->schema->getTableSchema('{{%order_status}}', true) === null) {
            throw new \RuntimeException('Таблица order_status не найдена. Сначала выполните предыдущие миграции.');
        }

        // Если статус уже есть — выходим
        $exists = (new \yii\db\Query())
            ->from('{{%order_status}}')
            ->where(['key' => 'canceled'])
            ->exists();
        if ($exists) {
            return;
        }

        // Определим максимальное значение sort, чтобы поставить "отменен" в конец
        $maxSort = (new \yii\db\Query())
            ->from('{{%order_status}}')
            ->max('sort');
        $sort = is_numeric($maxSort) ? ((int)$maxSort + 1) : 999;

        $this->insert('{{%order_status}}', [
            'key' => 'canceled',
            'label' => 'Отменен',
            'sort' => $sort,
            'logist_available' => 0, // логист не меняет на отменен
        ]);
    }

    public function safeDown()
    {
        // Не удаляем статус, если он помечен как базовый и используется в коде
        // По требованию можно разрешить откат:
        $this->delete('{{%order_status}}', ['key' => 'canceled']);
    }
}
