<?php

namespace app\backend\modules\common\behaviors;

use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;

/**
 * SoftDeleteBehavior — поведение для мягкого удаления
 * 
 * Рекомендация #12: Soft Delete везде
 * 
 * Использование:
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         SoftDeleteBehavior::class,
 *     ];
 * }
 * ```
 */
class SoftDeleteBehavior extends AttributeBehavior
{
    public $value;
    
    public function init()
    {
        parent::init();
        
        if (empty($this->attributes)) {
            $this->attributes = [
                ActiveRecord::EVENT_BEFORE_DELETE => 'deleted_at',
            ];
        }
    }
    
    protected function getValue($event)
    {
        if ($this->value === null) {
            return time();
        }
        return $this->value instanceof \Closure ? call_user_func($this->value, $event) : $this->value;
    }
    
    /**
     * Переопределяем delete для мягкого удаления
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'softDelete',
        ];
    }
    
    public function softDelete($event)
    {
        $model = $this->owner;
        
        // Отменяем реальное удаление
        $event->isValid = false;
        
        // Устанавливаем deleted_at
        $model->deleted_at = time();
        
        // Сохраняем без валидации
        $model->save(false);
        
        return false;
    }
}
