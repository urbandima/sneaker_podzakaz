<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * Buyout module — stub controller.
 * Full implementation: see docs/PROCUREMENT_BUYOUT_PLAN.md
 */
class BuyoutController extends BaseAdminController
{
    public function behaviors(): array
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'accept'       => ['POST'],
                    'cancel'       => ['POST'],
                    'link-order'   => ['POST'],
                    'unlink-order' => ['POST'],
                    'bulk-status'  => ['POST'],
                    'parse-url'    => ['POST'],
                ],
            ],
        ]);
    }

    public function actionIndex(): string
    {
        $this->view->title = 'Выкупы';
        return $this->render('index');
    }

    public function actionCreate()
    {
        $this->view->title = 'Новый выкуп';
        return $this->render('create');
    }

    public function actionView(int $id): string
    {
        $this->view->title = 'Выкуп #' . $id;
        return $this->render('view', ['id' => $id]);
    }

    public function actionEdit(int $id)
    {
        $this->view->title = 'Редактировать выкуп #' . $id;
        return $this->render('create', ['id' => $id]);
    }

    public function actionAccept(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function actionCancel(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function actionParseUrl()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function actionLinkOrder(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function actionUnlinkOrder(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function actionBulkStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['success' => false, 'message' => 'Not implemented'];
    }

    public function actionHistory(int $id): string
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return json_encode(['history' => []]);
    }
}
