<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\checkout\models\OrderStatus;

class SettingsController extends BaseAdminController
{
    /**
     * Настройки системы
     */
    public function actionIndex()
    {
        $settings = Yii::$app->settings;
        
        return $this->render('index', [
            'settings' => $settings,
        ]);
    }
    
    /**
     * Сохранение настроек
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = Yii::$app->request->post();
        
        if (empty($data)) {
            return ['success' => false, 'message' => 'Нет данных для сохранения'];
        }
        
        try {
            foreach ($data as $section => $settings) {
                if (is_array($settings)) {
                    foreach ($settings as $key => $value) {
                        Yii::$app->settings->set($section, $key, $value);
                    }
                }
            }
            return ['success' => true, 'message' => 'Настройки сохранены'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка сохранения: ' . $e->getMessage()];
        }
    }
    
    /**
     * Настройка статусов заказов
     */
    public function actionStatuses()
    {
        $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->asArray()->all();
        
        return $this->render('statuses', [
            'statuses' => $statuses,
        ]);
    }
    
    /**
     * Сохранение статусов
     */
    public function actionSaveStatuses()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = Yii::$app->request->post();
        $statuses = $data['statuses'] ?? [];
        
        try {
            $transaction = Yii::$app->db->beginTransaction();
            
            foreach ($statuses as $index => $statusData) {
                $model = OrderStatus::findOne(['key' => $statusData['key']]);
                if ($model) {
                    $model->label = $statusData['label'];
                    $model->color = $statusData['color'] ?? 'secondary';
                    $model->is_active = $statusData['active'] ?? true;
                    $model->sort = ($index + 1) * 10;
                    
                    if (!$model->save()) {
                        throw new \Exception('Ошибка сохранения статуса: ' . json_encode($model->errors));
                    }
                }
            }
            
            $transaction->commit();
            return ['success' => true, 'message' => 'Статусы сохранены'];
        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Настройка интеграций
     */
    public function actionIntegrations()
    {
        return $this->render('integrations');
    }
}
