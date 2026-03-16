<?php

namespace app\console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\Product;

/**
 * Округление всех цен до "красивых" значений (399, 409, 419...)
 * 
 * Usage:
 *   php yii round-prices/run
 */
class RoundPricesController extends Controller
{
    public $defaultAction = 'run';
    
    /**
     * Округлить цену до "красивого" значения, заканчивающегося на 9
     * Примеры: 365 → 359, 419 → 419, 324 → 319
     */
    private function roundToPrettyPrice($price)
    {
        $floored = floor($price);
        $result = floor($floored / 10) * 10 + 9;
        
        // Если результат больше исходной цены, отнимаем 10
        if ($result > $floored) {
            $result -= 10;
        }
        
        return $result;
    }
    
    /**
     * Округлить все цены в БД до красивых значений (399, 409, 419...)
     * 
     * @return int
     */
    public function actionRun()
    {
        $this->stdout("╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("║    ОКРУГЛЕНИЕ ЦЕН ДО КРАСИВЫХ ЗНАЧЕНИЙ (X99)        ║\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("\n");
        
        // Статистика
        $stats = [
            'product_sizes_updated' => 0,
            'products_updated' => 0,
            'total_sizes' => 0,
            'total_products' => 0,
        ];
        
        // 1. Округляем цены в ProductSize
        $this->stdout("🔹 Обработка цен в размерах товаров...\n", \yii\helpers\Console::FG_YELLOW);
        
        $productSizes = ProductSize::find()->all();
        $stats['total_sizes'] = count($productSizes);
        
        foreach ($productSizes as $size) {
            $updated = false;
            
            // Округляем price_byn до красивых цен
            if ($size->price_byn) {
                $oldPrice = $size->price_byn;
                $newPrice = $this->roundToPrettyPrice($size->price_byn);
                
                if ($oldPrice != $newPrice) {
                    $size->price_byn = $newPrice;
                    $updated = true;
                    $this->stdout("  ✓ ProductSize #{$size->id}: {$oldPrice} BYN → {$size->price_byn} BYN\n", \yii\helpers\Console::FG_GREEN);
                }
            }
            
            // Округляем price (если есть)
            if ($size->price) {
                $newPrice = $this->roundToPrettyPrice($size->price);
                if ($size->price != $newPrice) {
                    $size->price = $newPrice;
                    $updated = true;
                }
            }
            
            // Округляем price_client_byn (если есть)
            if ($size->price_client_byn) {
                $newPrice = $this->roundToPrettyPrice($size->price_client_byn);
                if ($size->price_client_byn != $newPrice) {
                    $size->price_client_byn = $newPrice;
                    $updated = true;
                }
            }
            
            if ($updated) {
                if ($size->save(false)) {
                    $stats['product_sizes_updated']++;
                }
            }
        }
        
        $this->stdout("\n");
        
        // 2. Округляем цены в Product
        $this->stdout("🔹 Обработка цен в товарах...\n", \yii\helpers\Console::FG_YELLOW);
        
        $products = Product::find()->all();
        $stats['total_products'] = count($products);
        
        foreach ($products as $product) {
            $updated = false;
            
            // Округляем price до красивых цен
            if ($product->price) {
                $oldPrice = $product->price;
                $newPrice = $this->roundToPrettyPrice($product->price);
                
                if ($oldPrice != $newPrice) {
                    $product->price = $newPrice;
                    $updated = true;
                    $this->stdout("  ✓ Product #{$product->id}: {$oldPrice} BYN → {$product->price} BYN\n", \yii\helpers\Console::FG_GREEN);
                }
            }
            
            // Округляем old_price (если есть)
            if ($product->old_price) {
                $newPrice = $this->roundToPrettyPrice($product->old_price);
                if ($product->old_price != $newPrice) {
                    $product->old_price = $newPrice;
                    $updated = true;
                }
            }
            
            if ($updated) {
                if ($product->save(false)) {
                    $stats['products_updated']++;
                }
            }
        }
        
        $this->stdout("\n");
        $this->stdout("╔══════════════════════════════════════════════════════╗\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("║                    РЕЗУЛЬТАТЫ                        ║\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("╚══════════════════════════════════════════════════════╝\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("\n");
        
        $this->stdout("📊 Обработано размеров товаров: {$stats['total_sizes']}\n");
        $this->stdout("✅ Обновлено размеров: {$stats['product_sizes_updated']}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("\n");
        $this->stdout("📊 Обработано товаров: {$stats['total_products']}\n");
        $this->stdout("✅ Обновлено товаров: {$stats['products_updated']}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("\n");
        
        $totalUpdated = $stats['product_sizes_updated'] + $stats['products_updated'];
        $this->stdout("🎉 Всего обновлено цен: {$totalUpdated}\n", \yii\helpers\Console::FG_GREEN);
        $this->stdout("\n");
        
        return ExitCode::OK;
    }
}
