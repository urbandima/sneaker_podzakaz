<?php

return [
    'adminEmail' => 'admin@sneakerculture.by',
    'senderEmail' => 'noreply@sneakerculture.by',
    'senderName' => 'СникерКультура',
    
    // Статусы заказов
    'orderStatuses' => [
        'created' => 'Заказ составлен',
        'paid' => 'Заказ оплачен',
        'accepted' => 'Заказ принят',
        'ordered' => 'Заказан товар',
        'received' => 'Заказ получен',
        'issued' => 'Заказ выдан',
    ],
    
    // Статусы, которые может изменять логист
    'logistStatuses' => [
        'ordered' => 'Заказан товар',
        'received' => 'Заказ получен',
        'issued' => 'Заказ выдан',
    ],
    
    // Тестовые реквизиты ООО "СникерКультура"
    'companyDetails' => [
        'name' => 'ООО "СникерКультура"',
        'unp' => '123456789',
        'address' => 'г. Минск, ул. Тестовая, д. 1',
        'bank' => 'ОАО "Тестовый банк"',
        'bic' => 'TESTBY2X',
        'account' => 'BY12TEST12345678901234567890',
        'phone' => '+375 29 123-45-67',
        'email' => 'info@sneakerculture.by',
    ],
];
