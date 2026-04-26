<?php

namespace app\backend\modules\checkout\services;

use Yii;
use yii\base\Component;
use app\backend\modules\checkout\models\Order;

/**
 * DobroPostService — интеграция с API Таможня:ДП
 *
 * Конфигурация через .env:
 *   DP_API_URL        — базовый URL API (по умолчанию https://api.dobropost.com)
 *   DP_API_EMAIL      — email учётной записи
 *   DP_API_PASSWORD   — пароль учётной записи
 *   DP_DEFAULT_TARIFF — ID тарифа по умолчанию (по умолчанию 26)
 *
 * Регистрация в web.php:
 *   'dobropost' => ['class' => DobroPostService::class]
 */
class DobroPostService extends Component
{
    /** @var string Базовый URL API */
    public string $apiUrl = '';

    /** @var string Email для авторизации */
    public string $email = '';

    /** @var string Пароль для авторизации */
    public string $password = '';

    /** @var int Тариф Таможня:ДП по умолчанию */
    public int $defaultTariff = 26;

    /** @var int Количество попыток при ошибке сети */
    private int $retryAttempts = 3;

    /** @var int Задержка между попытками в секундах */
    private int $retryDelay = 2;

    /** @var string Ключ кеша токена */
    private string $tokenCacheKey = 'dobropost_auth_token';

