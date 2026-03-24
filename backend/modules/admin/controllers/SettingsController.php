<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;

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
}
