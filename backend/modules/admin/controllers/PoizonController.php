<?php

/**
 * PoizonController — Управление импортом из Poizon (Dewu)
 * 
 * НАЗНАЧЕНИЕ:
 * Управление импортом товаров с китайской платформы Poizon/Dewu:
 * запуск импорта, просмотр истории, логов, управление батчами.
 * 
 * ФУНКЦИИ:
 * - Dashboard импорта со статистикой (index)
 * - Запуск нового импорта (run)
 * - Просмотр батча импорта (view)
 * - Просмотр лога импорта (view-log)
 * - Удаление батча (delete)
 * - Отмена активного импорта (cancel)
 * 
 * СВЯЗИ:
 * - ImportBatch (модель батча импорта)
 * - ImportLog (модель лога импорта)
 * - Product (модель товара)
 * - PoizonApiService (сервис API Poizon)
 * 
 * ДОСТУП:
 * - Только администраторы
 * 
 * ОСОБЕННОСТИ:
 * - Асинхронный импорт через консольную команду
 * - Логирование всех операций импорта
 * - Автоматическое создание товаров из данных Poizon
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use app\backend\modules\catalog\models\ImportBatch;
use app\backend\modules\catalog\models\ImportLog;
use app\backend\modules\catalog\models\Product;

class PoizonController extends BaseAdminController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $this->adminOnly = true;
        return parent::behaviors();
    }

    /**
     * Dashboard импорта Poizon
     */
    public function actionIndex()
    {
        // Последние батчи
        $dataProvider = new ActiveDataProvider([
            'query' => ImportBatch::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // Статистика
        $stats = $this->getImportStats();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }

    /**
     * Запустить импорт Poizon
     */
    public function actionRun()
    {
        if (Yii::$app->request->isPost) {
            // Проверяем импорт из URL
            $importUrl = Yii::$app->request->post('import_url');
            
            if ($importUrl) {
                // Импорт из URL
                $logFile = Yii::getAlias('@runtime/logs/poizon-url-import-' . time() . '.log');
                $logDir = dirname($logFile);
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0777, true);
                }
                
                // Запускаем импорт из URL с verbose
                $command = 'php ' . escapeshellarg(Yii::getAlias('@app') . '/yii') . ' poizon-import-json/run ' . escapeshellarg($importUrl) . ' --verbose=1 > ' . escapeshellarg($logFile) . ' 2>&1 &';
                
                exec($command);
                
                $this->flashSuccess('Импорт запущен в фоновом режиме');
                $this->flashInfo('Лог файл: ' . basename($logFile));
                
                return $this->redirect(['/admin/poizon/index']);
            }
        }

        return $this->render('run');
    }

    /**
     * Просмотр батча импорта
     * 
     * @param int $id
     */
    public function actionView($id)
    {
        $batch = $this->findModel($id);
        
        // Ищем файл лога для этого батча
        $logContent = '';
        $logDir = Yii::getAlias('@runtime/logs');
        
        if (is_dir($logDir)) {
            // Ищем лог по дате батча
            $batchDate = date('Y-m-d', strtotime($batch->created_at));
            $possibleLogFiles = glob($logDir . '/poizon-*' . $batchDate . '*.log');
            
            // Если не найден по дате, ищем по ID или времени создания
            if (empty($possibleLogFiles)) {
                $possibleLogFiles = glob($logDir . '/poizon-*.log');
                // Сортируем по времени изменения
                usort($possibleLogFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
            }
            
            // Читаем первый подходящий файл
            if (!empty($possibleLogFiles)) {
                foreach ($possibleLogFiles as $logFile) {
                    $content = file_get_contents($logFile);
                    // Проверяем, содержит ли лог информацию о нашем батче
                    if (strpos($content, 'Batch #' . $batch->id) !== false || 
                        strpos($content, $batch->created_at) !== false) {
                        $logContent = $content;
                        break;
                    }
                }
                // Если не нашли по батчу, берем последний
                if (empty($logContent) && !empty($possibleLogFiles)) {
                    $logContent = file_get_contents($possibleLogFiles[0]);
                }
            }
        }

        // Создаем провайдер для таблицы детальных логов
        $logsProvider = new ActiveDataProvider([
            'query' => ImportLog::find()->where(['batch_id' => $batch->id]),
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        return $this->render('view', [
            'batch' => $batch,
            'logContent' => $logContent,
            'logsProvider' => $logsProvider,
        ]);
    }

    /**
     * Просмотр лога импорта
     * 
     * @param string|null $file
     */
    public function actionViewLog($file = null)
    {
        $logDir = Yii::getAlias('@runtime/logs');
        
        // Создаем список всех логов
        $logsList = [];
        if (is_dir($logDir)) {
            $files = glob($logDir . '/poizon-*.log');
            foreach ($files as $logFile) {
                $logsList[] = [
                    'name' => basename($logFile),
                    'size' => filesize($logFile),
                    'time' => filemtime($logFile),
                ];
            }
            // Сортируем по времени (новые первыми)
            usort($logsList, function($a, $b) {
                return $b['time'] - $a['time'];
            });
        }
        
        // Если файл не указан, берем последний
        if (!$file && !empty($logsList)) {
            $file = $logsList[0]['name'];
        }
        
        // Читаем выбранный файл
        $content = '';
        $fileName = $file ?? '';
        $fileSize = 0;
        $lastModified = 0;
        
        if ($file) {
            $logFile = $logDir . '/' . basename($file);
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                $fileName = basename($file);
                $fileSize = filesize($logFile);
                $lastModified = filemtime($logFile);
            }
        }

        return $this->render('view-log', [
            'content' => $content,
            'fileName' => $fileName,
            'fileSize' => $fileSize,
            'lastModified' => $lastModified,
            'logsList' => $logsList,
        ]);
    }

    /**
     * Удаление батча
     * 
     * @param int $id
     */
    public function actionDelete($id)
    {
        $batch = $this->findModel($id);
        
        if ($batch->delete()) {
            $this->flashSuccess('Батч импорта успешно удален');
        } else {
            $this->flashError('Ошибка при удалении батча');
        }
        
        return $this->redirect(['/admin/poizon/index']);
    }

    /**
     * Показать ошибки импорта
     */
    public function actionErrors()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ImportLog::find()
                ->where(['type' => 'error'])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('errors', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Получить статистику импорта
     * 
     * @return array
     */
    protected function getImportStats()
    {
        $totalBatches = ImportBatch::find()->count();
        $successfulBatches = ImportBatch::find()->where(['status' => 'completed'])->count();
        $failedBatches = ImportBatch::find()->where(['status' => 'failed'])->count();
        
        // Подсчет общего количества импортированных товаров
        $totalProductsImported = Product::find()->where(['not', ['poizon_id' => null]])->count();
        
        // Подсчет общего количества ошибок из всех батчей
        $totalErrors = ImportBatch::find()->sum('error_count') ?? 0;
        
        // Процент успешности
        $successRate = $totalBatches > 0 ? round(($successfulBatches / $totalBatches) * 100, 1) : 0;
        
        // Последний батч
        $lastBatch = ImportBatch::find()->orderBy(['created_at' => SORT_DESC])->one();
        
        return [
            'total_products_imported' => $totalProductsImported,
            'total_batches' => $totalBatches,
            'successful_batches' => $successfulBatches,
            'failed_batches' => $failedBatches,
            'total_errors' => $totalErrors,
            'success_rate' => $successRate,
            'last_batch' => $lastBatch,
        ];
    }

    /**
     * Найти модель батча
     * 
     * @param int $id
     * @return ImportBatch
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $model = ImportBatch::findOne($id);
        
        if ($model === null) {
            throw new NotFoundHttpException('Батч импорта не найден');
        }
        
        return $model;
    }
}
