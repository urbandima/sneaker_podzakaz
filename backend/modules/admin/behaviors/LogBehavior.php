<?php

namespace app\backend\modules\admin\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use app\backend\modules\admin\services\ActivityLogService;

/**
 * Поведение для автоматического логирования create/update/delete AR-модели.
 *
 * Подключение:
 * ```php
 * public function behaviors(): array {
 *     return [
 *         ...parent::behaviors(),
 *         [
 *             'class'          => \app\backend\modules\admin\behaviors\LogBehavior::class,
 *             'targetType'     => 'Order',
 *             'labelAttribute' => 'order_number',
 *         ],
 *     ];
 * }
 * ```
 */
class LogBehavior extends Behavior
{
    /** Тип цели: Order, Customer, Product, ... */
    public string $targetType = '';

    /** Атрибут, который используется как человекочитаемый label */
    public ?string $labelAttribute = 'name';

    public function events(): array
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    public function afterInsert($event): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        ActivityLogService::logCreate(
            $owner,
            $this->targetType ?: null,
            $this->resolveLabel($owner)
        );
    }

    public function afterUpdate($event): void
    {
        /** @var ActiveRecord $owner */
        $owner    = $this->owner;
        $oldAttrs = $event->changedAttributes ?? [];

        if (empty($oldAttrs)) {
            return;
        }

        ActivityLogService::logChange(
            $owner,
            $oldAttrs,
            $this->targetType ?: null,
            $this->resolveLabel($owner)
        );
    }

    public function afterDelete($event): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        ActivityLogService::logDelete(
            $owner,
            $this->targetType ?: null,
            $this->resolveLabel($owner)
        );
    }

    // -------------------------------------------------------------------------

    private function resolveLabel(ActiveRecord $model): string
    {
        if ($this->labelAttribute && isset($model->{$this->labelAttribute})) {
            $val = $model->{$this->labelAttribute};
            if ($val !== null && $val !== '') {
                return (string)$val;
            }
        }
        return '#' . $model->getPrimaryKey();
    }
}
