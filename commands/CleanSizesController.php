<?php
namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\ProductSize;

/**
 * Очистка некорректных размеров в БД
 */
class CleanSizesController extends Controller
{
    /**
     * Очистка некорректных размеров CM (< 20 или > 35)
     */
    public function actionCm()
    {
        echo "=== ОЧИСТКА НЕКОРРЕКТНЫХ РАЗМЕРОВ CM ===\n\n";
        
        // Находим некорректные размеры
        $incorrectSizes = Yii::$app->db->createCommand(
            "SELECT id, product_id, cm_size 
             FROM product_size 
             WHERE cm_size IS NOT NULL 
             AND (CAST(cm_size AS DECIMAL(10,2)) < 20 OR CAST(cm_size AS DECIMAL(10,2)) > 35)"
        )->queryAll();
        
        $count = count($incorrectSizes);
        
        if ($count === 0) {
            echo "✅ Некорректных размеров не найдено!\n";
            return 0;
        }
        
        echo "Найдено {$count} записей с некорректными размерами CM:\n";
        
        // Группируем по размерам для отчета
        $sizeGroups = [];
        foreach ($incorrectSizes as $row) {
            $size = $row['cm_size'];
            if (!isset($sizeGroups[$size])) {
                $sizeGroups[$size] = 0;
            }
            $sizeGroups[$size]++;
        }
        
        arsort($sizeGroups);
        foreach ($sizeGroups as $size => $cnt) {
            echo sprintf("  • %s см: %d записей\n", $size, $cnt);
        }
        
        echo "\nОчищаем (устанавливаем cm_size = NULL)...\n";
        
        // Обнуляем некорректные размеры вместо удаления записей
        $updated = Yii::$app->db->createCommand()
            ->update('product_size', 
                ['cm_size' => null],
                'cm_size IS NOT NULL AND (CAST(cm_size AS DECIMAL(10,2)) < 20 OR CAST(cm_size AS DECIMAL(10,2)) > 35)'
            )
            ->execute();
        
        echo "✅ Обновлено {$updated} записей\n";
        echo "\n=== ОЧИСТКА ЗАВЕРШЕНА ===\n";
        
        // Очищаем кэш фильтров
        Yii::$app->cache->flush();
        echo "✅ Кэш очищен\n";
        
        return 0;
    }
    
    /**
     * Показать статистику по размерам
     */
    public function actionStats()
    {
        echo "=== СТАТИСТИКА РАЗМЕРОВ ===\n\n";
        
        // CM размеры
        echo "--- РАЗМЕРЫ CM ---\n";
        $cmSizes = Yii::$app->db->createCommand(
            "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT cm_size) as unique_sizes,
                MIN(CAST(cm_size AS DECIMAL(10,2))) as min_size,
                MAX(CAST(cm_size AS DECIMAL(10,2))) as max_size
             FROM product_size 
             WHERE cm_size IS NOT NULL"
        )->queryOne();
        
        echo sprintf("Всего записей: %d\n", $cmSizes['total']);
        echo sprintf("Уникальных размеров: %d\n", $cmSizes['unique_sizes']);
        echo sprintf("Диапазон: %.1f - %.1f см\n", $cmSizes['min_size'], $cmSizes['max_size']);
        
        // Некорректные
        $incorrect = Yii::$app->db->createCommand(
            "SELECT COUNT(*) as cnt
             FROM product_size 
             WHERE cm_size IS NOT NULL 
             AND (CAST(cm_size AS DECIMAL(10,2)) < 20 OR CAST(cm_size AS DECIMAL(10,2)) > 35)"
        )->queryScalar();
        
        echo sprintf("Некорректных (< 20 или > 35): %d ", $incorrect);
        if ($incorrect > 0) {
            echo "❌\n";
        } else {
            echo "✅\n";
        }
        
        echo "\n--- ТОП-10 РАЗМЕРОВ CM ---\n";
        $topSizes = Yii::$app->db->createCommand(
            "SELECT cm_size, COUNT(*) as cnt 
             FROM product_size 
             WHERE cm_size IS NOT NULL 
             GROUP BY cm_size 
             ORDER BY cnt DESC 
             LIMIT 10"
        )->queryAll();
        
        foreach ($topSizes as $row) {
            $status = ($row['cm_size'] >= 20 && $row['cm_size'] <= 35) ? '✅' : '❌';
            echo sprintf("  %s см: %d записей %s\n", $row['cm_size'], $row['cnt'], $status);
        }
        
        return 0;
    }
}
