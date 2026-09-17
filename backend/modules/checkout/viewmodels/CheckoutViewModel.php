<?php

namespace app\backend\modules\checkout\viewmodels;

use app\backend\modules\admin\models\CompanySettings;
use app\backend\modules\checkout\models\Order;
use app\backend\shared\helpers\PriceHelper;

/**
 * Assembles checkout-page display data from Order/OrderItem so views
 * don't call ActiveRecord directly (CMP-388, CMP-371 п.3.3).
 *
 * Sample consumer: frontend/views/order/view.php via
 * frontend\controllers\OrderController::actionView().
 */
class CheckoutViewModel
{
    private const DONE_STATUSES = ['delivered', 'canceled', 'cancelled', 'returned', 'refunded'];
    private const PAID_LIKE_STATUSES = [
        'paid', 'imported', 'ordered', 'awaiting_warehouse', 'at_warehouse', 'processing',
    ];

    public readonly string $orderNumber;
    public readonly string $status;
    public readonly string $statusLabel;
    public readonly int $createdAt;
    public readonly float $totalAmount;
    public readonly string $clientName;
    public readonly string $clientPhone;
    public readonly string $clientEmail;
    public readonly ?string $deliveryAddress;

    /** @var CheckoutOrderItemView[] */
    public readonly array $items;

    public readonly bool $hasPaymentProof;
    public readonly string $token;
    public readonly bool $isAwaitingPayment;
    public readonly bool $isPassportNeeded;
    public readonly bool $isPassportComplete;

    /** @var array{recipient:string,account:string,bank:string,bic:string,unp:string,amount:string,purpose:string,allText:string}|null */
    public readonly ?array $paymentRequisites;

    public function __construct(Order $order)
    {
        $this->orderNumber = (string)$order->order_number;
        $this->status = (string)$order->status;
        $this->statusLabel = $order->getStatusLabel();
        $this->createdAt = (int)$order->created_at;
        $this->totalAmount = (float)$order->total_amount;
        $this->clientName = (string)$order->client_name;
        $this->clientPhone = (string)$order->client_phone;
        $this->clientEmail = (string)$order->client_email;
        $this->deliveryAddress = $order->delivery_address !== null && $order->delivery_address !== ''
            ? (string)$order->delivery_address
            : null;
        $this->hasPaymentProof = (bool)$order->payment_proof;
        $this->token = (string)$order->token;

        $this->items = array_map(
            static fn ($item) => new CheckoutOrderItemView(
                (string)$item->product_name,
                $item->size !== null && $item->size !== '' ? (string)$item->size : null,
                (int)$item->quantity,
                (float)$item->price
            ),
            $order->orderItems
        );

        $this->isAwaitingPayment = self::computeIsAwaitingPayment($this->status, $this->hasPaymentProof);

        $canCheckPassport = $order->hasMethod('isPassportComplete');
        $passportComplete = $canCheckPassport && $order->isPassportComplete();
        $this->isPassportComplete = $passportComplete;
        $this->isPassportNeeded = self::computeIsPassportNeeded(
            $this->status,
            $this->hasPaymentProof,
            $canCheckPassport,
            $passportComplete
        );

        $this->paymentRequisites = $this->isAwaitingPayment ? $this->buildPaymentRequisites() : null;
    }

    /**
     * Pure status-transition logic, extracted so it can be unit-tested without
     * a live DB connection (Order/OrderItem relations require one — see
     * tests/unit/CheckoutViewModelTest.php).
     */
    public static function isDoneStatus(string $status): bool
    {
        return in_array($status, self::DONE_STATUSES, true);
    }

    public static function computeIsAwaitingPayment(string $status, bool $hasPaymentProof): bool
    {
        return !$hasPaymentProof && !self::isDoneStatus($status);
    }

    public static function computeIsPassportNeeded(
        string $status,
        bool $hasPaymentProof,
        bool $canCheckPassport,
        bool $passportComplete
    ): bool {
        if (self::isDoneStatus($status) || !$canCheckPassport || $passportComplete) {
            return false;
        }

        return $hasPaymentProof || in_array($status, self::PAID_LIKE_STATUSES, true);
    }

    private function buildPaymentRequisites(): array
    {
        $settings = CompanySettings::getSettings();
        $recipient = !empty($settings['name']) ? $settings['name'] : 'ИП Коляда С.С.';
        $account = $settings['account'] ?? '';
        $bank = $settings['bank'] ?? '';
        $bic = $settings['bic'] ?? '';
        $unp = $settings['unp'] ?? '';
        $amount = PriceHelper::format($this->totalAmount);
        $purpose = 'Оплата по договору оферты №' . $this->orderNumber;

        $allText = implode("\n", array_filter([
            'Получатель: ' . $recipient,
            $account ? 'Расчётный счёт: ' . $account : '',
            $bank ? 'Банк: ' . $bank : '',
            $bic ? 'БИК: ' . $bic : '',
            $unp ? 'УНП: ' . $unp : '',
            'Сумма: ' . $amount,
            'Назначение: ' . $purpose,
        ]));

        return [
            'recipient' => $recipient,
            'account' => $account,
            'bank' => $bank,
            'bic' => $bic,
            'unp' => $unp,
            'amount' => $amount,
            'purpose' => $purpose,
            'allText' => $allText,
        ];
    }
}
