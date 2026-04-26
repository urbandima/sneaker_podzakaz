<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\backend\modules\automation\models\AutomationTrigger;
use app\backend\modules\automation\models\AutomationLog;

class AutomationController extends BaseAdminController
{
    protected bool $adminOnly = true;

    public function actionIndex()
    {
        $triggers = AutomationTrigger::find()->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])->all();
        $eventCodes = AutomationTrigger::getEventCodes();

        return $this->render('index', [
            'triggers'   => $triggers,
            'eventCodes' => $eventCodes,
        ]);
    }

    public function actionCreate()
    {
        $model = new AutomationTrigger();

        if (Yii::$app->request->isPost) {
            return $this->save($model);
        }

        return $this->render('_form', [
            'model'      => $model,
            'eventCodes' => AutomationTrigger::getEventCodes(),
            'operators'  => AutomationTrigger::getOperators(),
            'actionTypes'=> AutomationTrigger::getActionTypes(),
        ]);
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findTrigger($id);

        if (Yii::$app->request->isPost) {
            return $this->save($model);
        }

        return $this->render('_form', [
            'model'      => $model,
            'eventCodes' => AutomationTrigger::getEventCodes(),
            'operators'  => AutomationTrigger::getOperators(),
            'actionTypes'=> AutomationTrigger::getActionTypes(),
        ]);
    }

    public function actionDelete(int $id)
    {
        $model = $this->findTrigger($id);
        $model->delete();
        $this->flashSuccess('Триггер удалён.');
        return $this->redirect(['/admin/settings/triggers']);
    }

    public function actionToggle(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = $this->findTrigger($id);
        $model->is_active = $model->is_active ? 0 : 1;
        if ($model->save(false)) {
            return ['success' => true, 'is_active' => (bool)$model->is_active];
        }
        return ['success' => false];
    }

    public function actionLog()
    {
        $query = AutomationLog::find()
            ->with('trigger')
            ->orderBy(['id' => SORT_DESC])
            ->limit(200);

        $logs = $query->all();

        return $this->render('log', ['logs' => $logs]);
    }

    private function save(AutomationTrigger $model): Response
    {
        $post = Yii::$app->request->post();

        $model->name        = $post['name'] ?? '';
        $model->description = $post['description'] ?? '';
        $model->event_code  = $post['event_code'] ?? '';
        $model->is_active   = (int)($post['is_active'] ?? 1);
        $model->priority    = (int)($post['priority'] ?? 10);

        // Build conditions JSON from form rows
        $condFields   = $post['cond_field']    ?? [];
        $condOps      = $post['cond_operator'] ?? [];
        $condVals     = $post['cond_value']    ?? [];
        $conditions = [];
        foreach ($condFields as $i => $field) {
            if (empty($field)) continue;
            $conditions[] = [
                'field'    => $field,
                'operator' => $condOps[$i] ?? 'equals',
                'value'    => $condVals[$i] ?? null,
            ];
        }
        $model->conditions = json_encode($conditions, JSON_UNESCAPED_UNICODE);

        // Build actions JSON from form rows
        $actTypes  = $post['act_type']   ?? [];
        $actParams = $post['act_params']  ?? [];
        $actions = [];
        foreach ($actTypes as $i => $type) {
            if (empty($type)) continue;
            $raw = $actParams[$i] ?? '{}';
            $params = json_decode($raw, true) ?? [];
            $actions[] = array_merge(['type' => $type], $params);
        }
        $model->actions = json_encode($actions, JSON_UNESCAPED_UNICODE);

        $isNew = $model->isNewRecord;
        if ($model->save()) {
            $this->flashSuccess($isNew ? 'Триггер создан.' : 'Триггер сохранён.');
            return $this->redirect(['/admin/settings/triggers']);
        }

        $this->flashError('Ошибка сохранения: ' . implode(', ', $model->getFirstErrors()));
        return $this->redirect(['/admin/settings/triggers']);
    }

    private function findTrigger(int $id): AutomationTrigger
    {
        $model = AutomationTrigger::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Триггер не найден.');
        }
        return $model;
    }
}
