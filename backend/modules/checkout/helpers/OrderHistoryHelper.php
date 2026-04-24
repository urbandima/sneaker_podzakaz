<?php

namespace app\backend\modules\checkout\helpers;

class OrderHistoryHelper
{
    private static array $labels = [
        'status'                  => 'Статус',
        'client_name'             => 'ФИО клиента',
        'client_phone'            => 'Телефон клиента',
        'client_email'            => 'Email клиента',
        'total_amount'            => 'Сумма заказа',
        'discount'                => 'Скидка',
        'delivery_method'         => 'Метод доставки',
        'delivery_address'        => 'Адрес доставки',
        'payment_method'          => 'Метод оплаты',
        'comment'                 => 'Комментарий к заказу',
        'delivery_date'           => 'Дата доставки',
        'china_track_number'      => 'Трек-номер Китай',
        'local_track_number'      => 'Трек-номер Беларусь',
        'shipment_value_cny'      => 'Стоимость отправления (CNY)',
        'recipient_last_name'     => 'Фамилия получателя',
        'recipient_first_name'    => 'Имя получателя',
        'recipient_middle_name'   => 'Отчество получателя',
        'passport_series'         => 'Серия паспорта',
        'passport_number'         => 'Номер паспорта',
        'passport_issue_date'     => 'Дата выдачи паспорта',
        'birth_date'              => 'Дата рождения',
        'inn'                     => 'ИНН',
        'full_address'            => 'Полный адрес',
        'city'                    => 'Город',
        'region'                  => 'Регион',
        'postal_code'             => 'Индекс',
        'customs_description'     => 'Таможенное описание',
        'item_quantity'           => 'Количество товара',
        'item_price_cny'          => 'Цена товара (CNY)',
        'product_link'            => 'Ссылка на товар',
        'dobropost_tariff'        => 'Тариф Таможня:ДП',
        'sneakerhead_order_link'  => 'Ссылка на заказ',
        'ms_number'               => '№ МойСклад',
        'is_processed'            => 'Обработан',
        'is_shipped'              => 'Отправлен',
        'customs_cleared'         => 'Таможня пройдена',
        'product_price'           => 'Цена товара (BYN)',
        'logistics_price'         => 'Цена логистики',
        'commission_price'        => 'Комиссия',
        'warehouse_arrival_date'  => 'Дата прибытия на склад',
        'china_delivery_status'   => 'Статус доставки Китай',
    ];

    public static function fieldLabel(string $field): string
    {
        return self::$labels[$field] ?? $field;
    }

    public static function formatTimestamp(int $ts): string
    {
        $months = ['янв', 'фев', 'мар', 'апр', 'май', 'июн',
                   'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];
        $d = (int)date('j', $ts);
        $m = $months[(int)date('n', $ts) - 1];
        $y = date('Y', $ts);
        $t = date('H:i', $ts);
        return "$d $m $y, $t";
    }
}
