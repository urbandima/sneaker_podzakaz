<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\shared\services\TelegramService;

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

        $fields = ['name', 'unp', 'address', 'phone', 'email', 'work_time', 'bank_details'];
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
            // Also set individual keys for backward compat
            foreach ($company as $k => $v) {
                Yii::$app->settings->set('company', $k, $v);
            }
            return ['success' => true, 'message' => 'Реквизиты компании сохранены'];
        } catch (\Exception $e) {
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
            $url = 'https://online.moysklad.ru/api/remap/1.2/context/employee';
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
}
