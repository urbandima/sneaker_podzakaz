<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\User;
use app\models\Order;
use app\models\OrderItem;
use app\models\OrderHistory;

class TestController extends Controller
{
    /**
     * Создает тестовый заказ для проверки функционала
     */
    public function actionCreateTestOrder()
    {
        echo "🧪 Создание тестового заказа...\n\n";

        // Проверяем пользователей
        echo "1. Проверка пользователей:\n";
        $admin = User::findOne(['username' => 'admin']);
        $manager = User::findOne(['username' => 'manager']);
        $logist = User::findOne(['username' => 'logist']);

        echo "   ✅ Администратор: " . ($admin ? "найден (ID: {$admin->id})" : "НЕ НАЙДЕН!") . "\n";
        echo "   ✅ Менеджер: " . ($manager ? "найден (ID: {$manager->id})" : "НЕ НАЙДЕН!") . "\n";
        echo "   ✅ Логист: " . ($logist ? "найден (ID: {$logist->id})" : "НЕ НАЙДЕН!") . "\n\n";

        if (!$manager) {
            echo "❌ ОШИБКА: Менеджер не найден!\n";
            return 1;
        }

        // Создаем заказ
        echo "2. Создание заказа:\n";
        $order = new Order();
        $order->client_name = 'Тестовый Клиент Иванович';
        $order->client_phone = '+375 29 123-45-67';
        $order->client_email = 'test@example.com';
        $order->delivery_date = '20-25 декабря 2024';
        $order->created_by = $manager->id;

        if ($order->save()) {
            echo "   ✅ Заказ создан:\n";
            echo "      - Номер: {$order->order_number}\n";
            echo "      - Токен: {$order->token}\n";
            echo "      - ID: {$order->id}\n\n";
        } else {
            echo "   ❌ ОШИБКА при создании заказа:\n";
            print_r($order->errors);
            return 1;
        }

        // Добавляем товары
        echo "3. Добавление товаров:\n";
        $items = [
            ['Кроссовки Nike Air Max', 1, 300.00],
            ['Футболка Adidas', 2, 50.00],
            ['Рюкзак Puma', 1, 120.00],
        ];

        $totalAmount = 0;
        foreach ($items as $index => $itemData) {
            $item = new OrderItem();
            $item->order_id = $order->id;
            $item->product_name = $itemData[0];
            $item->quantity = $itemData[1];
            $item->price = $itemData[2];

            if ($item->save()) {
                echo "   ✅ Товар " . ($index + 1) . ": {$item->product_name} - {$item->quantity} шт x {$item->price} BYN = {$item->total} BYN\n";
                $totalAmount += $item->total;
            } else {
                echo "   ❌ ОШИБКА при добавлении товара:\n";
                print_r($item->errors);
            }
        }

        // Обновляем общую сумму
        $order->total_amount = $totalAmount;
        $order->save(false);
        echo "   💰 Итоговая сумма: {$totalAmount} BYN\n\n";

        // Назначаем логиста
        if ($logist) {
            echo "4. Назначение логиста:\n";
            $order->assigned_logist = $logist->id;
            $order->save(false);
            echo "   ✅ Логист назначен: {$logist->username}\n\n";
        }

        // Меняем статус
        echo "5. Изменение статуса:\n";
        $oldStatus = $order->status;
        $order->status = 'paid';
        $order->save(false);

        $history = new OrderHistory();
        $history->order_id = $order->id;
        $history->old_status = $oldStatus;
        $history->new_status = 'paid';
        $history->comment = 'Автоматическое тестирование';
        $history->changed_by = $manager->id;
        $history->save();

        echo "   ✅ Статус изменен: {$oldStatus} → paid\n";
        echo "   ✅ Запись в истории создана\n\n";

        // Генерируем публичную ссылку
        echo "6. Публичная ссылка:\n";
        $publicUrl = $order->getPublicUrl();
        echo "   🔗 {$publicUrl}\n\n";

        // Итоговая информация
        echo "═══════════════════════════════════════════════════════\n";
        echo "✅ ТЕСТОВЫЙ ЗАКАЗ СОЗДАН УСПЕШНО!\n";
        echo "═══════════════════════════════════════════════════════\n\n";
        echo "Информация для тестирования:\n";
        echo "─────────────────────────────────────────────────────────\n";
        echo "📋 Номер заказа: {$order->order_number}\n";
        echo "🔑 ID заказа: {$order->id}\n";
        echo "💰 Сумма: {$order->total_amount} BYN\n";
        echo "📦 Товаров: " . count($order->orderItems) . "\n";
        echo "👤 Менеджер: {$order->creator->username}\n";
        echo "🚚 Логист: " . ($order->logist ? $order->logist->username : 'Не назначен') . "\n";
        echo "📊 Статус: {$order->getStatusLabel()}\n";
        echo "─────────────────────────────────────────────────────────\n";
        echo "🔗 Публичная ссылка для клиента:\n";
        echo "   {$publicUrl}\n";
        echo "─────────────────────────────────────────────────────────\n\n";

        echo "Для просмотра в админке:\n";
        echo "http://localhost:8080/admin/order/{$order->id}\n\n";

        echo "Для тестирования:\n";
        echo "1. Скопируйте публичную ссылку\n";
        echo "2. Откройте в режиме инкогнито\n";
        echo "3. Загрузите подтверждение оплаты\n";
        echo "4. Проверьте изменение статуса\n\n";

        return 0;
    }

