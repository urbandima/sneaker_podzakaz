<?php

/**
 * PerformanceMonitor — Мониторинг производительности
 * 
 * НАЗНАЧЕНИЕ:
 * Измерение времени выполнения запросов, SQL запросов, использования памяти.
 * 
 * МЕТРИКИ:
 * - Время выполнения запроса
 * - Количество SQL запросов
 * - Использование памяти
 * - Размер ответа
 */
namespace app\backend\shared\components;

use Yii;
use yii\base\Component;
use yii\db\Connection;

class PerformanceMonitor extends Component
{
    /**
     * Порог предупреждения времени выполнения (секунды)
     */
    public float $slowQueryThreshold = 1.0;
    
    /**
     * Порог количества SQL запросов
     */
    public int $queryCountThreshold = 50;
    
    /**
     * Порог использования памяти (MB)
     */
    public int $memoryThreshold = 128;
    
    /**
     * Включить логирование
     */
    public bool $enabled = true;
    
    /**
     * Начальное время
     */
    private float $startTime;
    
    /**
     * Начальная память
     */
    private int $startMemory;
    
    /**
     * Начать измерение
     */
    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }
    
    /**
     * Завершить измерение и вернуть метрики
     */
    public function end(): array
    {
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $metrics = [
            'duration' => round(($endTime - $this->startTime) * 1000, 2), // ms
            'memory_peak' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
            'memory_used' => round(($endMemory - $this->startMemory) / 1024 / 1024, 2), // MB
            'sql_count' => $this->getQueryCount(),
            'sql_duration' => $this->getQueryDuration(),
            'request_size' => $this->getRequestSize(),
        ];
        
        // Проверка порогов
        $warnings = $this->checkThresholds($metrics);
        
        if (!empty($warnings) && $this->enabled) {
            Yii::warning([
                'url' => Yii::$app->request->url,
                'warnings' => $warnings,
                'metrics' => $metrics,
            ], 'performance');
        }
        
        return [
            'metrics' => $metrics,
            'warnings' => $warnings,
        ];
    }
    
    /**
     * Получить количество SQL запросов
     */
    private function getQueryCount(): int
    {
        $db = Yii::$app->db;
        if ($db instanceof Connection) {
            $profiling = $db->getQueryProfiling();
            return count($profiling);
        }
        return 0;
    }
    
    /**
     * Получить общее время SQL запросов
     */
    private function getQueryDuration(): float
    {
        $db = Yii::$app->db;
        if ($db instanceof Connection) {
            $profiling = $db->getQueryProfiling();
            $total = 0;
            foreach ($profiling as $query) {
                $total += $query['duration'] ?? 0;
            }
            return round($total * 1000, 2); // ms
        }
        return 0;
    }
    
    /**
     * Получить размер запроса
     */
    private function getRequestSize(): int
    {
        return strlen(serialize($_REQUEST));
    }
    
    /**
     * Проверка порогов
     */
    private function checkThresholds(array $metrics): array
    {
        $warnings = [];
        
        if ($metrics['duration'] > $this->slowQueryThreshold * 1000) {
            $warnings[] = "Slow request: {$metrics['duration']}ms > {$this->slowQueryThreshold}s threshold";
        }
        
        if ($metrics['sql_count'] > $this->queryCountThreshold) {
            $warnings[] = "Too many SQL queries: {$metrics['sql_count']} > {$this->queryCountThreshold}";
        }
        
        if ($metrics['memory_peak'] > $this->memoryThreshold) {
            $warnings[] = "High memory usage: {$metrics['memory_peak']}MB > {$this->memoryThreshold}MB";
        }
        
        return $warnings;
    }
    
    /**
     * Добавить заголовок X-Debug-Time для отладки
     */
    public function addDebugHeaders(): void
    {
        if (!$this->enabled || !YII_ENV_DEV) {
            return;
        }
        
        $result = $this->end();
        
        Yii::$app->response->headers->set('X-Debug-Time', $result['metrics']['duration'] . 'ms');
        Yii::$app->response->headers->set('X-Debug-Memory', $result['metrics']['memory_peak'] . 'MB');
        Yii::$app->response->headers->set('X-Debug-SQL-Count', $result['metrics']['sql_count']);
    }
    
    /**
     * Получить статистику медленных запросов
     */
    public static function getSlowQueriesLog(): array
    {
        $logFile = Yii::getAlias('@runtime/logs/performance.log');
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $queries = [];
        
        foreach (array_slice($lines, -100) as $line) {
            $data = json_decode($line, true);
            if ($data) {
                $queries[] = $data;
            }
        }
        
        return $queries;
    }
}
