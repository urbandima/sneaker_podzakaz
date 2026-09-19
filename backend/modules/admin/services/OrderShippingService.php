<?php

namespace app\backend\modules\admin\services;

use Yii;
use app\backend\modules\checkout\models\Order;

/**
 * OrderShippingService — логистика заказа: статус логистики, назначение логиста,
 * поиск ПВЗ Европочты, трекинг отправлений, интеграция с Таможня:ДП.
 *
 * Извлечено из OrderController (CMP-392, план CMP-371 п.3.2). Не работает с
 * платёжными данными заказа — по решению CEO (CMP-392, 18.09) может мёржиться
 * в main на общих основаниях, без ожидания security review.
 */
class OrderShippingService
{
    public const ALLOWED_LOGISTICS_STATUSES = [
        Order::LOGISTICS_AWAITING_BUYOUT,
        Order::LOGISTICS_BOUGHT_AT_SOURCE,
        Order::LOGISTICS_IN_TRANSIT,
        Order::LOGISTICS_AT_WAREHOUSE,
    ];

    public static function isAllowedLogisticsStatus(string $status): bool
    {
        return in_array($status, self::ALLOWED_LOGISTICS_STATUSES, true);
    }

    public function setLogisticsStatus(Order $order, string $newStatus): bool
    {
        if (!self::isAllowedLogisticsStatus($newStatus)) {
            return false;
        }

        $order->logistics_status = $newStatus;
        return $order->save(false);
    }

    public function assignLogist(Order $order, $logistId): bool
    {
        $order->assigned_logist = $logistId ?: null;
        return $order->save(false);
    }

    public function bulkAssignLogist(array $ids, $logistId): int
    {
        return Order::updateAll(['assigned_logist' => $logistId ?: null], ['id' => $ids]);
    }

    /**
     * Фильтрация списка ПВЗ по поисковой строке. Чистая функция — не трогает
     * settings/DB, поэтому покрывается unit-тестом напрямую.
     */
    public static function filterPvzList(array $list, string $query, int $limit = 60): array
    {
        $q = mb_strtolower(trim($query));
        $results = [];

        foreach ($list as $pvz) {
            if ($q !== '') {
                $haystack = mb_strtolower(
                    ($pvz['city'] ?? '') . ' '
                    . ($pvz['name'] ?? '') . ' '
                    . ($pvz['full'] ?? '') . ' '
                    . ($pvz['num']  ?? '')
                );
                if (mb_strpos($haystack, $q) === false) {
                    continue;
                }
            }

            $results[] = [
                'id'       => $pvz['id'] ?? ($pvz['num'] ?? ''),
                'num'      => $pvz['num'] ?? '',
                'city'     => $pvz['city'] ?? '',
                'address'  => $pvz['name'] ?? '',
                'full'     => $pvz['full'] ?? '',
                'schedule' => $pvz['schedule'] ?? ($pvz['work_time'] ?? ''),
            ];
            if (count($results) >= $limit) {
                break;
            }
        }

        // При пустом запросе — группируем по городу для удобства
        if ($q === '' && !empty($results)) {
            usort($results, function ($a, $b) {
                $c = strcmp($a['city'] ?? '', $b['city'] ?? '');
                if ($c !== 0) {
                    return $c;
                }
                return strcmp($a['num'] ?? '', $b['num'] ?? '');
            });
        }

        return $results;
    }

    /**
     * Поиск пунктов выдачи Европочты (PVZ) — для автокомплита в админке заказа.
     * Источник данных: app_setting[section='shipping', key='europochta_points'] — JSON массив.
     */
    public function searchPvz(string $query, int $limit = 60): array
    {
        $limit = max(1, min(500, $limit));

        $raw  = Yii::$app->settings->get('shipping', 'europochta_points', '');
        $list = $raw ? (json_decode($raw, true) ?: []) : [];
        if (!is_array($list) || empty($list)) {
            return [];
        }

        return self::filterPvzList($list, $query, $limit);
    }

    /**
     * Форматирование ответа службы трекинга в текст для UI. Чистая функция.
     */
    public static function formatTrackStatusText(array $result): string
    {
        $statusName = $result['status_name'] ?? $result['message'] ?? $result['status'] ?? 'Нет данных';
        $date       = $result['status_date'] ?? null;
        $location   = $result['location'] ?? null;

        $text = $statusName;
        if ($date) {
            $ts = is_numeric($date) ? (int)$date : strtotime($date);
            if ($ts) {
                $text .= ' (' . date('d.m.Y', $ts) . ')';
            }
        }
        if ($location) {
            $text .= ' — ' . $location;
        }

        return $text;
    }

    public function checkTrack(string $track, ?Order $order): array
    {
        if ($track === '') {
            return ['success' => false, 'status' => 'Трек не указан'];
        }

        $deliveryMethod = $order ? strtolower($order->delivery_method ?? '') : '';

        try {
            $result = $this->callTrackingService($track, $deliveryMethod);
        } catch (\Exception $e) {
            Yii::warning('Track check error: ' . $e->getMessage(), 'tracking');
            return ['success' => false, 'status' => 'Ошибка: ' . $e->getMessage()];
        }

        return ['success' => true, 'status' => self::formatTrackStatusText($result), 'raw' => $result];
    }

