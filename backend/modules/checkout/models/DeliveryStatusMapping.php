<?php

namespace app\backend\modules\checkout\models;

use yii\db\ActiveRecord;

/**
 * DeliveryStatusMapping — маппинг статусов провайдера на внутренние статусы
 *
 * @property int    $id
 * @property int    $provider_id
 * @property string $provider_status_id
 * @property string $provider_status_name
 * @property string $internal_status
 * @property string $display_name
 * @property int    $estimated_days
 * @property int    $sort_order
 * @property int    $is_final
 *
 * @property DeliveryProvider $provider
 */
class DeliveryStatusMapping extends ActiveRecord
{
    // -------------------------------------------------------------------------
    // Internal status constants
    // -------------------------------------------------------------------------
    const STATUS_WAITING             = 'waiting';
    const STATUS_RECEIVED            = 'received';
    const STATUS_PROCESSING          = 'processing';
    const STATUS_IN_TRANSIT          = 'in_transit';
    const STATUS_CUSTOMS             = 'customs';
    const STATUS_CUSTOMS_DUTY        = 'customs_duty';
    const STATUS_CUSTOMS_CLEARED     = 'customs_cleared';
    const STATUS_CUSTOMS_REJECTED    = 'customs_rejected';
    const STATUS_IN_DELIVERY         = 'in_delivery';
    const STATUS_PREPARED_DELIVERY   = 'prepared_for_delivery';
    const STATUS_EDIT_REQUEST        = 'edit_request';
    const STATUS_EDIT_REJECTED       = 'edit_rejected';
    const STATUS_EDIT_DONE           = 'edit_done';
    const STATUS_LOST                = 'lost';

    public static function tableName(): string
    {
        return '{{%delivery_status_mapping}}';
    }

    public function rules(): array
    {
        return [
            [['provider_id', 'provider_status_id'], 'required'],
            [['provider_id', 'sort_order', 'estimated_days'], 'integer'],
            [['is_final'], 'boolean'],
            [['provider_status_id'], 'string', 'max' => 20],
            [['provider_status_name', 'display_name'], 'string', 'max' => 255],
            [['internal_status'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                   => 'ID',
            'provider_id'          => 'Провайдер',
            'provider_status_id'   => 'ID статуса',
            'provider_status_name' => 'Название у провайдера',
            'internal_status'      => 'Внутренний статус',
            'display_name'         => 'Отображаемое название',
            'estimated_days'       => 'Дней до доставки',
            'sort_order'           => 'Порядок',
            'is_final'             => 'Финальный',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function getProvider(): \yii\db\ActiveQuery
    {
        return $this->hasOne(DeliveryProvider::class, ['id' => 'provider_id']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Возвращает отображаемое название статуса (display_name или provider_status_name).
     */
    public function getDisplayName(): string
    {
        return $this->display_name ?: ($this->provider_status_name ?: $this->internal_status);
    }

    /**
     * Возвращает ориентировочное количество дней доставки.
     */
    public function getEstimatedDays(): ?int
    {
        return $this->estimated_days;
    }

    /**
     * Является ли статус финальным (доставлено, потеряно, отказ таможни).
     */
    public function isFinal(): bool
    {
        return (bool) $this->is_final;
    }

    /**
     * Находит маппинг по провайдеру и ID статуса провайдера.
     */
    public static function findByProviderStatus(int $providerId, string $providerStatusId): ?self
    {
        return static::findOne([
            'provider_id'        => $providerId,
            'provider_status_id' => $providerStatusId,
        ]);
    }
}
