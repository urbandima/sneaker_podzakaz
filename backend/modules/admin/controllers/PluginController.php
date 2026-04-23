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

    public function actionLamoda()
    {
        return $this->render('lamoda');
    }

    public function actionLamodaParser()
    {
        $lastResult = Yii::$app->settings->get('lamoda', 'last_parse_result', '{}');
        $schedule   = Yii::$app->settings->get('lamoda', 'schedule', 'manual');
        $lastUrl    = Yii::$app->settings->get('lamoda', 'last_url', '');

        $products = [];
        try {
            $products = \app\backend\modules\catalog\models\Product::find()
                ->where(['source' => 'lamoda'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(50)
                ->all();
        } catch (\Exception $e) {}

        return $this->render('lamoda-parser', [
            'lastResult' => json_decode($lastResult, true) ?: [],
            'schedule'   => $schedule,
            'lastUrl'    => $lastUrl,
            'products'   => $products,
        ]);
    }

    public function actionLamodaRun(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $url   = trim(Yii::$app->request->post('url', ''));
        $limit = (int)Yii::$app->request->post('limit', 50);

        if ($url) {
            Yii::$app->settings->set('lamoda', 'last_url', $url);
        } else {
            $url = Yii::$app->settings->get('lamoda', 'last_url', '');
        }

        if (!$url) {
            return ['success' => false, 'message' => 'URL не указан'];
        }

        $scriptPath = Yii::getAlias('@app/scripts/parse_lamoda.php');
        if (!file_exists($scriptPath)) {
            return ['success' => false, 'message' => 'Скрипт парсера не найден'];
        }

        $cmd = sprintf(
            'php %s %s %d > /tmp/lamoda_parse.log 2>&1 &',
            escapeshellarg($scriptPath),
            escapeshellarg($url),
            $limit
        );
        exec($cmd);

        Yii::$app->settings->set('lamoda', 'parse_status', 'running');
        Yii::$app->settings->set('lamoda', 'parse_started_at', time());

        return ['success' => true, 'message' => 'Парсинг запущен в фоне'];
    }

    public function actionLamodaStatus(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $status     = Yii::$app->settings->get('lamoda', 'parse_status', 'idle');
        $lastResult = json_decode(Yii::$app->settings->get('lamoda', 'last_parse_result', '{}'), true) ?: [];
        $log = '';
        if (file_exists('/tmp/lamoda_parse.log')) {
            $log = trim(shell_exec('tail -20 /tmp/lamoda_parse.log') ?: '');
        }
        return ['success' => true, 'status' => $status, 'result' => $lastResult, 'log' => $log];
    }

    public function actionLamodaSaveSchedule(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $schedule = Yii::$app->request->post('schedule', 'manual');
        if (!in_array($schedule, ['manual', 'daily', 'weekly'])) {
            $schedule = 'manual';
        }
        Yii::$app->settings->set('lamoda', 'schedule', $schedule);
        return ['success' => true, 'message' => 'Расписание сохранено'];
    }
}