    /** Подбор и вызов нужной службы трекинга */
    private function callTrackingService(string $track, string $deliveryMethod): array
    {
        switch ($deliveryMethod) {
            case 'europochta':
                if (Yii::$app->has('europochtaTracking')) {
                    return Yii::$app->europochtaTracking->getStatus($track);
                }
                break;
            case 'belpochta':
                if (Yii::$app->has('belpochtaTracking')) {
                    return Yii::$app->belpochtaTracking->getStatus($track);
                }
                break;
            case 'cdek':
            case 'sdek':
                if (Yii::$app->has('cdekTracking')) {
                    return Yii::$app->cdekTracking->getStatus($track);
                }
                break;
        }

        // Нет конкретного провайдера — пробуем каждый настроенный по очереди
        foreach (['europochtaTracking' => 'europochta', 'belpochtaTracking' => 'belpochta', 'cdekTracking' => 'cdek'] as $component => $name) {
            if (!Yii::$app->has($component)) {
                continue;
            }
            $svc = Yii::$app->$component;
            if (!$svc->isConfigured()) {
                continue;
            }
            $result = $svc->getStatus($track);
            if (!in_array($result['status'] ?? '', ['not_found', 'not_configured', 'error'])) {
                return $result;
            }
        }

        return ['status' => 'not_found', 'message' => 'Отправление не найдено. Проверьте на сайте перевозчика.'];
    }

    /** Вручную отправить заказ в Таможня:ДП */
    public function sendToDp(Order $order): array
    {
        if ($order->isSubmittedToDP()) {
            return ['success' => false, 'message' => 'Заказ уже отправлен в Таможня:ДП (шипмент #' . $order->dp_shipment_id . ')'];
        }

        $missing = $order->missingDpFields();
        if (!empty($missing)) {
            return [
                'success' => false,
                'message' => 'Не заполнены обязательные поля: ' . implode(', ', $missing),
                'missing_fields' => $missing,
            ];
        }

        try {
            /** @var \app\backend\modules\checkout\services\DobroPostService $dp */
            $dp = Yii::$app->dobropost;
            $response = $dp->createShipment($order);

            return [
                'success'      => true,
                'message'      => 'Заказ успешно отправлен в Таможня:ДП',
                'shipment_id'  => $response['id'] ?? null,
                'track_number' => $response['dptrackNumber'] ?? null,
            ];
        } catch (\Exception $e) {
            Yii::error('Исключение при отправке заказа #' . $order->id . ' в Таможня:ДП: ' . $e->getMessage(), 'dp-api');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /** Актуализировать статус шипмента из Таможня:ДП */
    public function refreshDpStatus(Order $order): array
    {
        if (!$order->isSubmittedToDP()) {
            return ['success' => false, 'message' => 'Заказ не отправлен в Таможня:ДП'];
        }

        try {
            /** @var \app\backend\modules\checkout\services\DobroPostService $dp */
            $dp = Yii::$app->dobropost;

            $result  = $dp->getShipments(['statusId' => null]);

            // Поддерживаем оба формата ответа: объект (одна запись) и массив (список)
            $shipment = null;
            if (isset($result['id']) && $result['id'] == $order->dp_shipment_id) {
                $shipment = $result;
            } elseif (is_array($result)) {
                foreach ($result as $s) {
                    if (isset($s['id']) && $s['id'] == $order->dp_shipment_id) {
                        $shipment = $s;
                        break;
                    }
                }
            }

            if ($shipment) {
                $dp->handleCreateResponse($order, $shipment);
                $statusName = $shipment['status']['name'] ?? $order->dp_status;
                return ['success' => true, 'message' => 'Статус обновлён: ' . $statusName, 'status' => $statusName];
            }

            return ['success' => true, 'message' => 'Шипмент не найден в ответе API', 'status' => $order->dp_status];
        } catch (\Exception $e) {
            Yii::error('Ошибка обновления статуса ДП заказа #' . $order->id . ': ' . $e->getMessage(), 'dp-api');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }

    /** Повторить отправку в Таможня:ДП (сброс и пересоздание шипмента) */
    public function retryDp(Order $order): array
    {
        $missing = $order->missingDpFields();
        if (!empty($missing)) {
            return [
                'success' => false,
                'message' => 'Не заполнены обязательные поля: ' . implode(', ', $missing),
                'missing_fields' => $missing,
            ];
        }

        try {
            $order->dp_shipment_id  = null;
            $order->dp_track_number = null;
            $order->dp_status       = null;
            $order->dp_status_date  = null;
            $order->dp_sent_at      = null;
            $order->dp_response     = null;
            $order->save(false);

            /** @var \app\backend\modules\checkout\services\DobroPostService $dp */
            $dp       = Yii::$app->dobropost;
            $response = $dp->createShipment($order);

            Yii::info('Повторная отправка заказа #' . $order->id . ' в Таможня:ДП успешна, шипмент #' . ($response['id'] ?? '-'), 'dp-api');
            return [
                'success'      => true,
                'message'      => 'Повторная отправка выполнена успешно',
                'shipment_id'  => $response['id'] ?? null,
                'track_number' => $response['dptrackNumber'] ?? null,
            ];
        } catch (\Exception $e) {
            Yii::error('Ошибка повторной отправки заказа #' . $order->id . ' в Таможня:ДП: ' . $e->getMessage(), 'dp-api');
            return ['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()];
        }
    }
}
