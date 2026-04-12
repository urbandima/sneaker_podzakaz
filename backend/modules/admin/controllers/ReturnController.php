<?php

/**
 * ReturnController — Контроллер возвратов для админки
 * 
 * НАЗНАЧЕНИЕ:
 * Управление возвратами в админ-панели: обработка заявок,
 * одобрение/отклонение, завершение возврата.
 * 
 * ФУНКЦИИ:
 * - index() - список всех заявок
 * - view() - просмотр заявки
 * - approve() - одобрение заявки
 * - reject() - отклонение заявки
 * - process() - начало обработки
 * - complete() - завершение возврата
 */
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\backend\modules\returns\models\ReturnRequest;
use app\backend\modules\returns\services\ReturnService;
use app\backend\modules\checkout\models\Order;

class ReturnController extends BaseAdminController
{
    /**
     * Behaviors
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'approve' => ['POST'],
                    'reject' => ['POST'],
                    'process' => ['POST'],
                    'complete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Список всех заявок на возврат
     */
    public function actionIndex()
    {
        $query = ReturnRequest::find()
            ->with(['order'])
            ->orderBy(['created_at' => SORT_DESC]);

        // Фильтрация по статусу
        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        // Поиск
        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere(['or',
                ['like', 'return_number', $search],
                ['like', 'reason', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Создание заявки на возврат из админки
     */
    public function actionCreate()
    {
        $model = new ReturnRequest();

        if ($model->load(Yii::$app->request->post())) {
            $model->return_number = 'R' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $model->status = ReturnRequest::STATUS_PENDING;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Заявка на возврат создана: ' . $model->return_number);
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $orders = Order::find()
            ->select(['id', 'order_number', 'client_name', 'total_amount'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(200)
            ->asArray()
            ->all();

        return $this->render('create', [
            'model' => $model,
            'orders' => $orders,
            'reasonList' => ReturnRequest::getReasonList(),
        ]);
    }

    /**
     * Просмотр заявки на возврат
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Одобрение заявки
     */
    public function actionApprove($id)
    {
        $model = $this->findModel($id);
        $comment = Yii::$app->request->post('comment');
        
        $returnService = new ReturnService();
        
        if ($returnService->processReturn($model, true, $comment)) {
            Yii::$app->session->setFlash('success', 'Заявка одобрена');
        } else {
            Yii::$app->session->setFlash('error', $returnService->getErrorMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Отклонение заявки
     */
    public function actionReject($id)
    {
        $model = $this->findModel($id);
        $comment = Yii::$app->request->post('comment');
        
        if (empty($comment)) {
            Yii::$app->session->setFlash('error', 'Укажите причину отклонения');
            return $this->redirect(['view', 'id' => $id]);
        }
        
        $returnService = new ReturnService();
        
        if ($returnService->processReturn($model, false, $comment)) {
            Yii::$app->session->setFlash('success', 'Заявка отклонена');
        } else {
            Yii::$app->session->setFlash('error', $returnService->getErrorMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Начало обработки возврата
     */
    public function actionProcess($id)
    {
        $model = $this->findModel($id);
        
        if ($model->startProcessing()) {
            Yii::$app->session->setFlash('success', 'Возврат переведён в обработку');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при обновлении статуса');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Завершение возврата с возвратом средств
     */
    public function actionComplete($id)
    {
        $model = $this->findModel($id);
        
        $returnService = new ReturnService();
        
        if ($returnService->completeReturn($model)) {
            Yii::$app->session->setFlash('success', 'Возврат завершён. Средства возвращены клиенту.');
        } else {
            Yii::$app->session->setFlash('error', $returnService->getErrorMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * AJAX: отметить шаг чек-листа (алиас completeStep → updateStep)
     */
    public function actionCompleteStep()
    {
        return $this->actionUpdateStep();
    }

    /**
     * PDF договора — рендерит _contract.php без layout
     */
    public function actionPdf($id)
    {
        return $this->actionContract($id);
    }

    /**
     * AJAX: отметить шаг чек-листа комиссионного договора выполненным
     */
    public function actionUpdateStep()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $id   = Yii::$app->request->post('id');
        $step = Yii::$app->request->post('step');

        $allowed = ['contract', 'epass', 'e_signature', 'published'];
        if (!$id || !in_array($step, $allowed, true)) {
            return ['success' => false, 'message' => 'Неверные параметры'];
        }

        try {
            $model = $this->findModel($id);
            $data  = is_string($model->checklist_data ?? null)
                ? json_decode($model->checklist_data, true) ?? []
                : ($model->checklist_data ?? []);

            $currentUser = Yii::$app->user->identity;
            $author      = $currentUser ? $currentUser->username : 'system';
            $date        = date('d.m.Y H:i');

            $data[$step] = [
                'done'   => true,
                'date'   => $date,
                'author' => $author,
            ];

            $model->checklist_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $model->save(false);

            return ['success' => true, 'date' => $date, 'author' => $author];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Печатный шаблон комиссионного договора
     */
    public function actionContract($id)
    {
        $model = $this->findModel($id);
        $this->layout = false;
        return $this->render('_contract', ['model' => $model]);
    }

    /**
     * Статистика возвратов
     */
    public function actionStatistics()
    {
        $stats = [
            'total' => ReturnRequest::find()->count(),
            'pending' => ReturnRequest::find()->where(['status' => ReturnRequest::STATUS_PENDING])->count(),
            'approved' => ReturnRequest::find()->where(['status' => ReturnRequest::STATUS_APPROVED])->count(),
            'rejected' => ReturnRequest::find()->where(['status' => ReturnRequest::STATUS_REJECTED])->count(),
            'completed' => ReturnRequest::find()->where(['status' => ReturnRequest::STATUS_COMPLETED])->count(),
            'totalRefunded' => ReturnRequest::find()
                ->where(['status' => ReturnRequest::STATUS_COMPLETED])
                ->sum('refund_amount') ?: 0,
        ];

        // Топ причин возврата
        $topReasons = ReturnRequest::find()
            ->select(['reason', 'COUNT(*) as count'])
            ->groupBy('reason')
            ->orderBy(['count' => SORT_DESC])
            ->limit(5)
            ->asArray()
            ->all();

        return $this->render('statistics', [
            'stats' => $stats,
            'topReasons' => $topReasons,
        ]);
    }

    /**
     * Найти модель по ID
     */
    protected function findModel($id)
    {
        if (($model = ReturnRequest::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Заявка не найдена');
    }
}
