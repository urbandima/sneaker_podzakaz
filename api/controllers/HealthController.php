<?php

/**
 * HealthController — Health Check эндпоинты для мониторинга
 * 
 * НАЗНАЧЕНИЕ:
 * Kubernetes/Docker health checks, мониторинг доступности сервисов.
 * 
 * ENDPOINTS:
 * - /health/live - Liveness probe (приложение живо)
 * - /health/ready - Readiness probe (приложение готово к работе)
 * - /health/status - Полный статус сервисов
 */
namespace app\api\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\db\Connection;
use yii\redis\Connection as RedisConnection;

class HealthController extends Controller
{
    public $enableCsrfValidation = false;
    
    /**
     * Liveness probe - приложение живо?
     * Используется Kubernetes для проверки зависших процессов
     */
    public function actionLive()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        return [
            'status' => 'ok',
            'timestamp' => time(),
        ];
    }
    
    /**
     * Readiness probe - приложение готово принимать трафик?
     * Проверяет подключение к БД и другим сервисам
     */
    public function actionReady()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];
        
        $allOk = !in_array(false, $checks, true);
        
        Yii::$app->response->statusCode = $allOk ? 200 : 503;
        
        return [
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Полный статус системы
     */
    public function actionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $checks = [
            'database' => $this->checkDatabaseDetails(),
            'cache' => $this->checkCacheDetails(),
            'storage' => $this->checkStorageDetails(),
            'php' => $this->getPhpInfo(),
            'opcache' => $this->getOpcacheStatus(),
        ];
        
        return [
            'status' => 'ok',
            'version' => Yii::$app->version ?? '1.0.0',
            'environment' => YII_ENV,
            'debug' => YII_DEBUG,
            'checks' => $checks,
            'timestamp' => time(),
        ];
    }
    
    /**
     * Проверка подключения к БД
     */
    private function checkDatabase(): bool
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->execute();
            return true;
        } catch (\Exception $e) {
            Yii::error('Database health check failed: ' . $e->getMessage(), 'health');
            return false;
        }
    }
    
    /**
     * Детальная проверка БД
     */
    private function checkDatabaseDetails(): array
    {
        try {
            $start = microtime(true);
            Yii::$app->db->createCommand('SELECT 1')->execute();
            $latency = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'ok',
                'latency_ms' => $latency,
                'driver' => Yii::$app->db->driverName,
                'version' => $this->getDbVersion(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Проверка кэша
     */
    private function checkCache(): bool
    {
        try {
            $testKey = 'health_check_' . time();
            Yii::$app->cache->set($testKey, 1, 10);
            $result = Yii::$app->cache->get($testKey);
            Yii::$app->cache->delete($testKey);
            return $result === 1;
        } catch (\Exception $e) {
            Yii::error('Cache health check failed: ' . $e->getMessage(), 'health');
            return false;
        }
    }
    
    /**
     * Детальная проверка кэша
     */
    private function checkCacheDetails(): array
    {
        $cache = Yii::$app->cache;
        
        $type = get_class($cache);
        $isRedis = $cache instanceof \yii\redis\Cache;
        
        $details = [
            'status' => 'ok',
            'type' => $type,
            'is_redis' => $isRedis,
        ];
        
        if ($isRedis) {
            try {
                $redis = Yii::$app->redis;
                if ($redis && $redis instanceof RedisConnection) {
                    $info = $redis->executeCommand('INFO');
                    $details['redis_connected_clients'] = $info['connected_clients'] ?? 'unknown';
                    $details['redis_used_memory'] = $info['used_memory_human'] ?? 'unknown';
                }
            } catch (\Exception $e) {
                $details['redis_error'] = $e->getMessage();
            }
        }
        
        return $details;
    }
    
    /**
     * Проверка файлового хранилища
     */
    private function checkStorage(): bool
    {
        $runtimePath = Yii::getAlias('@runtime');
        return is_writable($runtimePath);
    }
    
    /**
     * Детальная проверка хранилища
     */
    private function checkStorageDetails(): array
    {
        $runtimePath = Yii::getAlias('@runtime');
        $uploadsPath = Yii::getAlias('@uploads');
        
        return [
            'status' => 'ok',
            'runtime_writable' => is_writable($runtimePath),
            'uploads_writable' => is_writable($uploadsPath),
            'disk_free' => $this->formatBytes(disk_free_space($runtimePath)),
            'disk_total' => $this->formatBytes(disk_total_space($runtimePath)),
        ];
    }
    
    /**
     * Информация о PHP
     */
    private function getPhpInfo(): array
    {
        return [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'extensions' => [
                'redis' => extension_loaded('redis'),
                'gd' => extension_loaded('gd'),
                'imagick' => extension_loaded('imagick'),
                'zip' => extension_loaded('zip'),
            ],
        ];
    }
    
    /**
     * Статус OPcache
     */
    private function getOpcacheStatus(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['status' => 'disabled'];
        }
        
        $status = opcache_get_status(false);
        
        if (!$status) {
            return ['status' => 'disabled'];
        }
        
        return [
            'status' => 'enabled',
            'memory_used' => $this->formatBytes($status['memory_usage']['used_memory']),
            'memory_free' => $this->formatBytes($status['memory_usage']['free_memory']),
            'hit_rate' => round($status['opcache_statistics']['opcache_hit_rate'], 2) . '%',
            'cached_scripts' => $status['opcache_statistics']['num_cached_scripts'],
        ];
    }
    
    /**
     * Версия БД
     */
    private function getDbVersion(): string
    {
        try {
            $result = Yii::$app->db->createCommand('SELECT VERSION()')->queryScalar();
            return $result ?: 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
    
    /**
     * Форматирование байтов
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }
}
