<?php

/**
 * Конфигурация Correlation ID
 */
return [
    'enabled' => true,
    
    // Имя заголовка для Correlation ID
    'header_name' => 'X-Correlation-ID',
    
    // Генерировать новый ID если не передан
    'generate_if_missing' => true,
    
    // Добавлять в логи автоматически
    'log_context' => true,
    
    // Добавлять в HTTP ответы
    'response_header' => true,
    
    // Передавать в下游 сервисы
    'propagate' => true,
];
