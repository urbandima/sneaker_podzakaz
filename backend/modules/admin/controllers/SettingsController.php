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
        
        // Если статусов нет в БД, создаем дефолтные
        if (empty($statuses)) {
            $defaultStatuses = [
                ['key' => 'new', 'label' => 'Новый', 'color' => 'info', 'description' => 'Заказ создан, ожидает подтверждения', 'is_active' => true, 'sort' => 10],
                ['key' => 'confirmed', 'label' => 'Подтвержден', 'color' => 'primary', 'description' => 'Менеджер подтвердил заказ', 'is_active' => true, 'sort' => 20],
                ['key' => 'paid', 'label' => 'Оплачен', 'color' => 'success', 'description' => 'Получена 100% предоплата', 'is_active' => true, 'sort' => 30],
                ['key' => 'ordered', 'label' => 'Выкуплен', 'color' => 'warning', 'description' => 'Товар заказан у поставщика', 'is_active' => true, 'sort' => 40],
                ['key' => 'shipped_china', 'label' => 'В доставке из Китая', 'color' => 'info', 'description' => 'Товар в пути из Китая', 'is_active' => true, 'sort' => 50],
                ['key' => 'customs', 'label' => 'Таможня', 'color' => 'warning', 'description' => 'Прохождение таможни', 'is_active' => true, 'sort' => 60],
                ['key' => 'warehouse', 'label' => 'На складе РБ', 'color' => 'primary', 'description' => 'Прибыл на склад в Беларуси', 'is_active' => true, 'sort' => 70],
                ['key' => 'shipped_local', 'label' => 'Передан в доставку РБ', 'color' => 'info', 'description' => 'Отправлен клиенту', 'is_active' => true, 'sort' => 80],
                ['key' => 'completed', 'label' => 'Завершен', 'color' => 'success', 'description' => 'Клиент получил заказ', 'is_active' => true, 'sort' => 90],
                ['key' => 'cancelled', 'label' => 'Отменен', 'color' => 'danger', 'description' => 'Заказ отменен', 'is_active' => true, 'sort' => 100],
            ];
            
            foreach ($defaultStatuses as $status) {
                $model = new OrderStatus();
                $model->attributes = $status;
                $model->save();
            }
            
            $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->asArray()->all();
        }
        
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
            
            // Сначала деактивируем все статусы
            OrderStatus::updateAll(['is_active' => false]);
            
            foreach ($statuses as $index => $statusData) {
                $model = OrderStatus::findOne(['key' => $statusData['key']]) ?? new OrderStatus();
                $model->key = $statusData['key'];
                $model->label = $statusData['label'];
                $model->color = $statusData['color'] ?? 'info';
                $model->description = $statusData['description'] ?? '';
                $model->is_active = $statusData['active'] ?? true;
                $model->sort = ($index + 1) * 10;
                
                if (!$model->save()) {
                    throw new \Exception('Ошибка сохранения статуса: ' . json_encode($model->errors));
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
