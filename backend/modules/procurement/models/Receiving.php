<?php

namespace app\backend\modules\procurement\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use app\backend\modules\admin\behaviors\LogBehavior;

class Receiving extends ActiveRecord
{
    // ── Statuses ───────────────────────────────────────────────────────────────
    const STATUS_DRAFT      = 'draft';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED    = 'arrived';
    const STATUS_INSPECTING = 'inspecting';
    const STATUS_ACCEPTED   = 'accepted';
    const STATUS_PARTIAL    = 'partial';
    const STATUS_CANCELLED  = 'cancelled';

    const ALLOWED_TRANSITIONS = [
        self::STATUS_DRAFT      => [self::STATUS_IN_TRANSIT, self::STATUS_CANCELLED],
        self::STATUS_IN_TRANSIT => [self::STATUS_ARRIVED,    self::STATUS_CANCELLED],
        self::STATUS_ARRIVED    => [self::STATUS_INSPECTING, self::STATUS_CANCELLED],
        self::STATUS_INSPECTING => [self::STATUS_ACCEPTED,   self::STATUS_PARTIAL, self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED   => [],
        self::STATUS_PARTIAL    => [],
        self::STATUS_CANCELLED  => [],
    ];

    const FINAL_STATUSES = [self::STATUS_ACCEPTED, self::STATUS_PARTIAL, self::STATUS_CANCELLED];

    public static function tableName(): string
    {
        return '{{%receiving}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class'              => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
            ],
            [
                'class'          => LogBehavior::class,
                'targetType'     => 'Receiving',
                'labelAttribute' => 'number',
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['status'], 'in', 'range' => array_keys(self::getStatuses())],
            [['supplier_id', 'buyout_id', 'receiver_user_id'], 'integer'],
            [['subtotal_byn', 'expenses_total_byn', 'total_with_expenses_byn'], 'number'],
            [['total_items', 'total_qty_expected', 'total_qty_arrived', 'total_qty_defected'], 'integer'],
            [['receiving_date', 'expected_date', 'arrived_date', 'accepted_date'], 'safe'],
            [['notes'], 'string'],
            [['number'], 'string', 'max' => 32],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                      => 'ID',
            'number'                  => '№ приёмки',
            'supplier_id'             => 'Поставщик',
            'buyout_id'               => 'Выкуп',
            'status'                  => 'Статус',
            'receiving_date'          => 'Дата приёмки',
            'expected_date'           => 'Ожидаемая дата',
            'arrived_date'            => 'Дата прибытия',
            'accepted_date'           => 'Дата принятия',
            'total_items'             => 'Позиций',
            'total_qty_expected'      => 'Ожидалось',
            'total_qty_arrived'       => 'Прибыло',
            'total_qty_defected'      => 'Дефект',
            'subtotal_byn'            => 'Стоимость товаров',
            'expenses_total_byn'      => 'Расходы',
            'total_with_expenses_byn' => 'Итого с расходами',
            'receiver_user_id'        => 'Принял',
            'notes'                   => 'Примечания',
        ];
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function getSupplier()
    {
        return $this->hasOne(Supplier::class, ['id' => 'supplier_id']);
    }

    public function getBuyout()
    {
        return $this->hasOne(Buyout::class, ['id' => 'buyout_id']);
    }

    public function getItems()
    {
        return $this->hasMany(ReceivingItem::class, ['receiving_id' => 'id'])
                    ->orderBy(['id' => SORT_ASC]);
    }

    public function getExpenses()
    {
        return $this->hasMany(ReceivingExpense::class, ['receiving_id' => 'id'])
                    ->orderBy(['id' => SORT_ASC]);
    }

    public function getDocuments()
    {
        return $this->hasMany(ReceivingDocument::class, ['receiving_id' => 'id'])
                    ->orderBy(['uploaded_at' => SORT_DESC]);
    }

    public function getHistory()
    {
        return $this->hasMany(ReceivingHistory::class, ['receiving_id' => 'id'])
                    ->orderBy(['changed_at' => SORT_ASC]);
    }

