<?php
namespace app\backend\modules\procurement\models;

use yii\db\ActiveRecord;
use app\backend\modules\catalog\models\Brand;

/**
 * @property int $id
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property string|null $country        ISO-2 code
 * @property string|null $region
 * @property string|null $payment_terms
 * @property string|null $notes
 * @property string|null $brands         JSON array of brand_ids
 * @property string|null $contract_type  commission|stock|to_order|mixed
 * @property string|null $contract_terms
 * @property int $is_active
 * @property string $created_at
 */
class Supplier extends ActiveRecord
{
    public static function tableName() { return 'supplier'; }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name', 'contact_person', 'email'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 50],
            [['country'], 'string', 'max' => 2],
            [['region'], 'string', 'max' => 64],
            [['address', 'payment_terms', 'notes', 'contract_terms', 'brands'], 'string'],
            [['is_active'], 'boolean'],
            [['contract_type'], 'in', 'range' => ['commission', 'stock', 'to_order', 'mixed']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'             => 'ID',
            'name'           => 'Название',
            'contact_person' => 'Контактное лицо',
            'phone'          => 'Телефон',
            'email'          => 'Email',
            'address'        => 'Адрес',
            'country'        => 'Страна',
            'region'         => 'Регион',
            'payment_terms'  => 'Условия оплаты',
            'notes'          => 'Примечания',
            'brands'         => 'Бренды',
            'contract_type'  => 'Тип договора',
            'contract_terms' => 'Условия договора',
            'is_active'      => 'Активен',
            'created_at'     => 'Создан',
        ];
    }

    public function getPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, ['supplier_id' => 'id']);
    }

    // ── Brands ────────────────────────────────────────────────────────────────

    /**
     * Returns the decoded brand_ids array stored in the JSON `brands` column.
     * @return int[]
     */
    public function getBrandIds(): array
    {
        if (empty($this->brands)) return [];
        $decoded = json_decode($this->brands, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    /**
     * Returns Brand[] objects for this supplier.
     * @return Brand[]
     */
    public function getBrandsList(): array
    {
        $ids = $this->getBrandIds();
        if (empty($ids)) return [];
        return Brand::find()->where(['id' => $ids])->orderBy(['name' => SORT_ASC])->all();
    }

    // ── Country ───────────────────────────────────────────────────────────────

    /** ISO-2 → Russian name map */
    public static function getCountryMap(): array
    {
        return [
            'BY' => 'Беларусь',
            'RU' => 'Россия',
            'CN' => 'Китай',
            'IT' => 'Италия',
            'TR' => 'Турция',
            'DE' => 'Германия',
            'PL' => 'Польша',
            'US' => 'США',
            'GB' => 'Великобритания',
            'FR' => 'Франция',
            'ES' => 'Испания',
            'KR' => 'Южная Корея',
            'JP' => 'Япония',
        ];
    }

    public function getCountryName(): string
    {
        $map = self::getCountryMap();
        return $map[$this->country] ?? ($this->country ?: '—');
    }

    public static function getCountryFlagEmoji(string $iso): string
    {
        if (!$iso || strlen($iso) !== 2) return '';
        $code = strtoupper($iso);
        $flag = '';
        foreach (str_split($code) as $char) {
            $flag .= mb_chr(0x1F1E0 + ord($char) - ord('A'), 'UTF-8');
        }
        return $flag;
    }

    // ── Contract type ─────────────────────────────────────────────────────────

    public static function getContractTypeOptions(): array
    {
        return [
            'commission' => 'Комиссия',
            'stock'      => 'Наличие',
            'to_order'   => 'Подзаказ',
            'mixed'      => 'Смешанный',
        ];
    }

    public function getContractTypeLabel(): string
    {
        return self::getContractTypeOptions()[$this->contract_type] ?? '—';
    }

    public function getContractTypeBadgeColor(): string
    {
        return match($this->contract_type) {
            'commission' => '#5c6ef8',
            'stock'      => '#00a651',
            'to_order'   => '#e07b00',
            'mixed'      => '#9b59b6',
            default      => '#9ca3af',
        };
    }

    // ── Stats helper ──────────────────────────────────────────────────────────

    public function getPurchaseStats(): array
    {
        $rows = PurchaseOrder::find()
            ->select([
                'COUNT(*) AS cnt',
                'SUM(total_byn) AS total',
                'AVG(total_byn) AS avg',
                'MAX(ordered_at) AS last_date',
            ])
            ->where(['supplier_id' => $this->id])
            ->asArray()
            ->one();

        return [
            'count'     => (int)($rows['cnt']   ?? 0),
            'total'     => (float)($rows['total'] ?? 0),
            'avg'       => (float)($rows['avg']   ?? 0),
            'last_date' => $rows['last_date'] ?? null,
        ];
    }
}