    /** @var int Время жизни токена в кеше (11 часов; реальный TTL токена — 12ч) */
    private int $tokenCacheDuration = 39600;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function init(): void
    {
        parent::init();

        if (empty($this->apiUrl)) {
            $this->apiUrl = env('DP_API_URL', 'https://api.dobropost.com');
        }
        if (empty($this->email)) {
            $this->email = (string) env('DP_API_EMAIL', '');
        }
        if (empty($this->password)) {
            $this->password = (string) env('DP_API_PASSWORD', '');
        }
        $envTariff = env('DP_DEFAULT_TARIFF', '');
        if ($envTariff !== '') {
            $this->defaultTariff = (int) $envTariff;
        }
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    /**
     * Возвращает Bearer-токен. Результат кешируется на 11 часов.
     *
     * @throws \RuntimeException если авторизация не удалась
     */
    public function authenticate(): string
    {
        $cached = Yii::$app->cache->get($this->tokenCacheKey);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->request('POST', '/api/shipment/sign-in', [
            'email'    => $this->email,
            'password' => $this->password,
        ], false);

        if (empty($response['token'])) {
            throw new \RuntimeException('Таможня:ДП: не удалось получить токен авторизации. Ответ: ' . json_encode($response));
        }

        $token = $response['token'];
        Yii::$app->cache->set($this->tokenCacheKey, $token, $this->tokenCacheDuration);

        Yii::info('Таможня:ДП: авторизация прошла успешно, токен закеширован на ' . ($this->tokenCacheDuration / 3600) . ' ч.', 'dp-api');

        return $token;
    }

    /**
     * Сбрасывает закешированный токен (при ошибке 401).
     */
    private function invalidateToken(): void
    {
        Yii::$app->cache->delete($this->tokenCacheKey);
    }

    // -------------------------------------------------------------------------
    // Shipment CRUD
    // -------------------------------------------------------------------------

    /**
     * Создаёт шипмент в Таможня:ДП и обновляет поля заказа.
     *
     * @throws \RuntimeException при ошибке API
     */
    public function createShipment(Order $order): array
    {
        $payload  = $this->buildShipmentPayload($order);
        $response = $this->authorizedRequest('POST', '/api/shipment', $payload);

        $this->handleCreateResponse($order, $response);

        Yii::info(
            sprintf('Таможня:ДП: создан шипмент #%s для заказа #%d, DP ID: %s, трек: %s',
                $order->order_number, $order->id,
                $response['id'] ?? '-', $response['dptrackNumber'] ?? '-'
            ),
            'dp-api'
        );

        if (Yii::$app->has('automation')) {
            Yii::$app->automation->fireEvent('order.sent_to_dp', [
                'order'       => $order,
                'dp_response' => $response,
            ]);
        }

        return $response;
    }

    /**
     * Формирует payload для создания/обновления шипмента из полей заказа.
     */
    /**
     * Возвращает случайный телефон из справочника proxy_phones (настройки ДП).
     * Каждый элемент — объект {phone, label}. Если список пуст — возвращает дефолт.
     */
    public function getRandomPhone(): string
    {
        try {
            $raw   = Yii::$app->settings->get('dobropost', 'proxy_phones', '[]');
            $items = json_decode($raw, true);
            if (!empty($items) && is_array($items)) {
                $item = $items[array_rand($items)];
                return is_array($item) ? ($item['phone'] ?? '') : (string) $item;
            }
        } catch (\Throwable $e) {
            Yii::warning('proxy_phones setting unavailable: ' . $e->getMessage(), 'dobropost');
        }
        return '+375447009001'; // дефолт если справочник пуст
    }

    public function buildShipmentPayload(Order $order): array
    {
        // Телефон — случайный из справочника proxy_phones (НЕ реальный телефон клиента)
        $phone = $this->getRandomPhone();

        // Email всегда хардкодим, реальный email клиента не передаём в DP
        $email = '1@mail.ru';

        // Тариф: из поля dobropost_tariff заказа, иначе — дефолтный
        $tariff = !empty($order->dobropost_tariff) ? (int) $order->dobropost_tariff : $this->defaultTariff;

        // Описание товара: не более 60 символов
        $description = mb_substr($order->customs_description ?: 'Одежда и обувь', 0, 59);

        // Ссылка на товар
        $frontendBase = rtrim(\Yii::$app->params['frontendBaseUrl'] ?? \Yii::$app->params['frontendUrl'] ?? 'https://sneakerhead.by', '/');
        $storeLink = $order->product_link ?: $order->sneakerhead_order_link ?: $frontendBase;

        // Количество единиц товара
        $qty = max(1, (int) ($order->item_quantity ?: 1));

        // Стоимость товара в юанях
        $itemPriceCny = (float) ($order->item_price_cny ?: 0);

        // Общая стоимость шипмента в юанях
        $totalAmount = (float) ($order->shipment_value_cny ?: $itemPriceCny * $qty);

        // Комментарий (не более 60 символов)
        $comment = mb_substr($order->ms_number ? 'МС: ' . $order->ms_number : '', 0, 59);

        $payload = [
            'totalAmount'             => $totalAmount,
            'consigneeFamilyName'     => $order->recipient_last_name,
            'consigneeName'           => $order->recipient_first_name,
            'consigneePassportSerial' => $order->passport_series,
            'consigneePassportNumber' => $order->passport_number,
            'passportIssueDate'       => $order->passport_issue_date
                ? date('Y-m-d', is_numeric($order->passport_issue_date)
                    ? $order->passport_issue_date
                    : strtotime($order->passport_issue_date))
                : null,
            'vatIdentificationNumber' => $order->inn,
            'consigneeFullAddress'    => $order->full_address,
            'consigneeCity'           => $order->city,
            'consigneeState'          => $order->region,
            'consigneeZipCode'        => $order->postal_code,
            'consigneePhoneNumber'    => $phone,
            'consigneeEmail'          => $email,
            'itemDescription'         => $description,
            'numberOfItemPieces'      => $qty,
            'itemPrice'               => $itemPriceCny,
            'itemStoreLink'           => $storeLink,
            'dpTariffId'              => $tariff,
            'incomingDeclaration'     => $order->china_track_number,
        ];

        // Необязательные поля
        if (!empty($order->recipient_middle_name)) {
            $payload['consigneeMiddleName'] = $order->recipient_middle_name;
        }
        if (!empty($order->birth_date)) {
            $payload['consigneeBirthDate'] = date('Y-m-d', is_numeric($order->birth_date)
                ? $order->birth_date
                : strtotime($order->birth_date));
        }
        if (!empty($comment)) {
            $payload['comment'] = $comment;
        }

        return $payload;
    }

    /**
     * Сохраняет данные из ответа API в поля заказа.
     */
    public function handleCreateResponse(Order $order, array $response): void
    {
        $order->dp_shipment_id  = $response['id']            ?? null;
        $order->dp_track_number = $response['dptrackNumber'] ?? null;
        $order->dp_status       = isset($response['status']['id'])
            ? (string) $response['status']['id']
            : null;
        $order->dp_status_date  = isset($response['statusDate'])
            ? date('Y-m-d H:i:s', strtotime($response['statusDate']))
            : null;
        $order->dp_sent_at      = time();
        $order->dp_response     = $response;

        if (!$order->save(false)) {
            Yii::warning(
                'Таможня:ДП: не удалось сохранить DP-поля заказа #' . $order->id . ': ' . json_encode($order->errors),
                'dp-api'
            );
        }
    }

    /**
     * Возвращает список шипментов.
     *
     * @param array $params ['page' => int, 'offset' => int, 'statusId' => int]
     */
    public function getShipments(array $params = []): array
    {
        $query = http_build_query(array_filter($params, fn($v) => $v !== null && $v !== ''));
        $path  = '/api/shipment' . ($query ? '?' . $query : '');

        return $this->authorizedRequest('GET', $path);
    }

    /**
     * Обновляет шипмент.
     *
     * @param array $payload Поля для обновления (та же структура, что у createShipment)
     */
    public function updateShipment(array $payload): array
    {
        return $this->authorizedRequest('PUT', '/api/shipment', $payload);
    }

    /**
     * Удаляет шипмент по ID.
     */
    public function deleteShipment(int $shipmentId): bool
    {
        $this->authorizedRequest('DELETE', '/api/shipment/' . $shipmentId);
        Yii::info('Таможня:ДП: удалён шипмент #' . $shipmentId, 'dp-api');
        return true;
    }

    /**
     * Health-check: authenticate and fetch a single shipment list page.
     * Returns ['ok' => true, 'latency_ms' => int] on success.
     */
    public function ping(): array
    {
        $t0 = microtime(true);
        try {
            $this->authenticate();
            $this->authorizedRequest('GET', '/api/shipment?limit=1&offset=0');
            return ['ok' => true, 'latency_ms' => (int)((microtime(true) - $t0) * 1000)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage(), 'latency_ms' => (int)((microtime(true) - $t0) * 1000)];
        }
    }

    // -------------------------------------------------------------------------
    // HTTP layer
    // -------------------------------------------------------------------------

    /**
     * Выполняет запрос с Bearer-токеном, с повтором при ошибке 401.
     */
    private function authorizedRequest(string $method, string $path, array $body = []): array
    {
        $token = $this->authenticate();

        try {
            return $this->request($method, $path, $body, true, $token);
        } catch (\RuntimeException $e) {
            // При 401 сбрасываем токен и повторяем один раз
            if (str_contains($e->getMessage(), '401')) {
                $this->invalidateToken();
                $token = $this->authenticate();
                return $this->request($method, $path, $body, true, $token);
            }
            throw $e;
        }
    }

    /**
     * Выполняет HTTP-запрос к API с логикой повторов.
     *
     * @param string      $method  GET|POST|PUT|DELETE
     * @param string      $path    Путь относительно apiUrl
     * @param array       $body    Тело запроса
     * @param bool        $auth    Добавлять ли заголовок Authorization
     * @param string|null $token   Bearer-токен (если $auth = true)
     *
     * @throws \RuntimeException
     */
    private function request(string $method, string $path, array $body = [], bool $auth = true, ?string $token = null): array
    {
        $url     = rtrim($this->apiUrl, '/') . $path;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryAttempts) {
            $attempt++;
            try {
                $ch = curl_init();

                $headers = ['Content-Type: application/json', 'Accept: application/json'];
                if ($auth && $token) {
                    $headers[] = 'Authorization: Bearer ' . $token;
                }

                curl_setopt_array($ch, [
                    CURLOPT_URL            => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_CONNECTTIMEOUT => 10,
                    CURLOPT_HTTPHEADER     => $headers,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);

                if ($method === 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                } elseif ($method === 'PUT') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                } elseif ($method === 'DELETE') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                }

                if ($method === 'POST' || $method === 'PUT') {
                    Yii::info(
                        sprintf('Таможня:ДП %s %s payload: %s', $method, $path, json_encode($body, JSON_UNESCAPED_UNICODE)),
                        'dp-api'
                    );
                }

                $responseBody = curl_exec($ch);
                $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError    = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    throw new \RuntimeException('Таможня:ДП cURL ошибка: ' . $curlError);
                }

                $decoded = json_decode($responseBody, true);

                Yii::info(
                    sprintf('Таможня:ДП %s %s → HTTP %d', $method, $path, $httpCode),
                    'dp-api'
                );

                if ($httpCode === 401) {
                    throw new \RuntimeException('Таможня:ДП 401 Unauthorized: ' . $responseBody);
                }

                if ($httpCode >= 400) {
                    $errorMsg = $decoded['message'] ?? $decoded['error'] ?? $responseBody;
                    Yii::error(
                        sprintf('Таможня:ДП HTTP %d для %s %s. Payload: %s. Ответ: %s',
                            $httpCode, $method, $path,
                            json_encode($body, JSON_UNESCAPED_UNICODE),
                            $responseBody
                        ),
                        'dp-api'
                    );
                    throw new \RuntimeException(
                        sprintf('Таможня:ДП HTTP %d для %s %s: %s', $httpCode, $method, $path, $errorMsg)
                    );
                }

                // DELETE может возвращать пустое тело
                if ($method === 'DELETE') {
                    return [];
                }

                if (!is_array($decoded)) {
                    throw new \RuntimeException('Таможня:ДП: некорректный JSON в ответе: ' . $responseBody);
                }

                return $decoded;

            } catch (\RuntimeException $e) {
                $lastException = $e;

                // Не повторяем при 401 или клиентских ошибках (4xx)
                if (str_contains($e->getMessage(), '401')
                    || preg_match('/HTTP 4\d\d/', $e->getMessage())) {
                    throw $e;
                }

                if ($attempt < $this->retryAttempts) {
                    Yii::warning(
                        sprintf('Таможня:ДП: попытка %d/%d не удалась (%s %s): %s. Повтор через %d с.',
                            $attempt, $this->retryAttempts, $method, $path,
                            $e->getMessage(), $this->retryDelay
                        ),
                        'dp-api'
                    );
                    sleep($this->retryDelay);
                }
            }
        }

        Yii::error(
            sprintf('Таможня:ДП: все %d попытки исчерпаны для %s %s: %s',
                $this->retryAttempts, $method, $path, $lastException?->getMessage()
            ),
            'dp-api'
        );

        throw $lastException ?? new \RuntimeException('Таможня:ДП: неизвестная ошибка');
    }
}
