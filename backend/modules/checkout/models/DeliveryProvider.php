<?php

namespace app\backend\modules\checkout\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * DeliveryProvider — провайдер доставки (Таможня:ДП, Европочта, Белпочта и др.)
 *
 * @property int    $id
 * @property string $code
 * @property string $name
 * @property string $type               'international' | 'local'
 * @property string $api_url
 * @property array  $api_credentials    JSON
 * @property int    $is_active
 * @property array  $settings           JSON
 * @property int    $created_at
 * @property int    $updated_at
 *
 * @property DeliveryStatusMapping[] $statusMappings
 */
class DeliveryProvider extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%delivery_provider}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['code', 'name', 'type'], 'required'],
            [['code'], 'string', 'max' => 50],
            [['name'], 'string', 'max' => 255],
            [['type'], 'in', 'range' => ['international', 'local']],
            [['api_url'], 'string', 'max' => 500],
            [['is_active'], 'boolean'],
            [['api_credentials', 'settings'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'code'            => 'Код',
            'name'            => 'Название',
            'type'            => 'Тип',
            'api_url'         => 'URL API',
            'api_credentials' => 'Учётные данные API',
            'is_active'       => 'Активен',
            'settings'        => 'Настройки',
            'created_at'      => 'Создан',
            'updated_at'      => 'Обновлён',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function getStatusMappings(): \yii\db\ActiveQuery
    {
        return $this->hasMany(DeliveryStatusMapping::class, ['provider_id' => 'id'])
            ->orderBy(['sort_order' => SORT_ASC]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Возвращает объект маппинга по provider_status_id.
     */
    public function mapStatus(string $providerStatusId): ?DeliveryStatusMapping
    {
        return DeliveryStatusMapping::find()
            ->where([
                'provider_id'        => $this->id,
                'provider_status_id' => $providerStatusId,
            ])
            ->one();
    }

    /**
     * Возвращает внутренний статус по provider_status_id.
     */
    public function getInternalStatus(string $providerStatusId): ?string
    {
        $mapping = $this->mapStatus($providerStatusId);
        return $mapping ? $mapping->internal_status : null;
    }

    /**
     * Находит активного провайдера по коду.
     */
    public static function findByCode(string $code): ?self
    {
        return static::findOne(['code' => $code, 'is_active' => 1]);
    }

    /**
     * JSON геттер/сеттер — api_credentials хранится как строка в БД.
     */
    public function getApiCredentialsArray(): array
    {
        if (is_array($this->api_credentials)) {
            return $this->api_credentials;
        }
        return $this->api_credentials ? (json_decode($this->api_credentials, true) ?? []) : [];
    }

    public function getSettingsArray(): array
    {
        if (is_array($this->settings)) {
            return $this->settings;
        }
        return $this->settings ? (json_decode($this->settings, true) ?? []) : [];
    }
}
