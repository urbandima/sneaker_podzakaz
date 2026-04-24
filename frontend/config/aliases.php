<?php

/**
 * Class Aliases - Алиасы для совместимости
 */

Yii::setAlias('@app/helpers', dirname(dirname(__DIR__)) . '/backend/shared/helpers');

// Алиасы для классов
class_alias('app\\backend\\shared\\helpers\\ProductCardHelper', 'app\\helpers\\ProductCardHelper');
class_alias('app\\backend\\shared\\helpers\\ImageHelper', 'app\\helpers\\ImageHelper');
class_alias('app\\backend\\shared\\helpers\\TextHelper', 'app\\helpers\\TextHelper');
class_alias('app\\backend\\modules\\catalog\\models\\Product', 'app\\models\\Product');
class_alias('app\\backend\\modules\\account\\models\\Customer', 'app\\models\\Customer');
class_alias('app\\backend\\modules\\checkout\\models\\Order', 'app\\models\\Order');
class_alias('app\\backend\\modules\\account\\models\\CustomerLoginForm', 'app\\models\\CustomerLoginForm');
class_alias('app\\backend\\modules\\account\\models\\CustomerRegisterForm', 'app\\models\\CustomerRegisterForm');
