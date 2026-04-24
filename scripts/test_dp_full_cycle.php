<?php
/**
 * Тест полного цикла: заполнение паспортных данных → отправка в ДоброПост
 *
 * Запуск:
 *   php scripts/test_dp_full_cycle.php              # использовать заказ 378
 *   php scripts/test_dp_full_cycle.php 123          # указать ID заказа
 *
 * Что делает:
 *  1) Находит заказ по ID (по умолчанию — 378)
 *  2) Заполняет паспортные данные тестовыми (если не заполнены)
 *  3) Проверяет isPassportComplete()
 *  4) Формирует payload для ДП и печатает его
 *  5) (опционально) Отправляет заказ в ДП — см. DRY_RUN ниже
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
defined('YII_ENV_DEV') or define('YII_ENV_DEV', true);
defined('YII_ENV_PROD') or define('YII_ENV_PROD', false);
defined('YII_ENV_TEST') or define('YII_ENV_TEST', false);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../infrastructure/config/bootstrap.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../infrastructure/config/console.php';

use app\backend\modules\checkout\models\Order;

$app = new \yii\console\Application($config);

// --- Параметры теста ---
$orderId = (int) ($argv[1] ?? 378);
$dryRun  = in_array('--dry-run', $argv, true) || !in_array('--send', $argv, true);

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  ТЕСТ: Паспортные данные → ДоброПост                    ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "Заказ ID: {$orderId}\n";
echo "Режим:     " . ($dryRun ? 'DRY-RUN (без реальной отправки)' : 'SEND (реальная отправка в ДП)') . "\n\n";

// --- 1. Найти заказ ---
$order = Order::findOne($orderId);
if (!$order) {
    echo "❌ Заказ #{$orderId} не найден\n";
    exit(1);
}
echo "✓ Заказ найден: №{$order->order_number}, статус: {$order->status}\n";
echo "  Клиент: {$order->client_name} / {$order->client_phone} / {$order->client_email}\n\n";

// --- 2. Заполнить паспортные данные, если не полные ---
$needsFill = !$order->isPassportComplete();
if ($needsFill) {
    echo "▸ Паспортные данные не заполнены — заполняю тестовыми значениями…\n";

    $testData = [
        'recipient_last_name'   => $order->recipient_last_name   ?: 'Иванов',
        'recipient_first_name'  => $order->recipient_first_name  ?: 'Иван',
        'recipient_middle_name' => $order->recipient_middle_name ?: 'Иванович',
        'passport_series'       => $order->passport_series       ?: 'MP',
        'passport_number'       => $order->passport_number       ?: '1234567',
        'passport_issue_date'   => $order->passport_issue_date   ?: '2015-03-10',
        'birth_date'            => $order->birth_date            ?: '1990-05-15',
        'inn'                   => $order->inn                   ?: '123456789012',
        'city'                  => $order->city                  ?: 'Минск',
        'region'                => $order->region                ?: 'Минская обл.',
        'postal_code'           => $order->postal_code           ?: '220030',
        'full_address'          => $order->full_address          ?: 'г. Минск, ул. Ленина, д. 10, кв. 5',
        'customs_description'   => $order->customs_description   ?: 'Обувь спортивная',
        'item_quantity'         => $order->item_quantity         ?: 1,
        'item_price_cny'        => $order->item_price_cny        ?: 800,
        'shipment_value_cny'    => $order->shipment_value_cny    ?: 800,
        'china_track_number'    => $order->china_track_number    ?: 'SF1300000000000',
    ];

    foreach ($testData as $attr => $value) {
        if ($order->hasAttribute($attr) && empty($order->$attr)) {
            $order->$attr = $value;
        }
    }

    if (!$order->save(false)) {
        echo "❌ Не удалось сохранить паспортные данные: " . json_encode($order->errors, JSON_UNESCAPED_UNICODE) . "\n";
        exit(1);
    }
    echo "✓ Паспортные данные сохранены\n";
} else {
    echo "✓ Паспортные данные уже заполнены\n";
}

// --- 3. Проверить полноту данных ---
$isComplete = $order->isPassportComplete();
echo "▸ isPassportComplete(): " . ($isComplete ? '✓ да' : '✗ нет') . "\n\n";

if (!$isComplete) {
    echo "❌ Невозможно отправить в ДП — данные всё ещё неполные.\n";
    echo "   Проверьте реализацию Order::isPassportComplete()\n";
    exit(1);
}

// --- 4. Сформировать payload ---
$dp = Yii::$app->dobropost;
$payload = $dp->buildShipmentPayload($order);

echo "▸ Payload для ДоброПост API:\n";
echo "────────────────────────────────────────────────────────\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "────────────────────────────────────────────────────────\n\n";

// --- 5. Отправка в ДП (если разрешено) ---
if ($dryRun) {
    echo "ℹ DRY-RUN: реальной отправки НЕ производится.\n";
    echo "  Запустите: php scripts/test_dp_full_cycle.php {$orderId} --send\n";
    exit(0);
}

if ($order->isSubmittedToDP()) {
    echo "⚠ Заказ уже отправлен в ДП (shipment #{$order->dp_shipment_id}, трек {$order->dp_track_number})\n";
    exit(0);
}

echo "▸ Отправляю заказ в ДоброПост…\n";
try {
    $response = $dp->createShipment($order);
    echo "✓ Отправлено успешно!\n";
    echo "  Shipment ID: " . ($response['id'] ?? '?') . "\n";
    echo "  DP track:    " . ($response['dptrackNumber'] ?? '?') . "\n";
    echo "  Статус:      " . ($response['status']['name'] ?? ($response['status']['id'] ?? '?')) . "\n";
} catch (\Throwable $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    exit(1);
}
