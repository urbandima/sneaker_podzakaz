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
        $s = Yii::$app->settings;
        $tab = Yii::$app->request->get('tab', 'settings');

        $logs = [];
        if ($tab === 'logs') {
            try {
                $logs = Yii::$app->db->createCommand(
                    'SELECT * FROM {{%amocrm_log}} ORDER BY created_at DESC LIMIT 100'
                )->queryAll();
            } catch (\Exception $e) {}
        }

        return $this->render('amocrm', [
            'tab'          => $tab,
            'isConfigured' => !empty($s->get('amocrm', 'domain')) && !empty($s->get('amocrm', 'access_token')),
            'logs'         => $logs,
            'settings'     => [
                'domain'              => $s->get('amocrm', 'domain', ''),
                'client_id'           => $s->get('amocrm', 'client_id', ''),
                'pipeline_id'         => $s->get('amocrm', 'pipeline_id', ''),
                'responsible_user_id' => $s->get('amocrm', 'responsible_user_id', ''),
                'new_order_status_id' => $s->get('amocrm', 'new_order_status_id', ''),
                'paid_status_id'      => $s->get('amocrm', 'paid_status_id', ''),
                'auto_create_lead'    => $s->get('amocrm', 'auto_create_lead', '0'),
                'auto_sync'           => $s->get('amocrm', 'auto_sync', '0'),
                'token_expires_at'    => $s->get('amocrm', 'token_expires_at', '0'),
                'last_sync_at'        => $s->get('amocrm', 'last_sync_at', '0'),
                'last_sync_count'     => $s->get('amocrm', 'last_sync_count', '0'),
            ],
        ]);
    }

    public function actionAmocrmAuthorize()
    {
        $s = Yii::$app->settings;
        $clientId   = $s->get('amocrm', 'client_id', '');
        $domain     = $s->get('amocrm', 'domain', '');
        $redirectUri = Yii::$app->urlManager->createAbsoluteUrl(['/admin/plugin/amocrm/callback']);

        if (!$clientId || !$domain) {
            Yii::$app->session->setFlash('error', 'Укажите домен и Client ID перед авторизацией');
            return $this->redirect(['/admin/plugin/amocrm']);
        }

        $state = Yii::$app->security->generateRandomString(16);
        Yii::$app->session->set('amocrm_oauth_state', $state);

        $authUrl = 'https://www.amocrm.ru/oauth?' . http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'state'         => $state,
            'mode'          => 'post_message',
        ]);

        return $this->redirect($authUrl);
    }

    public function actionAmocrmCallback()
    {
        $code          = Yii::$app->request->get('code');
        $state         = Yii::$app->request->get('state');
        $expectedState = Yii::$app->session->get('amocrm_oauth_state');

        if (!$code) {
            Yii::$app->session->setFlash('error', 'Код авторизации не получен');
            return $this->redirect(['/admin/plugin/amocrm']);
        }
        if ($state !== $expectedState) {
            Yii::$app->session->setFlash('error', 'Ошибка CSRF-проверки OAuth');
            return $this->redirect(['/admin/plugin/amocrm']);
        }

        $s           = Yii::$app->settings;
        $clientId    = $s->get('amocrm', 'client_id', '');
        $clientSecret = $s->get('amocrm', 'client_secret', '');
        $domain      = $s->get('amocrm', 'domain', '');
        $redirectUri  = Yii::$app->urlManager->createAbsoluteUrl(['/admin/plugin/amocrm/callback']);

        $ch = curl_init('https://' . $domain . '/oauth2/access_token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $redirectUri,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($body, true);

        if (!empty($data['access_token'])) {
            $s->set('amocrm', 'access_token',  $data['access_token']);
            $s->set('amocrm', 'refresh_token', $data['refresh_token']);
            $s->set('amocrm', 'token_expires_at', (string)(time() + (int)($data['expires_in'] ?? 86400)));
            Yii::$app->session->remove('amocrm_oauth_state');
            Yii::$app->session->setFlash('success', 'AmoCRM успешно подключён!');
        } else {
            $hint = $data['hint'] ?? ($data['title'] ?? 'неизвестная ошибка');
            Yii::$app->session->setFlash('error', 'Не удалось получить токен: ' . $hint);
        }

        return $this->redirect(['/admin/plugin/amocrm']);
    }

    public function actionAmocrmSave(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $s      = Yii::$app->settings;
        $fields = [
            'domain', 'client_id', 'client_secret',
            'pipeline_id', 'responsible_user_id',
            'new_order_status_id', 'paid_status_id',
            'auto_create_lead', 'auto_sync',
        ];
        foreach ($fields as $f) {
            $val = Yii::$app->request->post($f);
            if ($val !== null) {
                $s->set('amocrm', $f, (string)$val);
            }
        }
        return ['success' => true, 'message' => 'Настройки сохранены'];
    }

    public function actionAmocrmTest(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var \app\backend\shared\components\AmocrmClient $amo */
        $amo = Yii::$app->amocrm;
        if (!$amo->isConfigured()) {
            return ['success' => false, 'message' => 'Не заполнены обязательные поля (домен, токен)'];
        }
        $users = $amo->getUsers();
        if ($users) {
            return ['success' => true, 'message' => 'Подключение успешно. Пользователей: ' . count($users)];
        }
        return ['success' => false, 'message' => 'Нет ответа. Проверьте токен и домен.'];
    }

    public function actionAmocrmSync(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var \app\backend\shared\components\AmocrmClient $amo */
        $amo = Yii::$app->amocrm;
        if (!$amo->isConfigured()) {
            return ['success' => false, 'message' => 'AmoCRM не настроен'];
        }

        $orderId = (int)Yii::$app->request->post('order_id', 0);
        $status  = Yii::$app->request->post('status', '');
        $limit   = min((int)Yii::$app->request->post('limit', 50), 200);

        if ($orderId) {
            $order = \app\backend\modules\checkout\models\Order::findOne($orderId);
            if (!$order) return ['success' => false, 'message' => 'Заказ не найден'];
            $ok = $this->syncOrderToAmo($amo, $order);
            return ['success' => $ok, 'message' => $ok ? "Заказ #{$orderId} синхронизирован" : 'Ошибка синхронизации'];
        }

        $query = \app\backend\modules\checkout\models\Order::find()
            ->andWhere(['IS', 'amocrm_lead_id', null])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);
        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        $orders = $query->all();
        $synced = 0;
        foreach ($orders as $order) {
            if ($this->syncOrderToAmo($amo, $order)) $synced++;
        }

        Yii::$app->settings->set('amocrm', 'last_sync_at', (string)time());
        Yii::$app->settings->set('amocrm', 'last_sync_count', (string)$synced);

        return ['success' => true, 'message' => "Синхронизировано: {$synced} из " . count($orders)];
    }

    private function syncOrderToAmo(\app\backend\shared\components\AmocrmClient $amo, $order): bool
    {
        try {
            $s = Yii::$app->settings;
            $leadData = [
                'name'  => 'Заказ #' . $order->id . ($order->recipient_name ? ' — ' . $order->recipient_name : ''),
                'price' => (int)($order->total ?? 0),
            ];
            if ($pid = (int)$s->get('amocrm', 'pipeline_id', 0)) $leadData['pipeline_id'] = $pid;
            if ($sid = (int)$s->get('amocrm', 'new_order_status_id', 0)) $leadData['status_id'] = $sid;
            if ($rid = (int)$s->get('amocrm', 'responsible_user_id', 0)) $leadData['responsible_user_id'] = $rid;

            $lead = $amo->createLead($leadData);
            if (!$lead || empty($lead['id'])) return false;

            $order->amocrm_lead_id      = $lead['id'];
            $order->amocrm_last_sync_at = time();
            $order->save(false);

            if (!empty($order->recipient_phone)) {
                $contact = $amo->findContactByPhone($order->recipient_phone);
                if (!$contact) {
                    $amo->createContact([
                        'name'                 => $order->recipient_name ?? 'Покупатель',
                        'custom_fields_values' => [[
                            'field_code' => 'PHONE',
                            'values'     => [['value' => $order->recipient_phone]],
                        ]],
                    ]);
                }
            }

            $amo->addNote($lead['id'], 'Заказ из магазина. Сумма: ' . $order->total . ' BYN. Статус: ' . ($order->status ?? ''));
            return true;
        } catch (\Exception $e) {
            Yii::error('AmoCRM sync: ' . $e->getMessage(), 'amocrm');
            return false;
        }
    }

    public function actionAmocrmLogs(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $page   = max(1, (int)Yii::$app->request->get('page', 1));
        $status = Yii::$app->request->get('status', '');
        $limit  = 50;
        $offset = ($page - 1) * $limit;

        try {
            $where  = $status ? ' WHERE status = :st' : '';
            $params = $status ? [':st' => $status] : [];
            $rows   = Yii::$app->db->createCommand(
                'SELECT id, direction, event, status, response_ms, created_at FROM {{%amocrm_log}}'
                . $where . ' ORDER BY created_at DESC LIMIT ' . $limit . ' OFFSET ' . $offset,
                $params
            )->queryAll();
            $total = (int)Yii::$app->db->createCommand(
                'SELECT COUNT(*) FROM {{%amocrm_log}}' . $where, $params
            )->queryScalar();
            return ['success' => true, 'rows' => $rows, 'total' => $total, 'page' => $page];
        } catch (\Exception $e) {
            return ['success' => true, 'rows' => [], 'total' => 0, 'page' => 1];
        }
    }

    public function actionAmocrmStats(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $db     = Yii::$app->db;
            $total  = (int)$db->createCommand('SELECT COUNT(*) FROM {{%amocrm_log}}')->queryScalar();
            $ok     = (int)$db->createCommand("SELECT COUNT(*) FROM {{%amocrm_log}} WHERE status='ok'")->queryScalar();
            $avgMs  = (int)$db->createCommand('SELECT AVG(response_ms) FROM {{%amocrm_log}}')->queryScalar();
            $synced = (int)$db->createCommand('SELECT COUNT(*) FROM {{%order}} WHERE amocrm_lead_id IS NOT NULL')->queryScalar();
            $byDay  = $db->createCommand(
                "SELECT DATE(FROM_UNIXTIME(created_at)) AS d, COUNT(*) AS cnt
                 FROM {{%amocrm_log}}
                 WHERE created_at > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 14 DAY))
                 GROUP BY d ORDER BY d"
            )->queryAll();
            return [
                'success'       => true,
                'total'         => $total,
                'ok'            => $ok,
                'fail'          => $total - $ok,
                'avg_ms'        => $avgMs,
                'synced_orders' => $synced,
                'by_day'        => $byDay,
            ];
        } catch (\Exception $e) {
            return ['success' => true, 'total' => 0, 'ok' => 0, 'fail' => 0,
                    'avg_ms' => 0, 'synced_orders' => 0, 'by_day' => []];
        }
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
