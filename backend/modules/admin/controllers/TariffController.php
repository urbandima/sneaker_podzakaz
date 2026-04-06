<?php

/**
 * TariffController — Управление тарифами и комиссиями
 * 
 * НАЗНАЧЕНИЕ:
 * Настройка тарифов и комиссий для расчёта цен: наценки,
 * курсы валют, условия доставки.
 * 
 * ФУНКЦИИ:
 * - Список тарифов (index)
 * - Создание тарифа (create)
 * - Редактирование тарифа (update)
 * - Удаление тарифа (delete)
 * - Калькулятор расчёта цены (calculate)
 * - История изменений тарифов (history)
 * 
 * СВЯЗИ:
 * - Tariff (модель тарифа)
 * - TariffCalculation (модель расчёта по тарифу)
 * - TariffSetupService (сервис настройки схемы БД)
 * 
 * ДОСТУП:
 * - Только администраторы
 * 
 * ОСОБЕННОСТИ:
 * - Автоматическая проверка схемы БД при загрузке
 * - Поддержка разных валют и курсов
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\Tariff;
use app\models\TariffCalculation;
use app\backend\shared\components\TariffSetupService;

class TariffController extends BaseAdminController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            return Yii::$app->user->identity->isAdmin();
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if (!TariffSetupService::ensureSchema()) {
            Yii::$app->session->setFlash('error', 'Не удалось подготовить таблицу тарифов. Проверьте подключение к базе данных.');
        }

        return parent::beforeAction($action);
    }

    /**
     * Список тарифов
     */
    public function actionIndex()
    {
        $tariffs = Tariff::find()->orderBy(['sort_order' => SORT_ASC])->all();
        
        if (empty($tariffs)) {
            Yii::$app->session->setFlash(
                'info',
                'Добавьте хотя бы один тариф, чтобы использовать калькулятор и применять тарифы к заказам.'
            );
        }

        // Получаем историю расчетов
        $calculationHistory = [];
        try {
            if (Yii::$app->db->schema->getTableSchema('{{%tariff_calculation}}') !== null) {
                $calculationHistory = TariffCalculation::getRecent(30);
            }
        } catch (\Exception $e) {
            // Таблица еще не создана
        }
        
        return $this->render('index', [
            'tariffs' => $tariffs,
            'tariffFeatureAvailable' => true,
            'calculationHistory' => $calculationHistory,
        ]);
    }

    /**
     * Создание тарифа
     */
    public function actionCreate()
    {
        $this->ensureTariffFeatureAvailable();

        $model = new Tariff();
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Тариф создан');
            return $this->redirect(['index']);
        }
        
        return $this->render('form', [
            'model' => $model,
        ]);
    }

    /**
     * Редактирование тарифа
     */
    public function actionUpdate($id)
    {
        $this->ensureTariffFeatureAvailable();

        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Тариф обновлен');
            return $this->redirect(['index']);
        }
        
        return $this->render('form', [
            'model' => $model,
        ]);
    }

    /**
     * Удаление тарифа
     */
    public function actionDelete($id)
    {
        $this->ensureTariffFeatureAvailable();

        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Тариф удален');
        return $this->redirect(['index']);
    }

    /**
     * Калькулятор стоимости (AJAX)
     */
    public function actionCalculate()
    {
        $this->ensureTariffFeatureAvailable();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $tariffId = Yii::$app->request->post('tariff_id');
        $priceCny = Yii::$app->request->post('price_cny', 0);
        $weightKg = Yii::$app->request->post('weight_kg', 0.5);
        $saveHistory = Yii::$app->request->post('save_history', true);
        $note = Yii::$app->request->post('note', '');
        
        $tariff = Tariff::findOne($tariffId);
        if (!$tariff) {
            return ['success' => false, 'message' => 'Тариф не найден'];
        }
        
        $calculation = $tariff->calculateOrderCost($priceCny, $weightKg);

        // Сохраняем в историю расчетов
        $historyRecord = null;
        if ($saveHistory) {
            try {
                if (Yii::$app->db->schema->getTableSchema('{{%tariff_calculation}}') !== null) {
                    $historyRecord = TariffCalculation::createFromCalculation($tariff, $calculation, $priceCny, $weightKg, $note);
                }
            } catch (\Exception $e) {
                Yii::error('Failed to save tariff calculation: ' . $e->getMessage());
            }
        }
        
        return [
            'success' => true,
            'calculation' => $calculation,
            'history_id' => $historyRecord ? $historyRecord->id : null,
        ];
    }

    /**
     * Переключение активности тарифа
     */
    public function actionToggle($id)
    {
        $this->ensureTariffFeatureAvailable();

        $model = $this->findModel($id);
        $model->is_active = !$model->is_active;
        $model->save();
        
        Yii::$app->session->setFlash('success', $model->is_active ? 'Тариф активирован' : 'Тариф деактивирован');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Tariff::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Тариф не найден');
    }

    private function ensureTariffFeatureAvailable(): void
    {
        if (!TariffSetupService::ensureTariffTable()) {
            throw new NotFoundHttpException('Раздел тарифов недоступен: таблица tariff отсутствует. Запустите миграции.');
        }
    }
}
