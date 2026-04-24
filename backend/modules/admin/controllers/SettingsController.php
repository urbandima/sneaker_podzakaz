<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\shared\services\TelegramService;
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
     * Настройки интеграций — перенесено в /admin/plugin
     */
    public function actionIntegrations()
    {
        return $this->redirect(['/admin/plugin/index']);
    }
    
    /**
     * Сохранение настроек
     */
    public function actionSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post();

        // Fallback: parse JSON body (when Content-Type: application/json)
        if (empty($data)) {
            $raw = Yii::$app->request->getRawBody();
            $data = json_decode($raw, true) ?? [];
        }

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
     * Сохранение настроек программы лояльности
     */
    public function actionSaveLoyalty()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true) ?? [];

        try {
            if (isset($data['loyalty']) && is_array($data['loyalty'])) {
                foreach ($data['loyalty'] as $key => $value) {
                    Yii::$app->settings->set('loyalty', $key, $value);
                }
            }
            return ['success' => true, 'message' => 'Настройки лояльности сохранены'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Сохранение настроек компании
     */
    public function actionSaveCompany()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $fields = ['name', 'unp', 'address', 'phone', 'email', 'work_time', 'bank', 'bic', 'account'];
        $data = Yii::$app->request->post();
        if (empty($data)) {
            $raw = Yii::$app->request->getRawBody();
            $data = json_decode($raw, true) ?? [];
        }

        try {
            $company = Yii::$app->settings->getCompany() ?? [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $company[$field] = $data[$field];
                }
            }
            Yii::$app->settings->set('company', 'data', json_encode($company, JSON_UNESCAPED_UNICODE));
            foreach ($company as $k => $v) {
                Yii::$app->settings->set('company', $k, $v);
            }
            // Sync to company_settings table (used by checkout/view.php)
            $cs = \app\backend\modules\admin\models\CompanySettings::find()->one();
            if (!$cs) {
                $cs = new \app\backend\modules\admin\models\CompanySettings();
            }
            foreach (['name', 'unp', 'address', 'phone', 'email', 'bank', 'bic', 'account'] as $f) {
                if (isset($company[$f])) {
                    $cs->$f = $company[$f];
                }
            }
            $cs->updated_at = time();
            $cs->save(false);
            return ['success' => true, 'message' => 'Реквизиты компании сохранены'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Управление статусами заказов
     */
    public function actionStatuses()
    {
        $statuses = OrderStatus::find()->orderBy(['sort' => SORT_ASC])->asArray()->all();

        return $this->render('statuses', [
            'statuses' => $statuses,
        ]);
    }

    /**
     * Сохранение статусов (AJAX POST JSON)
     */
    public function beforeAction($action)
    {
        // CSRF отключён для JSON AJAX-эндпоинтов настроек, которые принимают тело запроса
        // в формате application/json (getRawBody). Стандартный CSRF-токен Yii2 не передаётся
        // при fetch(..., { body: JSON.stringify(...) }) без явной передачи заголовка.
        // Безопасность обеспечивается авторизацией на уровне BaseAdminController (только admin).
        // TODO: передавать X-CSRF-Token в заголовках fetch-запросов и убрать это исключение.
        if (in_array($action->id, ['save-statuses', 'save-payment', 'save-shipping'])) {
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    public function actionSaveStatuses()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true) ?? [];

        if (empty($data) || !isset($data['statuses']) || !is_array($data['statuses'])) {
            return ['success' => false, 'message' => 'Нет данных для сохранения'];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $existingKeys = [];
            foreach ($data['statuses'] as $i => $item) {
                $key = $item['key'] ?? '';
                $label = $item['label'] ?? '';
                $color = $item['color'] ?? 'secondary';
                $isActive = !empty($item['is_active']) ? 1 : 0;
                $logistAvailable = !empty($item['logist_available']) ? 1 : 0;
                $sort = $i;

                if (empty($key) || empty($label)) {
                    continue;
                }

                $status = OrderStatus::findOne(['key' => $key]);
                if ($status) {
                    $status->label = $label;
                    $status->color = $color;
                    $status->is_active = $isActive;
                    $status->logist_available = $logistAvailable;
                    $status->sort = $sort;
                    $status->save(false);
                } else {
                    $status = new OrderStatus();
                    $status->key = $key;
                    $status->label = $label;
                    $status->color = $color;
                    $status->is_active = $isActive;
                    $status->logist_available = $logistAvailable;
                    $status->sort = $sort;
                    $status->save(false);
                }
                $existingKeys[] = $key;
            }

            // Удаляем статусы, которых нет в пришедших данных (кроме системных)
            $systemKeys = ['new', 'paid', 'canceled'];
            $allStatuses = OrderStatus::find()->all();
            foreach ($allStatuses as $s) {
                if (!in_array($s->key, $existingKeys) && !in_array($s->key, $systemKeys)) {
                    $s->delete();
                }
            }

            $transaction->commit();

            // Сбрасываем кэш статусов
            Yii::$app->settings->resetStatusesCache();

            return ['success' => true, 'message' => 'Статусы сохранены'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Email шаблоны — страница редактирования
     */
    public function actionEmailTemplates()
    {
        $events = ['confirmed', 'paid', 'ordered_poizon', 'at_warehouse', 'shipped_local', 'completed'];
        $templates = [];
        foreach ($events as $key) {
            $templates[$key] = [
                'subject' => Yii::$app->settings->get('email_template_' . $key, 'subject', ''),
                'body'    => Yii::$app->settings->get('email_template_' . $key, 'body', ''),
            ];
        }
        return $this->render('email-templates', ['templates' => $templates]);
    }

    /**
     * Сохранение одного email шаблона (AJAX POST JSON)
     */
    public function actionSaveEmailTemplate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true) ?? [];

        $key     = $data['key'] ?? '';
        $subject = $data['subject'] ?? '';
        $body    = $data['body'] ?? '';

        $allowed = ['confirmed', 'paid', 'ordered_poizon', 'at_warehouse', 'shipped_local', 'completed'];
        if (!in_array($key, $allowed, true)) {
            return ['success' => false, 'message' => 'Неверный ключ шаблона'];
        }

        try {
            Yii::$app->settings->set('email_template_' . $key, 'subject', $subject);
            Yii::$app->settings->set('email_template_' . $key, 'body', $body);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Тест email — отправляет тестовое письмо на email текущего администратора
     */
    public function actionTestEmail()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $raw  = Yii::$app->request->getRawBody();
        $data = json_decode($raw, true) ?? [];
        $key  = $data['key'] ?? '';

        $identity = Yii::$app->user->identity;
        $toEmail  = $identity ? ($identity->email ?? null) : null;

        if (!$toEmail) {
            return ['success' => false, 'message' => 'Не удалось определить email текущего пользователя'];
        }

        $subject = Yii::$app->settings->get('email_template_' . $key, 'subject', 'Тест: ' . $key) ?: ('Тест шаблона: ' . $key);
        $body    = Yii::$app->settings->get('email_template_' . $key, 'body', '') ?: '<p>Тестовое письмо для шаблона <b>' . htmlspecialchars($key) . '</b></p>';

        try {
            Yii::$app->mailer->compose()
                ->setFrom([Yii::$app->params['senderEmail'] ?? 'noreply@example.com' => Yii::$app->params['senderName'] ?? 'Admin'])
                ->setTo($toEmail)
                ->setSubject('[ТЕСТ] ' . $subject)
                ->setHtmlBody($body)
                ->send();
            return ['success' => true, 'message' => 'Тестовое письмо отправлено на ' . $toEmail];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка отправки: ' . $e->getMessage()];
        }
    }

    /**
     * Тест Telegram — отправляет тестовое сообщение в настроенные чаты.
     */
    public function actionTestTelegram()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $token = Yii::$app->settings->get('telegram', 'bot_token', '');
        if (empty($token)) {
            return ['success' => false, 'message' => 'Bot Token не настроен. Сохраните настройки перед тестом.'];
        }

        try {
            $result = TelegramService::sendTest();
            if ($result) {
                return ['success' => true, 'message' => 'Тестовое сообщение отправлено успешно'];
            }
            return ['success' => false, 'message' => 'Не удалось отправить сообщение. Проверьте token и chat_ids.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Тест подключения к МойСклад
     */
    public function actionTestMoysklad()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $apiKey = Yii::$app->settings->get('moysklad', 'api_key', '');
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API-ключ МойСклад не настроен.'];
        }

        try {
            $url = 'https://api.moysklad.ru/api/remap/1.2/context/employee';
            $ch  = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                $data = json_decode($body, true);
                $name = $data['name'] ?? 'аккаунт';
                return ['success' => true, 'message' => 'Подключение успешно. Аккаунт: ' . $name];
            }
            return ['success' => false, 'message' => 'Ошибка авторизации МойСклад (HTTP ' . $code . '). Проверьте API-ключ.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Тест подключения к AmoCRM
     */
    public function actionTestAmocrm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $domain   = Yii::$app->settings->get('amocrm', 'domain', '');
        $apiToken = Yii::$app->settings->get('amocrm', 'api_token', '');

        if (empty($domain) || empty($apiToken)) {
            return ['success' => false, 'message' => 'Домен и API Token AmoCRM не настроены.'];
        }

        try {
            $domain = rtrim(preg_replace('#^https?://#', '', $domain), '/');
            $url    = "https://{$domain}/api/v4/account";
            $ch     = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiToken,
                'Accept: application/json',
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200) {
                $data = json_decode($body, true);
                $name = $data['name'] ?? $domain;
                return ['success' => true, 'message' => 'Подключение успешно. Аккаунт: ' . $name];
            }
            return ['success' => false, 'message' => 'Ошибка авторизации AmoCRM (HTTP ' . $code . '). Проверьте токен и домен.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /**
     * Настройки служб доставки
     */
    /**
     * Страница управления способами оплаты
     */
    public function actionPayment()
    {
        $methods = $this->getPaymentMethods();

        return $this->render('payment', [
            'methods' => $methods,
        ]);
    }

    /**
     * Сохранение способов оплаты (AJAX POST JSON)
     */
    public function actionSavePayment()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $raw = json_decode(Yii::$app->request->getRawBody(), true);
        if (!isset($raw['methods']) || !is_array($raw['methods'])) {
            return ['success' => false, 'message' => 'Некорректные данные'];
        }

        $clean = [];
        foreach ($raw['methods'] as $m) {
            if (empty($m['id']) || empty($m['name'])) continue;
            $clean[] = [
                'id'          => preg_replace('/[^a-z0-9_]/', '', strtolower($m['id'])),
                'name'        => mb_substr(strip_tags($m['name']), 0, 80),
                'description' => mb_substr(strip_tags($m['description'] ?? ''), 0, 200),
                'icon'        => preg_replace('/[^a-z0-9\-]/', '', $m['icon'] ?? 'credit-card'),
                'status'      => ($m['status'] ?? '') === 'active' ? 'active' : 'inactive',
                'sort_order'  => (int)($m['sort_order'] ?? 0),
            ];
        }
        // Re-number sort_order sequentially to avoid gaps
        usort($clean, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        foreach ($clean as $i => &$item) { $item['sort_order'] = $i + 1; }
        unset($item);

        Yii::$app->settings->set('checkout', 'payment_methods', json_encode($clean, JSON_UNESCAPED_UNICODE));

        return ['success' => true, 'saved' => count($clean)];
    }

    /**
     * Получить методы оплаты из DB. Возвращает fallback значения по умолчанию, если не настроены.
     */
    public function getPaymentMethods(): array
    {
        $stored = Yii::$app->settings->get('checkout', 'payment_methods');
        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fallback значения по умолчанию
        return [
            [
                'id' => 'cash',
                'name' => 'Наличными при получении',
                'description' => 'Оплата наличными при получении заказа',
                'icon' => 'cash',
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'id' => 'card',
                'name' => 'Банковской картой',
                'description' => 'Visa, MasterCard, БелКарт',
                'icon' => 'credit-card',
                'status' => 'active',
                'sort_order' => 2,
            ],
        ];
    }

    /**
     * SEO настройки
     */
    public function actionSeo()
    {
        if (Yii::$app->request->isPost) {
            $fields = ['meta_title_tpl', 'meta_desc_tpl', 'ga_id', 'metrika_id', 'robots_txt', 'sitemap_enabled'];
            foreach ($fields as $field) {
                $value = Yii::$app->request->post($field, '');
                Yii::$app->settings->set('seo', $field, $value);
            }
            Yii::$app->session->addFlash('success', 'SEO настройки сохранены');
            return $this->redirect(['/admin/settings/seo']);
        }

        // W17: clear flash set by other actions (e.g., product image deletion)
        // that would otherwise appear on an unrelated page
        $flash = Yii::$app->session->getFlash('success', null, true);
        if ($flash && strpos((string)$flash, 'SEO') === false) {
            $flash = null;
        }

        $s = Yii::$app->settings;
        return $this->render('seo', [
            'seo_flash'       => $flash,
            'meta_title_tpl'  => $s->get('seo', 'meta_title_tpl', '{page_title} — {site_name}'),
            'meta_desc_tpl'   => $s->get('seo', 'meta_desc_tpl', ''),
            'ga_id'           => $s->get('seo', 'ga_id', ''),
            'metrika_id'      => $s->get('seo', 'metrika_id', ''),
            'robots_txt'      => $s->get('seo', 'robots_txt', "User-agent: *\nAllow: /"),
            'sitemap_enabled' => $s->get('seo', 'sitemap_enabled', '1'),
        ]);
    }

    public function actionSources()
    {
        $defaultSources = ['Сайт', 'Telegram', 'Instagram', 'ВКонтакте', 'Звонок', 'WhatsApp', 'Viber', 'Рекомендация', 'Другое'];

        if (Yii::$app->request->isPost) {
            $raw  = Yii::$app->request->getRawBody();
            $body = json_decode($raw, true);

            if (isset($body['sources'])) {
                $sources = array_values(array_filter(array_map('trim', (array)$body['sources'])));
                Yii::$app->settings->set('order', 'sources', json_encode($sources, JSON_UNESCAPED_UNICODE));
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => true];
            }
        }

        $saved = Yii::$app->settings->get('order', 'sources', '');
        $sources = $saved ? json_decode($saved, true) : $defaultSources;

        return $this->render('sources', [
            'sources'        => $sources ?: $defaultSources,
            'defaultSources' => $defaultSources,
        ]);
    }

    public function actionShipping()
    {
        $shippingMethods = $this->getShippingMethods();

        return $this->render('shipping', [
            'methods' => $shippingMethods,
        ]);
    }

    /**
     * Сохранение настроек доставки (AJAX POST JSON)
     */
    public function actionSaveShipping()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $raw = json_decode(Yii::$app->request->getRawBody(), true);
        if (!isset($raw['shipping']['methods']) || !is_array($raw['shipping']['methods'])) {
            return ['success' => false, 'message' => 'Некорректные данные'];
        }

        $clean = [];
        foreach ($raw['shipping']['methods'] as $m) {
            if (empty($m['id']) || empty($m['name'])) continue;
            $clean[] = [
                'id' => preg_replace('/[^a-z0-9_]/', '', strtolower($m['id'])),
                'name' => mb_substr(strip_tags($m['name']), 0, 80),
                'type' => mb_substr(strip_tags($m['type'] ?? 'international'), 0, 20),
                'carrier' => mb_substr(strip_tags($m['carrier'] ?? ''), 0, 50),
                'plugin' => preg_replace('/[^a-z0-9_]/', '', strtolower($m['plugin'] ?? '')),
                'status' => ($m['status'] ?? '') === 'active' ? 'active' : 'inactive',
                'delivery_time' => mb_substr(strip_tags($m['delivery_time'] ?? ''), 0, 50),
                'base_cost' => (float)($m['base_cost'] ?? 0),
                'currency' => mb_substr(strip_tags($m['currency'] ?? 'BYN'), 0, 10),
                'description' => mb_substr(strip_tags($m['description'] ?? ''), 0, 200),
            ];
        }

        Yii::$app->settings->set('checkout', 'shipping_methods', json_encode($clean, JSON_UNESCAPED_UNICODE));

        return ['success' => true];
    }

    /**
     * Получить все методы доставки
     */
    private function getShippingMethods()
    {
        $stored = Yii::$app->settings->get('checkout', 'shipping_methods');
        if ($stored) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Fallback значения по умолчанию
        return [
            [
                'id' => 'international_express',
                'name' => 'Международная экспресс',
                'type' => 'international',
                'type_label' => 'Международная',
                'carrier' => 'DHL',
                'status' => 'active',
                'delivery_time' => '3-5 дней',
                'base_cost' => 45.00,
                'currency' => 'BYN',
                'description' => 'Быстрая международная доставка',
            ],
            [
                'id' => 'international_standard',
                'name' => 'Международная стандарт',
                'type' => 'international',
                'type_label' => 'Международная',
                'carrier' => 'EMS',
                'status' => 'active',
                'delivery_time' => '7-14 дней',
                'base_cost' => 25.00,
                'currency' => 'BYN',
                'description' => 'Экономичная международная доставка',
            ],
            [
                'id' => 'local_courier',
                'name' => 'Курьер по городу',
                'type' => 'local',
                'type_label' => 'Внутри страны',
                'carrier' => 'Внутренний',
                'status' => 'active',
                'delivery_time' => '1-2 дня',
                'base_cost' => 8.00,
                'currency' => 'BYN',
                'description' => 'Доставка курьером по городу',
            ],
            [
                'id' => 'local_pickup',
                'name' => 'Самовывоз',
                'type' => 'local',
                'type_label' => 'Внутри страны',
                'carrier' => 'Пункт выдачи',
                'status' => 'active',
                'delivery_time' => 'Сегодня',
                'base_cost' => 0.00,
                'currency' => 'BYN',
                'description' => 'Бесплатный самовывоз из пункта',
            ],
            [
                'id' => 'local_post',
                'name' => 'Почта',
                'type' => 'local',
                'type_label' => 'Внутри страны',
                'carrier' => 'Белпочта',
                'status' => 'active',
                'delivery_time' => '3-7 дней',
                'base_cost' => 5.00,
                'currency' => 'BYN',
                'description' => 'Доставка почтой по стране',
            ],
        ];
    }
}
