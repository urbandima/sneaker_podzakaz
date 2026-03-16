<?php

/**
 * DevToolsController — Инструменты разработчика
 * 
 * НАЗНАЧЕНИЕ:
 * Диагностика системы, проверка состояния БД, кэша, логов,
 * быстрое исправление проблем и сброс кэша.
 * 
 * ФУНКЦИИ:
 * - Главная страница диагностики (index)
 * - Проверка базы данных (check-database)
 * - Проверка кэша (check-cache)
 * - Сброс кэша (clear-cache)
 * - Просмотр логов (logs)
 * - Проверка маршрутов (check-routes)
 * - Проверка файлов (check-files)
 * - Диагностика производительности (performance)
 * 
 * СВЯЗИ:
 * - Прямая работа с Yii::$app->db, Yii::$app->cache
 * - FileHelper для работы с файлами
 * 
 * ДОСТУП:
 * - Только администраторы
 * 
 * ОСОБЕННОСТИ:
 * - Инструменты для быстрой диагностики проблем
 * - Не используется в production регулярно
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\helpers\FileHelper;
use yii\db\Exception as DbException;

class DevToolsController extends BaseAdminController
{
    /**
     * Главная страница dev-панели
     */
    public function actionIndex()
    {
        // Собираем диагностическую информацию
        $diagnostics = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'logs' => $this->checkLogs(),
            'routes' => $this->checkRoutes(),
            'files' => $this->checkFiles(),
            'performance' => $this->checkPerformance(),
        ];

        return $this->render('index', [
            'diagnostics' => $diagnostics,
        ]);
    }

    /**
     * Проверка базы данных
     */
    protected function checkDatabase()
    {
        $issues = [];
        $stats = [];

        try {
            // Проверка подключения
            $db = Yii::$app->db;
            $db->open();
            $stats['connection'] = 'OK';

            // Статистика таблиц
            $tables = [
                'product' => 'Товары',
                'product_size' => 'Размеры',
                'order' => 'Заказы',
                'customer' => 'Покупатели',
                'cart' => 'Корзины',
            ];

            foreach ($tables as $table => $label) {
                try {
                    $quotedTable = $db->quoteTableName($table);
                    $count = $db->createCommand("SELECT COUNT(*) FROM {$quotedTable}")->queryScalar();
                    $stats['tables'][$label] = $count;
                } catch (\Exception $e) {
                    $issues[] = "Таблица {$table}: " . $e->getMessage();
                }
            }

            // Проверка индексов
            $slowQueries = $db->createCommand("SHOW PROCESSLIST")->queryAll();
            if (count($slowQueries) > 50) {
                $issues[] = "Много активных запросов: " . count($slowQueries);
            }

        } catch (\Exception $e) {
            $issues[] = "Ошибка подключения к БД: " . $e->getMessage();
            $stats['connection'] = 'ERROR';
        }

        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'stats' => $stats,
            'issues' => $issues,
        ];
    }

    /**
     * Проверка кэша
     */
    protected function checkCache()
    {
        $issues = [];
        $stats = [];

        try {
            $cache = Yii::$app->cache;
            
            // Тест записи/чтения
            $testKey = 'devtools_test_' . time();
            $testValue = 'test_value';
            
            if ($cache->set($testKey, $testValue, 60)) {
                $retrieved = $cache->get($testKey);
                if ($retrieved === $testValue) {
                    $stats['read_write'] = 'OK';
                    $cache->delete($testKey);
                } else {
                    $issues[] = "Кэш не возвращает корректные данные";
                }
            } else {
                $issues[] = "Не удалось записать в кэш";
            }

            // Статистика кэша (если доступна)
            $cacheDir = Yii::getAlias('@runtime/cache');
            if (is_dir($cacheDir)) {
                $files = FileHelper::findFiles($cacheDir);
                $stats['files_count'] = count($files);
                
                $totalSize = 0;
                foreach ($files as $file) {
                    $totalSize += filesize($file);
                }
                $stats['total_size'] = Yii::$app->formatter->asShortSize($totalSize);
            }

        } catch (\Exception $e) {
            $issues[] = "Ошибка кэша: " . $e->getMessage();
        }

        return [
            'status' => empty($issues) ? 'ok' : 'error',
            'stats' => $stats,
            'issues' => $issues,
        ];
    }

    /**
     * Проверка логов
     */
    protected function checkLogs()
    {
        $issues = [];
        $stats = [];

        $logDir = Yii::getAlias('@runtime/logs');
        
        if (!is_dir($logDir)) {
            $issues[] = "Директория логов не существует";
            return [
                'status' => 'error',
                'stats' => $stats,
                'issues' => $issues,
            ];
        }

        // Последние ошибки из app.log
        $appLog = $logDir . '/app.log';
        if (file_exists($appLog)) {
            $stats['app_log_size'] = Yii::$app->formatter->asShortSize(filesize($appLog));
            
            // Читаем последние 100 строк
            $lines = $this->tail($appLog, 100);
            $errorCount = 0;
            $recentErrors = [];
            
            foreach ($lines as $line) {
                if (preg_match('/\[error\]|\[warning\]/i', $line)) {
                    $errorCount++;
                    if (count($recentErrors) < 5) {
                        $recentErrors[] = $line;
                    }
                }
            }
            
            $stats['recent_errors'] = $errorCount;
            $stats['error_samples'] = $recentErrors;
            
            if ($errorCount > 10) {
                $issues[] = "Обнаружено {$errorCount} ошибок в последних 100 строках лога";
            }
        }

        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'stats' => $stats,
            'issues' => $issues,
        ];
    }

    /**
     * Проверка маршрутов
     */
    protected function checkRoutes()
    {
        $issues = [];
        $stats = [];

        $urlManager = Yii::$app->urlManager;
        $rules = $urlManager->rules;
        
        $stats['total_rules'] = count($rules);

        // Проверка критичных маршрутов
        $criticalRoutes = [
            '/catalog' => 'Каталог',
            '/catalog/product' => 'Страница товара',
            '/cart' => 'Корзина',
            '/admin' => 'Админка',
            '/account/login' => 'Вход покупателя',
        ];

        foreach ($criticalRoutes as $route => $label) {
            try {
                $url = Yii::$app->urlManager->createUrl($route);
                $stats['routes'][$label] = 'OK';
            } catch (\Exception $e) {
                $issues[] = "Маршрут {$label} ({$route}): " . $e->getMessage();
            }
        }

        return [
            'status' => empty($issues) ? 'ok' : 'error',
            'stats' => $stats,
            'issues' => $issues,
        ];
    }

    /**
     * Проверка файлов и директорий
     */
    protected function checkFiles()
    {
        $issues = [];
        $stats = [];

        // Проверка критичных директорий
        $dirs = [
            '@runtime' => 'Runtime',
            '@webroot/images' => 'Изображения',
            '@webroot/cache' => 'Кэш изображений',
        ];

        foreach ($dirs as $alias => $label) {
            $path = Yii::getAlias($alias);
            if (!is_dir($path)) {
                $issues[] = "Директория {$label} не существует: {$path}";
            } elseif (!is_writable($path)) {
                $issues[] = "Директория {$label} недоступна для записи: {$path}";
            } else {
                $stats['directories'][$label] = 'OK';
            }
        }

        // Проверка размера директорий
        $runtimeSize = $this->getDirectorySize(Yii::getAlias('@runtime'));
        $stats['runtime_size'] = Yii::$app->formatter->asShortSize($runtimeSize);

        if ($runtimeSize > 500 * 1024 * 1024) { // 500 MB
            $issues[] = "Директория runtime занимает более 500 MB";
        }

        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'stats' => $stats,
            'issues' => $issues,
        ];
    }

    /**
     * Проверка производительности
     */
    protected function checkPerformance()
    {
        $issues = [];
        $stats = [];

        // Время выполнения PHP
        $stats['execution_time'] = round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 4) . 's';

        // Использование памяти
        $memoryUsage = memory_get_usage(true);
        $stats['memory_usage'] = Yii::$app->formatter->asShortSize($memoryUsage);
        
        if ($memoryUsage > 128 * 1024 * 1024) { // 128 MB
            $issues[] = "Высокое использование памяти: " . $stats['memory_usage'];
        }

        // Проверка opcache
        if (function_exists('opcache_get_status')) {
            $opcache = opcache_get_status();
            if ($opcache && isset($opcache['opcache_enabled'])) {
                $stats['opcache'] = $opcache['opcache_enabled'] ? 'Включен' : 'Выключен';
                if (!$opcache['opcache_enabled']) {
                    $issues[] = "OPcache выключен - рекомендуется включить для production";
                }
            }
        }

        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'stats' => $stats,
            'issues' => $issues,
        ];
    }

    /**
     * Очистка кэша
     */
    public function actionClearCache()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            Yii::$app->cache->flush();
            
            return [
                'success' => true,
                'message' => 'Кэш успешно очищен',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка очистки кэша: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Очистка логов
     */
    public function actionClearLogs()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $logDir = Yii::getAlias('@runtime/logs');
            $files = FileHelper::findFiles($logDir, ['only' => ['*.log']]);
            
            $cleared = 0;
            foreach ($files as $file) {
                if (file_put_contents($file, '') !== false) {
                    $cleared++;
                }
            }

            return [
                'success' => true,
                'message' => "Очищено файлов: {$cleared}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка очистки логов: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Получить последние N строк файла
     */
    protected function tail($file, $lines = 100)
    {
        if (!file_exists($file)) {
            return [];
        }

        $handle = fopen($file, 'r');
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = [];

        while ($linecounter > 0) {
            $t = ' ';
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos--;
            }
            $linecounter--;
            if ($beginning) {
                rewind($handle);
            }
            $text[$lines - $linecounter - 1] = fgets($handle);
            if ($beginning) break;
        }
        fclose($handle);

        return array_reverse($text);
    }

    /**
     * Получить размер директории
     */
    protected function getDirectorySize($path)
    {
        $size = 0;
        if (!is_dir($path)) {
            return $size;
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Экспорт диагностики в JSON
     */
    public function actionExportDiagnostics()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $diagnostics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'logs' => $this->checkLogs(),
            'routes' => $this->checkRoutes(),
            'files' => $this->checkFiles(),
            'performance' => $this->checkPerformance(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'yii_version' => Yii::getVersion(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            ],
        ];

        return $diagnostics;
    }
}
