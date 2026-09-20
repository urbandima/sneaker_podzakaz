<?php

namespace app\backend\modules\admin\services;

use Yii;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderItem;
use app\backend\modules\procurement\models\Buyout;
use app\backend\modules\procurement\models\BuyoutOrderLink;

/**
 * OrderPaymentService — денежная часть заказа: закупочная стоимость (buyout),
 * загрузка чека закупки, пересчёт итоговой суммы заказа по составу товаров.
 *
 * Извлечено из OrderController (CMP-392, план CMP-371 п.3.2).
 *
 * НЕ МЁРЖИТЬ в main до security review — работает с денежными суммами и
 * файлами чеков (upload). См. решение CEO в CMP-392 (18.09): гейт SecurityEngineer
 * относится к мёржу, а не к написанию кода.
 */
class OrderPaymentService
{
    public const ALLOWED_RECEIPT_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    public const ALLOWED_RECEIPT_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'application/pdf',
    ];

    /** Чистая функция валидации типа файла — testable без реального upload. */
    public static function isAllowedReceiptFile(string $extension, string $mimeType): bool
    {
        return in_array(strtolower($extension), self::ALLOWED_RECEIPT_EXTENSIONS, true)
            && in_array($mimeType, self::ALLOWED_RECEIPT_MIME_TYPES, true);
    }

    /**
     * #12 Сохранение данных выкупа, создание Buyout draft и линковка к заказу.
     *
     * @return array{success: bool, message: string, buyout_filled?: bool, product_price?: float, status_code?: int}
     */
    public function saveBuyout(Order $order, array $post, ?\yii\web\UploadedFile $receipt = null): array
    {
        if (isset($post['purchase_cost'])) {
            $order->purchase_cost     = $post['purchase_cost'];
        }
        if (isset($post['purchase_currency'])) {
            $order->purchase_currency = $post['purchase_currency'];
        }
        if (isset($post['purchase_date'])) {
            $order->purchase_date     = $post['purchase_date'];
        }
        if (isset($post['purchase_user_id'])) {
            $order->purchase_user_id  = (int)$post['purchase_user_id'];
        }
        if (!empty($post['china_track_number'])) {
            $order->china_track_number = $post['china_track_number'];
        }

        // File upload for receipt — whitelist by extension + real MIME type (RCE prevention)
        if ($receipt) {
            $extension = strtolower($receipt->extension);
            $mimeType = mime_content_type($receipt->tempName);

            if (!self::isAllowedReceiptFile($extension, $mimeType)) {
                return [
                    'success'     => false,
                    'message'     => 'Недопустимый формат файла. Разрешены: jpg, png, webp, pdf',
                    'status_code' => 422,
                ];
            }

            $uploadDir = Yii::getAlias('@webroot') . '/uploads/receipts/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            if (!file_exists($uploadDir . '.htaccess')) {
                @file_put_contents($uploadDir . '.htaccess', "php_flag engine off\n<FilesMatch \"\\.(php|php\\d?|phtml|phar)$\">\nRequire all denied\n</FilesMatch>\n");
            }
            $filename = 'receipt_' . $order->id . '_' . time() . '.' . $extension;
            if ($receipt->saveAs($uploadDir . $filename)) {
                $order->purchase_receipt_url = '/uploads/receipts/' . $filename;
            }
        }

        $order->purchase_status = 'draft';

        // Auto-fill product_price from purchase_cost if empty
        if ((float)$order->purchase_cost > 0 && (float)($order->product_price ?? 0) == 0) {
            $order->product_price = $order->purchase_cost;
        }

        // Set expected_delivery_at if not already set
        if (empty($order->expected_delivery_at)) {
            $order->expected_delivery_at = $order->computeExpectedDelivery();
        }

        $order->save(false);

        $this->syncBuyoutRecord($order);

        return [
            'success'       => true,
            'message'       => 'Данные выкупа сохранены',
            'buyout_filled' => $order->isBuyoutFilled(),
            'product_price' => (float)$order->product_price,
        ];
    }

    /** Create or update the Buyout + BuyoutOrderLink linked to this order. */
    private function syncBuyoutRecord(Order $order): void
    {
        try {
            $link   = BuyoutOrderLink::find()->where(['order_id' => $order->id])->one();
            $buyout = $link ? Buyout::findOne($link->buyout_id) : null;

            if (!$buyout) {
                $buyout = new Buyout();
            }

            $buyout->status           = Buyout::STATUS_DRAFT;
            $buyout->unit_cost_source = (float)($order->purchase_cost ?: 0);
            $buyout->source_currency  = $order->purchase_currency ?: 'CNY';
            $buyout->tracking_number  = $order->china_track_number ?: '';
            $buyout->receipt_url      = $order->purchase_receipt_url ?: '';
            $buyout->ordered_at       = !empty($order->purchase_date)
                ? strtotime($order->purchase_date) : null;
            $buyout->buyer_user_id    = $order->purchase_user_id ?: Yii::$app->user->id;
            $buyout->source           = 'manual';
            $buyout->qty              = 1;

            if ($buyout->save(false) && !$link) {
                $link            = new BuyoutOrderLink();
                $link->buyout_id = $buyout->id;
                $link->order_id  = $order->id;
                $link->qty       = 1;
                $link->save(false);
            }
        } catch (\Throwable $e) {
            Yii::warning('Buyout save error for order #' . $order->id . ': ' . $e->getMessage(), 'order');
        }
    }

    /**
     * Пересчитать состав и итоговую сумму заказа из списка позиций.
     *
     * @return array{0: float, 1: int} [totalAmount, itemCount]
     * @throws \Exception при пустом составе или ошибке сохранения
     */
    public function saveOrderItems(Order $order, array $items, bool $replaceExisting): array
    {
        if ($replaceExisting) {
            OrderItem::deleteAll(['order_id' => $order->id]);
        }

        $totalAmount = 0;
        $itemCount = 0;

        foreach ($items as $itemData) {
            $productName = trim((string)($itemData['product_name'] ?? ''));
            $priceRaw = $itemData['price'] ?? null;

            if ($productName === '' || $priceRaw === null || $priceRaw === '') {
                continue;
            }

            $item = new OrderItem();
            $item->order_id = $order->id;
            $item->product_name = $productName;
            $item->quantity = isset($itemData['quantity']) ? (int)$itemData['quantity'] : 1;
            $item->price = (float)$priceRaw;

            if (!$item->save()) {
                throw new \Exception('Ошибка сохранения товара: ' . json_encode($item->errors));
            }

            $totalAmount += $item->total;
            $itemCount++;
        }

        if ($itemCount === 0) {
            throw new \Exception('Необходимо добавить хотя бы один товар в заказ');
        }

        $order->total_amount = $totalAmount;
        if (!$order->save(false)) {
            throw new \Exception('Ошибка обновления суммы заказа');
        }

        return [$totalAmount, $itemCount];
    }
}
