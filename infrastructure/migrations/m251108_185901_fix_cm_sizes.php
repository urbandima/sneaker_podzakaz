<?php

use yii\db\Migration;

/**
 * Исправление размеров в см: 265 → 26.5, 270 → 27.0 и т.д.
 * Преобразует трехзначные целые числа в двузначные с десятичной точкой
 */
class m251108_185901_fix_cm_sizes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        echo "Исправление размеров в см...\n";
        
        // 1. Изменяем тип колонки на DECIMAL для поддержки дробных значений
        $this->alterColumn('product_size', 'cm_size', 'DECIMAL(4,1) NULL');
        echo "✅ Тип колонки cm_size изменен на DECIMAL(4,1)\n";
        
        // 2. Исправляем существующие данные: трехзначные числа делим на 10
        // Примеры: 265 → 26.5, 270 → 27.0, 245 → 24.5
        $sql = "UPDATE product_size 
                SET cm_size = cm_size / 10 
                WHERE cm_size >= 100 AND cm_size IS NOT NULL";
        
        $affectedRows = $this->db->createCommand($sql)->execute();
        echo "✅ Исправлено размеров: {$affectedRows}\n";
        
        // 3. Выводим примеры исправленных данных
        $samples = $this->db->createCommand(
            "SELECT id, product_id, cm_size 
             FROM product_size 
             WHERE cm_size IS NOT NULL 
             ORDER BY cm_size 
             LIMIT 10"
        )->queryAll();
        
        echo "\nПримеры исправленных размеров:\n";
        foreach ($samples as $sample) {
            echo "  ID {$sample['id']} (товар #{$sample['product_id']}): {$sample['cm_size']} см\n";
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "Откат миграции fix_cm_sizes...\n";
        
        // Откатываем: умножаем обратно на 10
        $sql = "UPDATE product_size 
                SET cm_size = cm_size * 10 
                WHERE cm_size < 100 AND cm_size IS NOT NULL";
        
        $this->db->createCommand($sql)->execute();
        
        // Возвращаем тип колонки обратно
        $this->alterColumn('product_size', 'cm_size', 'INT NULL');
        
        echo "✅ Откат выполнен\n";
        return true;
    }
}
