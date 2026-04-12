<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\infrastructure\plugins\PluginManager;

class PluginController extends BaseAdminController
{
    /**
     * Список всех плагинов
     */
    public function actionIndex()
    {
        $manager = PluginManager::getInstance();
        
        $plugins = $manager->getAllPlugins();
        $activePlugins = $manager->getActivePlugins();
        
        return $this->render('index', [
            'plugins' => $plugins,
            'activePlugins' => $activePlugins,
        ]);
    }
    
    /**
     * Активация/деактивация плагина
     */
    public function actionToggle()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $id = Yii::$app->request->post('id');
        $action = Yii::$app->request->post('action');
        
        if (!$id || !in_array($action, ['activate', 'deactivate'])) {
            return ['success' => false, 'message' => 'Неверные параметры'];
        }
        
        $manager = PluginManager::getInstance();
        
        if ($action === 'activate') {
            $result = $manager->activatePlugin($id);
            $message = $result ? 'Плагин активирован' : 'Ошибка активации';
        } else {
            $result = $manager->deactivatePlugin($id);
            $message = $result ? 'Плагин деактивирован' : 'Ошибка деактивации';
        }
        
        return [
            'success' => $result,
            'message' => $message,
        ];
    }
    
    /**
     * Настройки плагина (для динамических плагинов из PluginManager)
     */
    public function actionSettings($id)
    {
        $manager = PluginManager::getInstance();
        $plugin = $manager->getPlugin($id);

        if (!$plugin) {
            throw new \yii\web\NotFoundHttpException('Плагин не найден');
        }

        if (Yii::$app->request->isPost) {
            $settings = Yii::$app->request->post('settings', []);

            if ($manager->savePluginSettings($id, $settings)) {
                Yii::$app->session->setFlash('success', 'Настройки сохранены');
                return $this->redirect(['index']);
            } else {
                Yii::$app->session->setFlash('error', 'Ошибка сохранения настроек');
            }
        }

        return $this->render('settings', [
            'plugin' => $plugin,
            'settings' => $plugin->getSettings(),
        ]);
    }

    // ----------------------------------------------------------------
    // Встроенные интеграции — индивидуальные страницы настроек
    // ----------------------------------------------------------------

    public function actionMoysklad()
    {
        return $this->render('moysklad');
    }

    public function actionAmocrm()
    {
        return $this->render('amocrm');
    }

    public function actionTelegram()
    {
        return $this->render('telegram');
    }

    public function actionCurrency()
    {
        return $this->render('currency');
    }

    public function actionDobropost()
    {
        return $this->render('dobropost');
    }
}
