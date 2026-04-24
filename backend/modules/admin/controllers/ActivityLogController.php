<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use app\backend\modules\admin\models\AdminLog;

class ActivityLogController extends BaseAdminController
{
    protected bool $adminOnly = true;

    public function actionIndex()
    {
        $filterAction = Yii::$app->request->get('action', '');
        $filterUser   = Yii::$app->request->get('user', '');
        $filterEntity = Yii::$app->request->get('entity', '');
        $filterDate   = Yii::$app->request->get('date', '');

        $query = AdminLog::find()->orderBy(['created_at' => SORT_DESC]);

        if ($filterAction) {
            $query->andWhere(['action' => $filterAction]);
        }
        if ($filterUser) {
            $query->andWhere(['like', 'username', $filterUser]);
        }
        if ($filterEntity) {
            $query->andWhere(['entity_type' => $filterEntity]);
        }
        if ($filterDate) {
            $query->andWhere(['>=', 'created_at', $filterDate . ' 00:00:00'])
                  ->andWhere(['<=', 'created_at', $filterDate . ' 23:59:59']);
        }

        $logs = $query->limit(500)->all();

        $actionTypes = [
            AdminLog::ACTION_CREATE        => 'Создание',
            AdminLog::ACTION_UPDATE        => 'Изменение',
            AdminLog::ACTION_DELETE        => 'Удаление',
            AdminLog::ACTION_STATUS_CHANGE => 'Смена статуса',
            AdminLog::ACTION_BULK_ACTION   => 'Массовое действие',
            AdminLog::ACTION_EXPORT        => 'Экспорт',
            AdminLog::ACTION_IMPORT        => 'Импорт',
            AdminLog::ACTION_LOGIN         => 'Вход',
            AdminLog::ACTION_LOGOUT        => 'Выход',
        ];

        return $this->render('index', [
            'logs'         => $logs,
            'actionTypes'  => $actionTypes,
            'filterAction' => $filterAction,
            'filterUser'   => $filterUser,
            'filterEntity' => $filterEntity,
            'filterDate'   => $filterDate,
        ]);
    }
}