    // ── Statics ────────────────────────────────────────────────────────────────

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT      => 'Черновик',
            self::STATUS_IN_TRANSIT => 'В пути',
            self::STATUS_ARRIVED    => 'Прибыла',
            self::STATUS_INSPECTING => 'Проверка',
            self::STATUS_ACCEPTED   => 'Принята',
            self::STATUS_PARTIAL    => 'Частично',
            self::STATUS_CANCELLED  => 'Отменена',
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            self::STATUS_DRAFT      => '#6b7280',
            self::STATUS_IN_TRANSIT => '#d97706',
            self::STATUS_ARRIVED    => '#2563eb',
            self::STATUS_INSPECTING => '#7c3aed',
            self::STATUS_ACCEPTED   => '#059669',
            self::STATUS_PARTIAL    => '#ea580c',
            self::STATUS_CANCELLED  => '#dc2626',
        ];
    }

    public function getStatusLabel(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return self::getStatusColors()[$this->status] ?? '#6b7280';
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function isFinal(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES, true);
    }

    // ── Business logic ─────────────────────────────────────────────────────────

    public static function generateNumber(): string
    {
        $prefix = 'RCV-' . date('Ym') . '-';
        $last = static::find()
            ->where(['like', 'number', $prefix . '%', false])
            ->orderBy(['id' => SORT_DESC])
            ->scalar();

        $seq = $last ? ((int)substr($last, -5) + 1) : 1;
        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $items = $this->items;

        $this->total_items        = count($items);
        $this->total_qty_expected = array_sum(array_column($items, 'qty_expected'));
        $this->total_qty_arrived  = array_sum(array_column($items, 'qty_arrived'));
        $this->total_qty_defected = array_sum(array_column($items, 'qty_defected'));
        $this->subtotal_byn       = array_sum(array_map(
            fn($i) => $i->unit_cost_byn * $i->qty_arrived, $items
        ));
        $this->expenses_total_byn     = array_sum(array_column($this->expenses, 'amount_byn'));
        $this->total_with_expenses_byn = array_sum(array_column($items, 'final_cost_byn'));
    }

    public function redistributeExpenses(): void
    {
        $items    = $this->items;
        $expenses = $this->expenses;

        if (!$items) {
            return;
        }

        // Reset allocations
        foreach ($items as $item) {
            $item->allocated_expenses_byn = 0;
        }

        foreach ($expenses as $expense) {
            $totalArrived = array_sum(array_column($items, 'qty_arrived')) ?: 1;
            $totalValue   = array_sum(array_map(fn($i) => $i->unit_cost_byn * max($i->qty_arrived, 1), $items)) ?: 1;

            foreach ($items as $item) {
                $share = match($expense->distribution_method) {
                    'equal'    => $expense->amount_byn / count($items),
                    'by_qty'   => ($item->qty_arrived / $totalArrived) * $expense->amount_byn,
                    'by_value' => (($item->unit_cost_byn * max($item->qty_arrived, 1)) / $totalValue) * $expense->amount_byn,
                    default    => 0,
                };
                $item->allocated_expenses_byn += round($share, 2);
            }
        }

        // Recalculate final costs and save
        foreach ($items as $item) {
            $item->final_cost_byn = round(
                $item->unit_cost_byn * max($item->qty_arrived, 0) + $item->allocated_expenses_byn,
                2
            );
            $item->save(false);
        }

        $this->recalculateTotals();
        $this->save(false);
    }

    public function addItem(array $data): ReceivingItem
    {
        $item = new ReceivingItem();
        $item->receiving_id     = $this->id;
        $item->product_id       = (int)$data['product_id'];
        $item->size_id          = isset($data['size_id']) ? (int)$data['size_id'] : null;
        $item->qty_expected     = (int)($data['qty_expected'] ?? 1);
        $item->unit_cost_source = (float)($data['unit_cost_source'] ?? 0);
        $item->source_currency  = strtoupper($data['source_currency'] ?? 'BYN');
        $item->exchange_rate    = (float)($data['exchange_rate'] ?? 1);
        $item->unit_cost_byn    = round($item->unit_cost_source * $item->exchange_rate, 2);
        $item->notes            = $data['notes'] ?? null;
        $item->save(false);

        $this->redistributeExpenses();

        return $item;
    }

    public function transitionTo(string $newStatus, ?string $comment = null): bool
    {
        if (!$this->canTransitionTo($newStatus)) {
            return false;
        }

        $oldStatus    = $this->status;
        $this->status = $newStatus;

        if ($newStatus === self::STATUS_ARRIVED) {
            $this->arrived_date = date('Y-m-d H:i:s');
        }
        if (in_array($newStatus, [self::STATUS_ACCEPTED, self::STATUS_PARTIAL])) {
            $this->accepted_date     = date('Y-m-d H:i:s');
            $this->receiver_user_id  = Yii::$app->user->id ?? null;
        }

        if (!$this->save(false)) {
            return false;
        }

        $history              = new ReceivingHistory();
        $history->receiving_id = $this->id;
        $history->from_status = $oldStatus;
        $history->to_status   = $newStatus;
        $history->comment     = $comment;
        $history->changed_by  = Yii::$app->user->id ?? null;
        $history->changed_at  = time();
        $history->save(false);

        return true;
    }
}
