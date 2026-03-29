<?php

/**
 * Class Aliases - Алиасы для совместимости
 */

Yii::setAlias('@app/helpers', '@app/backend/shared/helpers');

// Алиасы для классов
class_alias('app\\backend\\shared\\helpers\\ProductCardHelper', 'app\\helpers\\ProductCardHelper');
class_alias('app\\backend\\shared\\helpers\\ImageHelper', 'app\\helpers\\ImageHelper');
class_alias('app\\backend\\modules\\catalog\\models\\Product', 'app\\models\\Product');
