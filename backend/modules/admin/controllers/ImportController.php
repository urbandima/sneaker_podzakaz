<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\backend\modules\admin\models\import\ImportSource;
use app\backend\modules\admin\models\import\ImportTask;
use app\backend\modules\admin\models\import\ImportLog;
use app\backend\modules\admin\models\import\ImportProductPrice;
use app\backend\modules\admin\models\ImportUploadForm;
use app\backend\modules\admin\services\import\ImportService;
use app\backend\modules\admin\services\import\ProxyService;
use app\backend\modules\admin\services\import\CaptchaService;
use app\backend\modules\admin\services\import\NbrbRateService;

/**
 * ImportController — Управление импортом товаров
 * 
 * Actions:
 * - index: Дашборд импорта
 * - source: Настройка источника
 * - run: Запуск импорта вручную
 * - runAll: Запуск всех активных источников
 * - logs: Просмотр логов
 * - stats: Статистика импорта
 * - settings: Общие настройки
 * - upload: Загрузка файлов для ручного импорта
 * - process-file: Обработка загруженного файла
 */
class ImportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'run' => ['POST'],
                    'run-all' => ['POST'],
                    'stop' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        // Регистрируем CSS/JS для админки
        $this->view->params['bodyClass'] = 'import-page';
        
        return true;
    }

    /**
     * Дашборд импорта
     * @return string
     */
    public function actionIndex()
    {
        // Источники
        $sources = ImportSource::find()->orderBy(['name' => SORT_ASC])->all();

        // Последние задачи
        $recentTasks = ImportTask::getRecentTasks(10);

        // Запущенные задачи
        $runningTasks = ImportTask::getRunningTasks();

        // Статистика
        $stats = $this->getOverallStats();

        // Курсы валют
        $currencyService = new NbrbRateService();
        $rates = $currencyService->getAllRates();

        return $this->render('index', [
            'sources' => $sources,
            'recentTasks' => $recentTasks,
            'runningTasks' => $runningTasks,
            'stats' => $stats,
            'rates' => $rates,
        ]);
    }

    /**
     * Настройка источника
     * @param int $id ID источника
     * @return string|Response
     */
    public function actionSource($id = null)
    {
        if ($id) {
            $model = ImportSource::findOne($id);
        } else {
            $model = new ImportSource();
        }

        if (!$model) {
            Yii::$app->session->setFlash('error', 'Источник не найден');
            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            // Обрабатываем proxy_list как JSON
            if (isset($post['proxy_list_text']) && !empty($post['proxy_list_text'])) {
                $proxies = array_filter(array_map('trim', explode("\n", $post['proxy_list_text'])));
                $model->setProxyListArray($proxies);
            }
            
            if ($model->load($post) && $model->save()) {
                Yii::$app->session->setFlash('success', 'Источник сохранен');
                return $this->redirect(['source', 'id' => $model->id]);
            }
        }

        // Маппинг категорий
        $categoryMaps = $id ? \app\backend\modules\admin\models\import\ImportCategoryMap::getSourceMappings($id) : [];

        // Категории для выбора
        $categories = \app\backend\modules\catalog\models\Category::find()
            ->where(['is_active' => true])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return $this->render('source', [
            'model' => $model,
            'categoryMaps' => $categoryMaps,
            'categories' => $categories,
        ]);
    }

    /**
     * Запуск импорта вручную
     * @param int $sourceId ID источника
     * @return Response
     */
    public function actionRun($sourceId)
    {
        $source = ImportSource::findOne($sourceId);
        
        if (!$source || !$source->is_active) {
            Yii::$app->session->setFlash('error', 'Источник не найден или неактивен');
            return $this->redirect(['index']);
        }

        try {
            // Инициализируем сервисы
            $importService = $this->createImportService();

            // Запускаем импорт
            $task = $importService->runImport($sourceId, [
                'limit' => 50, // Лимит товаров для ручного запуска
            ]);

            Yii::$app->session->setFlash('success', "Импорт запущен (Задача #{$task->id})");
            
            return $this->redirect(['logs', 'taskId' => $task->id]);
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Ошибка запуска: ' . $e->getMessage());
            return $this->redirect(['index']);
        }
    }

    /**
     * Запуск всех активных источников
     * @return Response
     */
    public function actionRunAll()
    {
        $sources = ImportSource::getActiveSources();
        
        if (empty($sources)) {
            Yii::$app->session->setFlash('warning', 'Нет активных источников');
            return $this->redirect(['index']);
        }

        $importService = $this->createImportService();
        $taskIds = [];

        foreach ($sources as $source) {
            try {
                $task = $importService->runImport($source->id, [
                    'limit' => 30,
                ]);
                $taskIds[] = $task->id;
            } catch (\Exception $e) {
                Yii::error("Failed to start import for {$source->name}: " . $e->getMessage(), 'import');
            }
        }

        if (!empty($taskIds)) {
            Yii::$app->session->setFlash('success', 'Запущено задач: ' . count($taskIds));
        } else {
            Yii::$app->session->setFlash('error', 'Не удалось запустить ни одной задачи');
        }

        return $this->redirect(['index']);
    }

    /**
     * Просмотр логов
     * @param int|null $taskId ID задачи
     * @return string
     */
    public function actionLogs($taskId = null)
    {
        $query = ImportLog::find()->with(['task', 'task.source']);

        if ($taskId) {
            $query->andWhere(['task_id' => $taskId]);
            $task = ImportTask::findOne($taskId);
        } else {
            $task = null;
        }

        // Фильтры
        $filters = Yii::$app->request->get();
        
        if (!empty($filters['action'])) {
            $query->andWhere(['action' => $filters['action']]);
        }
        
        if (!empty($filters['level'])) {
            $query->andWhere(['level' => $filters['level']]);
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->andWhere([
                'or',
                ['like', 'product_name', $search],
                ['like', 'sku', $search],
                ['like', 'message', $search],
            ]);
        }

        // Пагинация
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        return $this->render('logs', [
            'dataProvider' => $dataProvider,
            'task' => $task,
            'filters' => $filters,
        ]);
    }

    /**
     * Статистика импорта
     * @return string
     */
    public function actionStats()
    {
        // Общая статистика
        $overallStats = $this->getOverallStats();

        // Статистика по источникам
        $sourceStats = $this->getSourceStats();

        // Статистика по дням (последние 30 дней)
        $dailyStats = $this->getDailyStats(30);

        // Топ товаров с лучшими ценами
        $bestPrices = ImportProductPrice::find()
            ->with(['product', 'source'])
            ->where(['is_available' => true])
            ->orderBy(['price_byn' => SORT_ASC])
            ->limit(20)
            ->all();

        return $this->render('stats', [
            'overallStats' => $overallStats,
            'sourceStats' => $sourceStats,
            'dailyStats' => $dailyStats,
            'bestPrices' => $bestPrices,
        ]);
    }

    /**
     * Общие настройки
     * @return string|Response
     */
    public function actionSettings()
    {
        $settings = Yii::$app->request->post('settings', []);

        if (Yii::$app->request->isPost) {
            // Сохраняем настройки в конфиг
            foreach ($settings as $key => $value) {
                Yii::$app->settings->set('import', $key, $value);
            }

            Yii::$app->session->setFlash('success', 'Настройки сохранены');
            return $this->refresh();
        }

        // Текущие настройки
        $currentSettings = [
            'auto_import_enabled' => Yii::$app->settings->get('import', 'auto_import_enabled', true),
            'import_interval_hours' => Yii::$app->settings->get('import', 'import_interval_hours', 8),
            'max_products_per_run' => Yii::$app->settings->get('import', 'max_products_per_run', 100),
            'log_retention_days' => Yii::$app->settings->get('import', 'log_retention_days', 30),
            'notify_on_complete' => Yii::$app->settings->get('import', 'notify_on_complete', true),
            'notify_on_error' => Yii::$app->settings->get('import', 'notify_on_error', true),
        ];

        // Курсы валют
        $currencyService = new NbrbRateService();
        $rates = $currencyService->getAllRates();
        $lastUpdate = $currencyService->getLastUpdateDate();

        // Баланс CAPTCHA сервисов
        $captchaService = new CaptchaService();
        $captchaBalances = $captchaService->getBalances();

        return $this->render('settings', [
            'settings' => $currentSettings,
            'rates' => $rates,
            'lastUpdate' => $lastUpdate,
            'captchaBalances' => $captchaBalances,
        ]);
    }

    /**
     * Обновить курсы валют
     * @return Response
     */
    public function actionUpdateRates()
    {
        $currencyService = new NbrbRateService();
        $results = $currencyService->updateAllRates();

        $success = count(array_filter($results, fn($r) => $r['success']));
        $total = count($results);

        Yii::$app->session->setFlash(
            $success === $total ? 'success' : 'warning',
            "Курсы обновлены: {$success}/{$total}"
        );

        return $this->redirect(['settings']);
    }

    /**
     * Проверить прокси
     * @param int $sourceId
     * @return Response
     */
    public function actionCheckProxies($sourceId)
    {
        $proxyService = new ProxyService();
        $results = $proxyService->checkAllProxies($sourceId);

        $working = count(array_filter($results, fn($r) => $r['working']));
        $total = count($results);

        Yii::$app->session->setFlash('info', "Рабочих прокси: {$working}/{$total}");

        return $this->redirect(['source', 'id' => $sourceId]);
    }

    /**
     * Создать сервис импорта
     * @return ImportService
     */
    protected function createImportService()
    {
        $service = new ImportService();
        
        $service->proxyService = new ProxyService();
        $service->captchaService = new CaptchaService();
        $service->currencyService = new NbrbRateService();

        return $service;
    }

    /**
     * Получить общую статистику
     * @return array
     */
    protected function getOverallStats()
    {
        return [
            'total_sources' => ImportSource::find()->count(),
            'active_sources' => ImportSource::find()->where(['is_active' => true])->count(),
            'total_tasks' => ImportTask::find()->count(),
            'running_tasks' => ImportTask::find()->where(['status' => ImportTask::STATUS_RUNNING])->count(),
            'completed_tasks' => ImportTask::find()->where(['status' => ImportTask::STATUS_COMPLETED])->count(),
            'failed_tasks' => ImportTask::find()->where(['status' => ImportTask::STATUS_FAILED])->count(),
            'total_products_imported' => (int) ImportTask::find()->sum('imported_count'),
            'total_products_updated' => (int) ImportTask::find()->sum('updated_count'),
            'total_errors' => (int) ImportTask::find()->sum('failed_count'),
        ];
    }

    /**
     * Получить статистику по источникам
     * @return array
     */
    protected function getSourceStats()
    {
        $sources = ImportSource::find()->with('tasks')->all();
        $stats = [];

        foreach ($sources as $source) {
            $stats[] = [
                'source' => $source,
                'total_tasks' => count($source->tasks),
                'successful_runs' => $source->successful_runs,
                'failed_runs' => $source->failed_runs,
                'total_products' => $source->total_products_parsed,
                'last_run' => $source->last_run_at,
            ];
        }

        return $stats;
    }

    /**
     * Получить ежедневную статистику
     * @param int $days
     * @return array
     */
    protected function getDailyStats($days = 30)
    {
        $stats = Yii::$app->db->createCommand(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as tasks,
                SUM(imported_count) as imported,
                SUM(updated_count) as updated,
                SUM(failed_count) as errors
            FROM {{%import_task}}
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC"
        )->bindValue(':days', $days)->queryAll();

        return $stats;
    }

    /**
     * Загрузка файлов для ручного импорта
     * @return string
     */
    public function actionUpload()
    {
        $model = new ImportUploadForm();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            
            if ($model->upload()) {
                try {
                    $result = $this->processUploadedFile($model);
                    
                    if ($result['success']) {
                        Yii::$app->session->setFlash('success', "Файл обработан. Импортировано: {$result['imported']}, обновлено: {$result['updated']}, ошибок: {$result['errors']}");
                        return $this->redirect(['logs']);
                    } else {
                        Yii::$app->session->setFlash('error', $result['error'] ?? 'Ошибка обработки файла');
                    }
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('error', 'Ошибка: ' . $e->getMessage());
                }
            }
        }

        $sources = ImportSource::find()->where(['is_active' => true])->all();

        return $this->render('upload', [
            'model' => $model,
            'sources' => $sources,
        ]);
    }

    /**
     * Обработка загруженного файла
     * @param object $model
     * @return array
     */
    protected function processUploadedFile($model)
    {
        $source = ImportSource::findOne($model->source_id);
        if (!$source) {
            return ['success' => false, 'error' => 'Источник не найден'];
        }

        // Создаем задачу импорта
        $task = new ImportTask([
            'source_id' => $source->id,
            'status' => ImportTask::STATUS_RUNNING,
            'total_products' => 0,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
        $task->save();

        $imported = 0;
        $updated = 0;
        $errors = 0;

        try {
            switch ($model->format) {
                case 'json':
                    $result = $this->processJsonFile($model->file->tempName, $task);
                    break;
                case 'csv':
                    $result = $this->processCsvFile($model->file->tempName, $task);
                    break;
                case 'xlsx':
                    $result = $this->processExcelFile($model->file->tempName, $task);
                    break;
                default:
                    throw new \Exception('Неподдерживаемый формат файла');
            }

            $imported = $result['imported'] ?? 0;
            $updated = $result['updated'] ?? 0;
            $errors = $result['errors'] ?? 0;

            $task->imported_count = $imported;
            $task->updated_count = $updated;
            $task->failed_count = $errors;
            $task->status = ImportTask::STATUS_COMPLETED;
            $task->finished_at = date('Y-m-d H:i:s');
            $task->save();

            return ['success' => true, 'imported' => $imported, 'updated' => $updated, 'errors' => $errors];

        } catch (\Exception $e) {
            $task->error_message = $e->getMessage();
            $task->status = ImportTask::STATUS_FAILED;
            $task->finished_at = date('Y-m-d H:i:s');
            $task->save();

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Обработка JSON файла
     */
    protected function processJsonFile($filePath, $task)
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (!$data) {
            throw new \Exception('Невалидный JSON файл');
        }

        return $this->importProductsFromArray($data, $task);
    }

    /**
     * Обработка Excel файла
     */
    protected function processExcelFile($filePath, $task)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('Файл не найден: ' . $filePath);
        }

        // Проверяем расширение файла
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            throw new \Exception('Неподдерживаемый формат файла. Используйте XLSX, XLS или CSV');
        }

        try {
            // Для CSV используем встроенные функции PHP
            if ($extension === 'csv') {
                return $this->processCsvFile($filePath, $task);
            }

            // Для Excel проверяем наличие PhpSpreadsheet
            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                // Если библиотека не установлена, пытаемся обработать как CSV
                Yii::warning('PhpSpreadsheet не установлен, пытаемся обработать как CSV', 'import');
                return $this->processCsvFile($filePath, $task);
            }

            // Загружаем Excel файл
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (empty($rows)) {
                throw new \Exception('Файл пуст');
            }

            // Первая строка - заголовки
            $headers = array_shift($rows);
            $headers = array_map('trim', $headers);

            // Преобразуем данные в формат для импорта
            $products = [];
            foreach ($rows as $rowIndex => $row) {
                if (empty(array_filter($row))) {
                    continue; // Пропускаем пустые строки
                }

                $product = [];
                foreach ($headers as $colIndex => $header) {
                    $value = $row[$colIndex] ?? null;
                    $product[$header] = $value;
                }

                $products[] = $product;
            }

            if (empty($products)) {
                throw new \Exception('Нет данных для импорта');
            }

            return $this->importProductsFromArray($products, $task);

        } catch (\Exception $e) {
            Yii::error('Ошибка обработки Excel: ' . $e->getMessage(), 'import');
            throw $e;
        }
    }

    /**
     * Обработка CSV файла
     */
    protected function processCsvFile($filePath, $task)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Не удалось открыть CSV файл');
        }

        $products = [];
        $headers = null;
        $rowIndex = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($rowIndex === 0) {
                // Первая строка - заголовки
                $headers = array_map('trim', $row);
                $rowIndex++;
                continue;
            }

            if (empty(array_filter($row))) {
                continue; // Пропускаем пустые строки
            }

            $product = [];
            foreach ($headers as $colIndex => $header) {
                $value = $row[$colIndex] ?? null;
                $product[$header] = $value;
            }

            $products[] = $product;
            $rowIndex++;
        }

        fclose($handle);

        if (empty($products)) {
            throw new \Exception('Нет данных для импорта в CSV файле');
        }

        return $this->importProductsFromArray($products, $task);
    }

    /**
     * Импорт товаров из массива данных
     */
    protected function importProductsFromArray($products, $task)
    {
        $imported = 0;
        $updated = 0;
        $errors = 0;

        foreach ($products as $index => $productData) {
            try {
                // Валидация обязательных полей
                if (empty($productData['name']) || empty($productData['sku'])) {
                    $this->logError($task, "Строка " . ($index + 2) . ": Отсутствуют обязательные поля (name, sku)");
                    $errors++;
                    continue;
                }

                // Поиск существующего товара
                $existingProduct = \app\backend\modules\catalog\models\Product::find()
                    ->where(['sku' => $productData['sku']])
                    ->one();

                if ($existingProduct) {
                    // Обновление существующего товара
                    $this->updateProductFromData($existingProduct, $productData, $task);
                    $updated++;
                } else {
                    // Создание нового товара
                    $this->createProductFromData($productData, $task);
                    $imported++;
                }

            } catch (\Exception $e) {
                $this->logError($task, "Строка " . ($index + 2) . ": " . $e->getMessage());
                $errors++;
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * Создание товара из данных
     */
    protected function createProductFromData($data, $task)
    {
        $product = new \app\backend\modules\catalog\models\Product();
        
        $product->name = $data['name'];
        $product->sku = $data['sku'];
        $product->description = $data['description'] ?? '';
        $product->price = $data['price'] ?? 0;
        $product->is_active = $data['is_active'] ?? true;
        
        if (isset($data['brand_id'])) {
            $product->brand_id = $data['brand_id'];
        } elseif (isset($data['brand_name'])) {
            $brand = \app\backend\modules\catalog\models\Brand::find()
                ->where(['name' => $data['brand_name']])
                ->one();
            if ($brand) {
                $product->brand_id = $brand->id;
            }
        }

        if ($product->save()) {
            $this->logSuccess($task, "Создан товар: {$product->name}", $product->sku);
        } else {
            throw new \Exception('Ошибка сохранения товара: ' . implode(', ', $product->getFirstErrors()));
        }

        return $product;
    }

    /**
     * Обновление товара из данных
     */
    protected function updateProductFromData($product, $data, $task)
    {
        $product->name = $data['name'] ?? $product->name;
        $product->description = $data['description'] ?? $product->description;
        $product->price = $data['price'] ?? $product->price;
        $product->is_active = $data['is_active'] ?? $product->is_active;

        if ($product->save()) {
            $this->logInfo($task, "Обновлен товар: {$product->name}", $product->sku);
        } else {
            throw new \Exception('Ошибка обновления товара: ' . implode(', ', $product->getFirstErrors()));
        }

        return $product;
    }

    /**
     * Логирование успеха
     */
    protected function logSuccess($task, $message, $sku = null)
    {
        $log = new ImportLog([
            'task_id' => $task->id,
            'action' => 'created',
            'level' => 'info',
            'sku' => $sku,
            'message' => $message,
        ]);
        $log->save();
    }

    /**
     * Логирование информации
     */
    protected function logInfo($task, $message, $sku = null)
    {
        $log = new ImportLog([
            'task_id' => $task->id,
            'action' => 'updated',
            'level' => 'info',
            'sku' => $sku,
            'message' => $message,
        ]);
        $log->save();
    }

    /**
     * Логирование ошибки
     */
    protected function logError($task, $message, $sku = null)
    {
        $log = new ImportLog([
            'task_id' => $task->id,
            'action' => 'error',
            'level' => 'error',
            'sku' => $sku,
            'message' => $message,
        ]);
        $log->save();
    }
}
