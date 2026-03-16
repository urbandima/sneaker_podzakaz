<?php

namespace app\api\v1\resources;

use yii\base\BaseObject;
use app\backend\modules\checkout\models\Order;

/**
 * API ресурс заказа (DTO)
 */
class OrderResource extends BaseObject
{
    public int $id;
    public string $order_number;
    public string $status;
    public float $total;
    public string $created_at;
    public array $items;
    public ?array $customer;

    public function __construct(Order $order)
    {
        parent::__construct([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'total' => (float) $order->total,
            'created_at' => $order->created_at,
            'items' => array_map(function($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'size' => $item->size,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                ];
            }, $order->items),
            'customer' => $order->customer ? [
                'name' => $order->customer->getShortName(),
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ] : null,
        ]);
    }

    public static function collection($orders): array
    {
        return array_map(function($order) {
            return new self($order);
        }, $orders);
    }
}
