<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use yii\web\Response;
use app\models\ImportBatch;
use app\models\ImportLog;
use app\components\PoizonApiService;

/**
 * Контроллер управления импортом Poizon в админке
 */
class AdminImportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Dashboard импорта
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
     * Запустить импорт
     */
    public function actionRun()
    {
        if (Yii::$app->request->isPost) {
            // Запускаем импорт в фоне через exec
            $limit = Yii::$app->request->post('limit', 100);
            $command = "php " . Yii::getAlias('@app') . "/yii poizon-import/run --limit={$limit} > /dev/null 2>&1 &";
            
            exec($command);
            
            Yii::$app->session->setFlash('success', 'Импорт запущен в фоновом режиме');
            return $this->redirect(['index']);
        }

        return $this->render('run');
    }

    /**
     * Детали батча импорта
     */
    public function actionView($id)
    {
        $batch = $this->findModel($id);

        // Логи батча
        $logsProvider = new ActiveDataProvider([
            'query' => ImportLog::find()
                ->where(['batch_id' => $id])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('view', [
            'batch' => $batch,
            'logsProvider' => $logsProvider,
        ]);
    }

    /**
     * Логи ошибок
     */
    public function actionErrors()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ImportLog::find()
                ->where(['action' => ImportLog::ACTION_ERROR])
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
     * API: Статус последнего импорта
     */
    public function actionStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $lastBatch = ImportBatch::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        if (!$lastBatch) {
            return [
                'status' => 'no_imports',
                'message' => 'Импорты еще не запускались',
            ];
        }

        return [
            'status' => $lastBatch->status,
            'batch_id' => $lastBatch->id,
            'started_at' => $lastBatch->started_at,
            'finished_at' => $lastBatch->finished_at,
            'progress' => [
                'total' => $lastBatch->total_items,
                'created' => $lastBatch->created_count,
                'updated' => $lastBatch->updated_count,
                'errors' => $lastBatch->error_count,
            ],
            'success_rate' => $lastBatch->getSuccessRate(),
        ];
    }

    /**
     * API: Проверить наличие размера в реальном времени
     * 
     * Используется на странице товара, когда пользователь выбирает размер
     */
    public function actionCheckSize($poizonSkuId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $poizonApi = Yii::$app->get('poizonApi');
            $result = $poizonApi->checkSizeAvailability($poizonSkuId);

            return [
                'success' => true,
                'available' => $result['available'],
                'stock' => $result['stock'] ?? 0,
                'price_cny' => $result['price_cny'] ?? null,
                'delivery_days' => '14-30', // Срок доставки
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Тест подключения к Poizon API
     */
    public function actionTestConnection()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $poizonApi = Yii::$app->get('poizonApi');
        $result = $poizonApi->testConnection();

        return $result;
    }

    /**
     * Статистика импорта
     */
    private function getImportStats()
    {
        $lastBatch = ImportBatch::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->one();

        $totalBatches = ImportBatch::find()->count();
        $successfulBatches = ImportBatch::find()
            ->where(['status' => ImportBatch::STATUS_COMPLETED])
            ->count();
        
        $totalProductsImported = ImportBatch::find()
            ->sum('created_count + updated_count');

        $totalErrors = ImportBatch::find()->sum('error_count');

        return [
            'last_batch' => $lastBatch,
            'total_batches' => $totalBatches,
            'successful_batches' => $successfulBatches,
            'total_products_imported' => $totalProductsImported ?? 0,
            'total_errors' => $totalErrors ?? 0,
            'success_rate' => $totalBatches > 0 ? round(($successfulBatches / $totalBatches) * 100, 1) : 0,
        ];
    }

    /**
     * Найти модель или вернуть 404
     */
    protected function findModel($id)
    {
        if (($model = ImportBatch::findOne($id)) !== null) {
            return $model;
        }

        throw new \yii\web\NotFoundHttpException('Batch не найден');
    }
}
