<?php

namespace app\controllers\admin;

use Yii;
use yii\web\NotFoundHttpException;
use app\models\CompanySettings;
use app\models\OrderStatus;
use app\models\ChangePasswordForm;

/**
 * DashboardController - Главная панель администратора
 * 
 * Методы:
 * - index() - Главная страница (редирект на заказы)
 * - profile() - Профиль пользователя и смена пароля
 * - settings() - Настройки системы (только для админа)
 * - logout() - Выход из системы
 */
class DashboardController extends BaseAdminController
{
    /**
     * Главная страница админ-панели
     * Перенаправляет на список заказов
     */
    public function actionIndex()
    {
        return $this->redirect(['/admin/order/index']);
    }

    /**
     * Профиль пользователя и смена пароля
     */
    public function actionProfile()
    {
        $user = $this->getCurrentUser();
        $model = new ChangePasswordForm();

        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            $this->flashSuccess('Пароль успешно изменен');
            return $this->refresh();
        }

        return $this->render('/admin/profile', [
            'model' => $model,
            'user' => $user,
        ]);
    }

    /**
     * Настройки системы
     * Доступ только для администраторов
     */
    public function actionSettings()
    {
        // Только админ может изменять настройки
        if (!$this->isAdmin()) {
            throw new NotFoundHttpException('Доступ запрещен.');
        }

        $settings = CompanySettings::find()->orderBy(['id' => SORT_ASC])->one();
        if (!$settings) {
            $settings = new CompanySettings();
        }

        $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            
            // Сохранение реквизитов компании
            if ($settings->load($post) && $settings->validate()) {
                $settings->updated_at = time();
                $settings->save(false);
            }

            // Обновление статусов
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Обновляем существующие статусы
                $postedStatuses = $post['statuses'] ?? [];
                foreach ($postedStatuses as $key => $data) {
                    $model = OrderStatus::findOne(['key' => $key]);
                    if ($model) {
                        $model->label = trim($data['label'] ?? $model->label);
                        $model->sort = (int)($data['sort'] ?? $model->sort);
                        $model->logist_available = !empty($data['logist_available']);
                        $model->is_active = !empty($data['is_active']);
                        $model->save(false);
                    }
                }

                // Добавление нового статуса
                $newStatus = $post['new_status'] ?? [];
                if (!empty($newStatus['key']) && !empty($newStatus['label'])) {
                    if (!OrderStatus::find()->where(['key' => $newStatus['key']])->exists()) {
                        $statusModel = new OrderStatus();
                        $statusModel->key = trim($newStatus['key']);
                        $statusModel->label = trim($newStatus['label']);
                        $statusModel->sort = (int)($newStatus['sort'] ?? 999);
                        $statusModel->logist_available = !empty($newStatus['logist_available']);
                        $statusModel->is_active = 1; // Новые статусы по умолчанию активны
                        $statusModel->save(false);
                    }
                }

                $transaction->commit();
                $this->flashSuccess('Настройки сохранены');
                
                // Обновляем данные для рендера
                $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->all();
                
            } catch (\Throwable $e) {
                $transaction->rollBack();
                Yii::error('Ошибка при сохранении настроек: ' . $e->getMessage(), 'admin');
                $this->flashError('Ошибка при сохранении: ' . $e->getMessage());
            }
        }

        return $this->render('/admin/settings', [
            'settings' => $settings,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Выход из системы
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }
}
