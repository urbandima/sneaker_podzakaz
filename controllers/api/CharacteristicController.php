<?php

namespace app\controllers\api;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\Cors;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\SessionAuth;
use yii\rest\ActiveController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Characteristic;
use app\models\CharacteristicValue;

/**
 * REST API: Управление характеристиками и их значениями
 */
class CharacteristicController extends ActiveController
{
    /** @inheritdoc */
    public $modelClass = Characteristic::class;

    /** @inheritdoc */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Гарантируем JSON даже для text/html запросов
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;

        // CORS для интеграций
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Allow-Headers' => ['*'],
            ],
        ];

        // Аутентификация: токен или сессия
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'except' => ['index', 'view', 'values'],
            'authMethods' => [
                HttpBearerAuth::class,
                SessionAuth::class,
            ],
        ];

        // AccessControl для write операций
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'only' => ['create', 'update', 'delete', 'create-value', 'update-value', 'delete-value'],
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['@'],
                    'matchCallback' => function () {
                        return !Yii::$app->user->isGuest && Yii::$app->user->identity->isAdmin();
                    },
                ],
            ],
        ];

        return $behaviors;
    }

    /** @inheritdoc */
    protected function verbs()
    {
        return [
            'index' => ['GET', 'HEAD'],
            'view' => ['GET', 'HEAD'],
            'create' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
            'values' => ['GET'],
            'create-value' => ['POST'],
            'update-value' => ['PUT', 'PATCH'],
            'delete-value' => ['DELETE'],
        ];
    }

    /**
     * Пользовательские data provider для списка характеристик
     */
    public function prepareDataProvider()
    {
        $query = Characteristic::find();
        $request = Yii::$app->request;

        if (($type = $request->get('type')) !== null) {
            $query->andWhere(['type' => $type]);
        }
        if (($isFilter = $request->get('is_filter')) !== null) {
            $query->andWhere(['is_filter' => (int)$isFilter]);
        }
        if (($isActive = $request->get('is_active')) !== null) {
            $query->andWhere(['is_active' => (int)$isActive]);
        }

        $query->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => (int)$request->get('per-page', 50),
                'pageParam' => 'page',
                'pageSizeParam' => 'per-page',
            ],
        ]);
    }

    /** @inheritdoc */
    public function actions()
    {
        $actions = parent::actions();
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        return $actions;
    }

    /**
     * GET /api/characteristic/{id}/values
     */
    public function actionValues($id)
    {
        $model = $this->findModel($id);
        return $model->getValues()->orderBy(['sort_order' => SORT_ASC])->asArray()->all();
    }

    /**
     * POST /api/characteristic/{id}/values
     */
    public function actionCreateValue($id)
    {
        $characteristic = $this->findModel($id);
        $this->ensureAdmin();

        $value = new CharacteristicValue([
            'characteristic_id' => $characteristic->id,
        ]);

        $value->load(Yii::$app->request->getBodyParams(), '');
        if ($value->save()) {
            Yii::$app->response->setStatusCode(201);
            return $value;
        }

        Yii::$app->response->setStatusCode(422);
        return $value->getErrors();
    }

    /**
     * PUT/PATCH /api/characteristic/{id}/values/{valueId}
     */
    public function actionUpdateValue($id, $valueId)
    {
        $this->ensureAdmin();
        $value = $this->findValue($id, $valueId);
        $value->load(Yii::$app->request->getBodyParams(), '');

        if ($value->save()) {
            return $value;
        }

        Yii::$app->response->setStatusCode(422);
        return $value->getErrors();
    }

    /**
     * DELETE /api/characteristic/{id}/values/{valueId}
     */
    public function actionDeleteValue($id, $valueId)
    {
        $this->ensureAdmin();
        $value = $this->findValue($id, $valueId);
        if ($value->delete() === false) {
            Yii::$app->response->setStatusCode(500);
            return ['success' => false];
        }
        Yii::$app->response->setStatusCode(204);
        return null;
    }

    /** @inheritdoc */
    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['create', 'update', 'delete'], true)) {
            $this->ensureAdmin();
        }
    }

    /**
     * Поиск характеристики
     */
    protected function findModel($id): Characteristic
    {
        if (($model = Characteristic::findOne($id)) === null) {
            throw new NotFoundHttpException('Характеристика не найдена.');
        }
        return $model;
    }

    /**
     * Поиск значения
     */
    protected function findValue($characteristicId, $valueId): CharacteristicValue
    {
        $value = CharacteristicValue::findOne([
            'id' => $valueId,
            'characteristic_id' => $characteristicId,
        ]);

        if ($value === null) {
            throw new NotFoundHttpException('Значение характеристики не найдено.');
        }

        return $value;
    }

    /**
     * Проверка прав администратора
     */
    protected function ensureAdmin(): void
    {
        if (Yii::$app->user->isGuest || !Yii::$app->user->identity->isAdmin()) {
            throw new ForbiddenHttpException('Требуются права администратора.');
        }
    }
}
