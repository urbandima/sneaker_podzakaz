<?php

namespace app\console\controllers;

use yii\console\Controller;
use yii\console\ExitCode;
use app\infrastructure\services\ImageOptimizationService;
use app\infrastructure\services\ProductionCacheService;

/**
 * Production команды для продакшена
 */
class ProductionController extends Controller
{
    private $imageOptimizer;
    private $cacheService;
    
    public function init()
    {
        parent::init();
        $this->imageOptimizer = new ImageOptimizationService();
        $this->cacheService = new ProductionCacheService();
    }
    
    /**
     * Оптимизация изображений
     */
    public function actionOptimizeImages($path = 'frontend/web/images')
    {
        $this->stdout("🖼️ Оптимизация изображений в: {$path}\n", \yii\helpers\Console::FG_YELLOW);
        
        if (!is_dir($path)) {
            $this->stdout("❌ Директория не найдена: {$path}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        $optimized = $this->imageOptimizer->optimizeDirectory($path);
        
        $this->stdout("✅ Оптимизировано изображений: {$optimized}\n", \yii\helpers\Console::FG_GREEN);
        return ExitCode::OK;
    }
    
    /**
     * Очистка кэша
     */
    public function actionClearCache($type = 'all')
    {
        $this->stdout("🧹 Очистка кэша...\n", \yii\helpers\Console::FG_YELLOW);
        
        switch ($type) {
            case 'catalog':
                $this->cacheService->clearCatalogCache();
                $this->stdout("✅ Кэш каталога очищен\n", \yii\helpers\Console::FG_GREEN);
                break;
            case 'all':
                Yii::$app->cache->flush();
                $this->stdout("✅ Весь кэш очищен\n", \yii\helpers\Console::FG_GREEN);
                break;
            default:
                $this->stdout("❌ Неизвестный тип кэша: {$type}\n", \yii\helpers\Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Статистика кэша
     */
    public function actionCacheStats()
    {
        $this->stdout("📊 Статистика кэша:\n", \yii\helpers\Console::FG_YELLOW);
        
        $stats = $this->cacheService->getCacheStats();
        
        $this->stdout("Redis ключей: {$stats['redis_keys']}\n", \yii\helpers\Console::FG_CYAN);
        $this->stdout("Hit rate: {$stats['cache_hit_rate']}%\n", \yii\helpers\Console::FG_CYAN);
        
        if (isset($stats['redis_memory']['used_memory_human'])) {
            $this->stdout("Память Redis: {$stats['redis_memory']['used_memory_human']}\n", \yii\helpers\Console::FG_CYAN);
        }
        
        return ExitCode::OK;
    }
    
    /**
     * Проверка здоровья системы
     */
    public function actionHealthCheck()
    {
        $this->stdout("🏥 Проверка здоровья системы...\n", \yii\helpers\Console::FG_YELLOW);
        
        $checks = [];
        
        // Проверка базы данных
        try {
            Yii::$app->db->createCommand('SELECT 1')->queryOne();
            $checks['db'] = '✅ OK';
        } catch (\Exception $e) {
            $checks['db'] = '❌ Error';
        }
        
        // Проверка Redis
        try {
            Yii::$app->redis->ping();
            $checks['redis'] = '✅ OK';
        } catch (\Exception $e) {
            $checks['redis'] = '❌ Error';
        }
        
        // Проверка кэша
        try {
            Yii::$app->cache->set('health_check', 'ok', 10);
            $result = Yii::$app->cache->get('health_check');
            $checks['cache'] = $result === 'ok' ? '✅ OK' : '❌ Error';
        } catch (\Exception $e) {
            $checks['cache'] = '❌ Error';
        }
        
        foreach ($checks as $service => $status) {
            $this->stdout("{$service}: {$status}\n", 
                strpos($status, '✅') !== false ? \yii\helpers\Console::FG_GREEN : \yii\helpers\Console::FG_RED
            );
        }
        
        $allOk = !in_array('❌', $checks);
        
        if ($allOk) {
            $this->stdout("\n✅ Все сервисы работают нормально\n", \yii\helpers\Console::FG_GREEN);
            return ExitCode::OK;
        } else {
            $this->stdout("\n❌ Есть проблемы с сервисами\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
    
    /**
     * Создание WebP версий
     */
    public function actionCreateWebp($path = 'frontend/web/images')
    {
        $this->stdout("🌐 Создание WebP версий в: {$path}\n", \yii\helpers\Console::FG_YELLOW);
        
        if (!is_dir($path)) {
            $this->stdout("❌ Директория не найдена: {$path}\n", \yii\helpers\Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }
        
        $files = \yii\helpers\FileHelper::findFiles($path, [
            'only' => ['*.jpg', '*.jpeg', '*.png']
        ]);
        
        $created = 0;
        foreach ($files as $file) {
            if ($this->imageOptimizer->createWebpVersion($file)) {
                $created++;
            }
        }
        
        $this->stdout("✅ Создано WebP версий: {$created}\n", \yii\helpers\Console::FG_GREEN);
        return ExitCode::OK;
    }
}