    /**
     * Проверка всех компонентов системы
     */
    public function actionCheckSystem()
    {
        echo "🔍 Проверка системы...\n\n";

        $checks = [
            'База данных' => $this->checkDatabase(),
            'Пользователи' => $this->checkUsers(),
            'Конфигурация' => $this->checkConfig(),
            'Email шаблоны' => $this->checkEmailTemplates(),
            'Директории' => $this->checkDirectories(),
        ];

        foreach ($checks as $name => $result) {
            $status = $result['status'] ? '✅' : '❌';
            echo "{$status} {$name}: {$result['message']}\n";
        }

        $totalChecks = count($checks);
        $passedChecks = count(array_filter($checks, function($check) {
            return $check['status'];
        }));

        echo "\n═══════════════════════════════════════════════════════\n";
        echo "Результат: {$passedChecks}/{$totalChecks} проверок пройдено\n";
        echo "═══════════════════════════════════════════════════════\n";

        return $passedChecks === $totalChecks ? 0 : 1;
    }

    private function checkDatabase()
    {
        try {
            Yii::$app->db->open();
            $tables = Yii::$app->db->schema->getTableNames();
            $requiredTables = ['user', 'order', 'order_item', 'order_history', 'migration'];
            $found = array_intersect($requiredTables, $tables);
            
            return [
                'status' => count($found) === count($requiredTables),
                'message' => count($found) . '/' . count($requiredTables) . ' таблиц найдено'
            ];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkUsers()
    {
        $count = User::find()->count();
        $hasAdmin = User::find()->where(['role' => 'admin'])->exists();
        $hasManager = User::find()->where(['role' => 'manager'])->exists();
        $hasLogist = User::find()->where(['role' => 'logist'])->exists();

        $allRoles = $hasAdmin && $hasManager && $hasLogist;

        return [
            'status' => $count >= 3 && $allRoles,
            'message' => "{$count} пользователей, все роли " . ($allRoles ? 'есть' : 'отсутствуют')
        ];
    }

    private function checkConfig()
    {
        $config = Yii::$app->params;
        $hasStatuses = isset($config['orderStatuses']) && !empty($config['orderStatuses']);
        $hasCompany = isset($config['companyDetails']) && !empty($config['companyDetails']);

        return [
            'status' => $hasStatuses && $hasCompany,
            'message' => 'Параметры ' . ($hasStatuses && $hasCompany ? 'настроены' : 'отсутствуют')
        ];
    }

    private function checkEmailTemplates()
    {
        $templates = [
            Yii::getAlias('@app/mail/order-created.php'),
            Yii::getAlias('@app/mail/payment-uploaded.php'),
            Yii::getAlias('@app/mail/layouts/html.php'),
            Yii::getAlias('@app/mail/layouts/text.php'),
        ];

        $existing = array_filter($templates, 'file_exists');

        return [
            'status' => count($existing) === count($templates),
            'message' => count($existing) . '/' . count($templates) . ' шаблонов найдено'
        ];
    }

    private function checkDirectories()
    {
        $dirs = [
            Yii::getAlias('@runtime'),
            Yii::getAlias('@webroot/uploads'),
            Yii::getAlias('@webroot/assets'),
        ];

        $writable = array_filter($dirs, 'is_writable');

        return [
            'status' => count($writable) === count($dirs),
            'message' => count($writable) . '/' . count($dirs) . ' директорий доступны для записи'
        ];
    }
}
