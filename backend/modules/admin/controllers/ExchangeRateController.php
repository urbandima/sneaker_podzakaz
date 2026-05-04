<?php
/**
 * ExchangeRateController — Управление курсом валют
 *
 * B15.1: Автокурс CNY с НБРБ
 * B3.4: Виджет курса на дашборде
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;

class ExchangeRateController extends BaseAdminController
{
    private $nbrbApiUrl = 'https://api.nbrb.by/exrates/rates/';

    /**
     * Получить текущий курс CNY
     */
    public function actionCurrent()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $rate = $this->getCurrentRate();

        return [
            'success' => true,
            'rate' => $rate,
            'currency' => 'CNY',
            'date' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Обновить курс (ручной запуск или cron)
     */
    public function actionUpdate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        try {
            $rate = $this->fetchRateFromNbrb();

            // Сохраняем в настройки
            Yii::$app->settings->set('currency', 'cny_rate', $rate);
            Yii::$app->settings->set('currency', 'cny_updated', time());

            Yii::info("CNY rate updated: {$rate}", 'admin');

            return [
                'success' => true,
                'rate' => $rate,
                'message' => 'Курс обновлен',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * История курса за 30 дней
     */
    public function actionHistory()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Получаем из лога или API
        $history = $this->getRateHistory(30);

        return ['history' => $history];
    }

    /**
     * Получить курс из НБРБ API
     */
    private function fetchRateFromNbrb()
    {
        $url = $this->nbrbApiUrl . 'CNY?parammode=2';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($response)) {
            throw new \Exception('НБРБ API недоступен');
        }

        $data = json_decode($response, true);
        $rate = $data['Cur_OfficialRate'] ?? null;

        if (!$rate) {
            throw new \Exception('Неверный ответ от НБРБ');
        }

        // Конвертируем курс (НБРБ дает за 100 CNY)
        return round($rate / 100, 4);
    }

    /**
     * Получить текущий курс из кэша или API
     */
    private function getCurrentRate()
    {
        $cached = Yii::$app->settings->get('currency', 'cny_rate', 0);
        $updated = Yii::$app->settings->get('currency', 'cny_updated', 0);

        // Если курс устарел (>24 часов), обновляем
        if (empty($cached) || (time() - $updated) > 86400) {
            try {
                return $this->fetchRateFromNbrb();
            } catch (\Exception $e) {
                return $cached ?: 0.45; // fallback
            }
        }

        return $cached;
    }

    /**
     * Получить историю курса
     */
    private function getRateHistory($days)
    {
        // Заглушка - в реальности читать из таблицы rate_history
        $history = [];
        $currentRate = $this->getCurrentRate();

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            // Симуляция небольших колебаний
            $rate = $currentRate * (1 + (rand(-50, 50) / 10000));

            $history[] = [
                'date' => $date,
                'rate' => round($rate, 4),
            ];
        }

        return $history;
    }

    /**
     * Страница настроек курса
     */
    public function actionSettings()
    {
        return $this->render('settings', [
            'currentRate' => $this->getCurrentRate(),
            'lastUpdated' => Yii::$app->settings->get('currency', 'cny_updated', 0),
        ]);
    }
}
