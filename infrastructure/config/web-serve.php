<?php

// Конфигурация для yii serve (без web-specific компонентов)
$config = require __DIR__ . '/web.php';

// Удаляем компоненты, которые не работают в console приложении
unset($config['components']['errorHandler']['errorAction']);
unset($config['components']['user']); // User component не нужен для serve
unset($config['components']['authClientCollection']); // Auth не нужен для serve

// Полностью удаляем request компонент - serve использует свой
unset($config['components']['request']);

return $config;
