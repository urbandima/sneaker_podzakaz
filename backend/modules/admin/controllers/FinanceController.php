<?php
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\db\Query;
use app\backend\modules\finance\models\Payment;
use app\backend\modules\finance\models\Expense;
use app\backend\modules\checkout\models\Order;

class FinanceController extends BaseAdminController
{
    protected bool $adminOnly   = false;
    protected bool $financeOnly = true;

    public function actionIndex()
    {
        return $this->redirect(['/admin/finance/payments']);
    }

    // ─── Payments ─────────────────────────────────────────────────────────────

    public function actionPayments()
    {
        $filterStatus = Yii::$app->request->get('status', '');
        $filterMethod = Yii::$app->request->get('method', '');
        $filterFrom   = Yii::$app->request->get('date_from', '');
        $filterTo     = Yii::$app->request->get('date_to', '');

        $query = Payment::find()->orderBy(['created_at' => SORT_DESC]);

        if ($filterStatus) $query->andWhere(['status' => $filterStatus]);
        if ($filterMethod) $query->andWhere(['payment_method' => $filterMethod]);
        if ($filterFrom)   $query->andWhere(['>=', 'created_at', $filterFrom . ' 00:00:00']);
        if ($filterTo)     $query->andWhere(['<=', 'created_at', $filterTo   . ' 23:59:59']);

        $payments = $query->with(['order'])->limit(200)->all();

        // A11: 'pending' = unpaid orders sum (orders awaiting payment), not just Payment records
        $unpaidStatuses = ['new', 'awaiting_payment', 'confirmed', 'pending_payment'];
        $pendingFromOrders = (float) Order::find()
            ->where(['status' => $unpaidStatuses])
            ->andWhere(['!=', 'status', 'imported_invalid'])
            ->sum('total_amount') ?: 0;

        $totals = [
            'confirmed' => (float) Payment::find()->where(['status' => Payment::STATUS_CONFIRMED])->sum('amount') ?: 0,
            'pending'   => $pendingFromOrders,
        ];

        return $this->render('payments', [
            'payments'     => $payments,
            'totals'       => $totals,
            'filterStatus' => $filterStatus,
            'filterMethod' => $filterMethod,
            'filterFrom'   => $filterFrom,
            'filterTo'     => $filterTo,
            'statuses'     => Payment::getStatuses(),
            'methods'      => Payment::getMethods(),
        ]);
    }

    public function actionCreatePayment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        $payment = new Payment();
        $payment->order_id      = $data['order_id']      ? (int)$data['order_id'] : null;
        $payment->customer_id   = $data['customer_id']   ? (int)$data['customer_id'] : null;
        $payment->amount        = (float)($data['amount'] ?? 0);
        $payment->currency      = $data['currency']       ?? 'BYN';
        $payment->payment_method = $data['payment_method'] ?? null;
        $payment->status        = $data['status']         ?? Payment::STATUS_PENDING;
        $payment->bank_reference = $data['bank_reference'] ?? null;
        $payment->description   = $data['description']    ?? null;

        if ($payment->status === Payment::STATUS_CONFIRMED) {
            $payment->confirmed_by = Yii::$app->user->id;
            $payment->confirmed_at = date('Y-m-d H:i:s');
        }

        if ($payment->save()) {
            return ['success' => true, 'id' => $payment->id];
        }
        return ['success' => false, 'errors' => $payment->errors];
    }

    public function actionConfirmPayment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id   = (int)($data['id'] ?? 0);
        $p    = Payment::findOne($id);
        if (!$p) return ['success' => false, 'message' => 'Не найдено'];

        $p->status       = Payment::STATUS_CONFIRMED;
        $p->confirmed_by = Yii::$app->user->id;
        $p->confirmed_at = date('Y-m-d H:i:s');
        $p->updated_at   = date('Y-m-d H:i:s');
        $p->save(false);

        return ['success' => true];
    }

    // ─── Expenses ─────────────────────────────────────────────────────────────

    public function actionExpenses()
    {
        $filterCat  = Yii::$app->request->get('category', '');
        $filterFrom = Yii::$app->request->get('date_from', '');
        $filterTo   = Yii::$app->request->get('date_to', '');

        $query = Expense::find()->orderBy(['created_at' => SORT_DESC]);
        if ($filterCat)  $query->andWhere(['category' => $filterCat]);
        if ($filterFrom) $query->andWhere(['>=', 'created_at', $filterFrom . ' 00:00:00']);
        if ($filterTo)   $query->andWhere(['<=', 'created_at', $filterTo   . ' 23:59:59']);

        $expenses = $query->limit(500)->all();

        // Totals by category
        $byCategory = (new Query())
            ->select(['category', 'SUM(amount) as total'])
            ->from('expense')
            ->groupBy('category')
            ->indexBy('category')
            ->column();

        return $this->render('expenses', [
            'expenses'   => $expenses,
            'byCategory' => $byCategory,
            'filterCat'  => $filterCat,
            'filterFrom' => $filterFrom,
            'filterTo'   => $filterTo,
            'categories' => Expense::getCategories(),
        ]);
    }

    public function actionCreateExpense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();

        $expense = new Expense();
        $expense->category        = $data['category']        ?? Expense::CAT_OTHER;
        $expense->order_id        = $data['order_id']        ? (int)$data['order_id'] : null;
        $expense->supplier_id     = $data['supplier_id']     ? (int)$data['supplier_id'] : null;
        $expense->amount          = (float)($data['amount']  ?? 0);
        $expense->currency        = $data['currency']        ?? 'BYN';
        $expense->amount_original = $data['amount_original'] ? (float)$data['amount_original'] : null;
        $expense->currency_original = $data['currency_original'] ?? null;
        $expense->exchange_rate   = $data['exchange_rate']   ? (float)$data['exchange_rate'] : null;
        $expense->description     = $data['description']     ?? null;
        $expense->document_number = $data['document_number'] ?? null;
        $expense->created_by      = Yii::$app->user->id;

        if ($expense->save()) {
            return ['success' => true, 'id' => $expense->id];
        }
        return ['success' => false, 'errors' => $expense->errors];
    }

    // ─── P&L ──────────────────────────────────────────────────────────────────

    public function actionPnl()
    {
        $year  = (int) Yii::$app->request->get('year',  date('Y'));
        $years = $this->getAvailableYears();

        // Revenue from orders by month (non-cancelled, non-imported)
        $revenueRows = (new Query())
            ->select(['MONTH(FROM_UNIXTIME(created_at)) as month', 'SUM(total_amount) as total'])
            ->from('`order`')
            ->where(['NOT IN', 'status', ['canceled', 'cancelled', 'imported']])
            ->andWhere(['YEAR(FROM_UNIXTIME(created_at))' => $year])
            ->groupBy('month')
            ->indexBy('month')
            ->all();

        // Confirmed payments by month
        $paymentRows = (new Query())
            ->select(['MONTH(created_at) as month', 'SUM(amount) as total'])
            ->from('payment')
            ->where(['status' => Payment::STATUS_CONFIRMED])
            ->andWhere(['YEAR(created_at)' => $year])
            ->groupBy('month')
            ->indexBy('month')
            ->all();

        // Expenses by category and month
        $expenseRows = (new Query())
            ->select(['MONTH(created_at) as month', 'category', 'SUM(amount) as total'])
            ->from('expense')
            ->where(['YEAR(created_at)' => $year])
            ->groupBy(['month', 'category'])
            ->all();

        // Build month→category→amount map
        $expMap = [];
        foreach ($expenseRows as $row) {
            $expMap[$row['month']][$row['category']] = (float)$row['total'];
        }

        // Delivery cost from orders (delivery_cost field)
        $deliveryRows = (new Query())
            ->select(['MONTH(FROM_UNIXTIME(created_at)) as month', 'SUM(delivery_cost) as total'])
            ->from('`order`')
            ->where(['NOT IN', 'status', ['canceled', 'cancelled', 'imported']])
            ->andWhere(['YEAR(FROM_UNIXTIME(created_at))' => $year])
            ->groupBy('month')
            ->indexBy('month')
            ->all();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenue  = (float)($revenueRows[$m]['total']  ?? 0);
            $cogs     = (float)($expMap[$m][Expense::CAT_PURCHASE] ?? 0);
            $delChina = (float)($expMap[$m][Expense::CAT_DELIVERY_CHINA] ?? 0);
            $customs  = (float)($expMap[$m][Expense::CAT_CUSTOMS] ?? 0);
            $delLocal = (float)(($expMap[$m][Expense::CAT_DELIVERY_LOCAL] ?? 0)
                             + ($deliveryRows[$m]['total'] ?? 0));
            $rent     = (float)($expMap[$m][Expense::CAT_RENT]   ?? 0);
            $salary   = (float)($expMap[$m][Expense::CAT_SALARY] ?? 0);
            $other    = (float)($expMap[$m][Expense::CAT_OTHER]  ?? 0);

            $gross  = $revenue - $cogs;
            $totalExp = $delChina + $customs + $delLocal + $rent + $salary + $other;
            $net    = $gross - $totalExp;
            $margin = $revenue > 0 ? round($net / $revenue * 100, 1) : 0;

            $months[$m] = compact(
                'revenue', 'cogs', 'gross', 'delChina', 'customs',
                'delLocal', 'rent', 'salary', 'other', 'net', 'margin'
            );
        }

        return $this->render('pnl', compact('months', 'year', 'years'));
    }

    // ─── Margin reports ───────────────────────────────────────────────────────

    public function actionMargin()
    {
        $tab  = Yii::$app->request->get('tab', 'product');
        $from = Yii::$app->request->get('from', date('Y-m-01'));
        $to   = Yii::$app->request->get('to',   date('Y-m-d'));

        $fromTs = strtotime($from);
        $toTs   = strtotime($to . ' 23:59:59');

        if ($tab === 'manager') {
            $data = $this->getMarginByManager($fromTs, $toTs);
        } else {
            $data = $this->getMarginByProduct($fromTs, $toTs);
        }

        return $this->render('margin', compact('tab', 'from', 'to', 'data'));
    }

    private function getMarginByProduct(int $from, int $to): array
    {
        $rows = (new Query())
            ->select([
                'oi.product_name',
                'COUNT(DISTINCT o.id) as order_count',
                'SUM(oi.quantity) as qty',
                'SUM(oi.total) as revenue',
            ])
            ->from('order_item oi')
            ->innerJoin('`order` o', 'o.id = oi.order_id')
            ->where(['NOT IN', 'o.status', ['canceled', 'cancelled', 'imported']])
            ->andWhere(['BETWEEN', 'o.created_at', $from, $to])
            ->groupBy('oi.product_name')
            ->orderBy(['revenue' => SORT_DESC])
            ->limit(100)
            ->all();

        // Attach COGS from expenses linked to order
        $expCogs = (new Query())
            ->select(['e.order_id', 'SUM(e.amount) as cogs'])
            ->from('expense e')
            ->where(['e.category' => Expense::CAT_PURCHASE])
            ->andWhere(['IS NOT', 'e.order_id', null])
            ->groupBy('e.order_id')
            ->indexBy('order_id')
            ->all();

        foreach ($rows as &$row) {
            $row['revenue'] = (float)$row['revenue'];
            // No per-product COGS linkage yet — show revenue only
            $row['cogs']   = 0.0;
            $row['margin'] = $row['revenue'];
            $row['margin_pct'] = 100.0;
        }
        unset($row);

        return $rows;
    }

    private function getMarginByManager(int $from, int $to): array
    {
        $rows = (new Query())
            ->select([
                'u.username as manager',
                'COUNT(o.id) as orders',
                'SUM(o.total_amount) as revenue',
                'SUM(o.delivery_cost) as delivery_cost',
            ])
            ->from('`order` o')
            ->leftJoin('user u', 'u.id = o.created_by')
            ->where(['NOT IN', 'o.status', ['canceled', 'cancelled', 'imported']])
            ->andWhere(['BETWEEN', 'o.created_at', $from, $to])
            ->groupBy('o.created_by')
            ->orderBy(['revenue' => SORT_DESC])
            ->all();

        foreach ($rows as &$row) {
            $row['revenue']       = (float)$row['revenue'];
            $row['delivery_cost'] = (float)$row['delivery_cost'];
            $row['manager']       = $row['manager'] ?? 'Система';
            // Estimated margin: revenue - delivery as proxy
            $row['margin']     = $row['revenue'] - $row['delivery_cost'];
            $row['margin_pct'] = $row['revenue'] > 0
                ? round($row['margin'] / $row['revenue'] * 100, 1)
                : 0;
        }
        unset($row);

        return $rows;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getAvailableYears(): array
    {
        $minYear = (int)((new Query())->from('`order`')->min('YEAR(FROM_UNIXTIME(created_at))') ?? date('Y'));
        $maxYear = (int) date('Y');
        $years = [];
        for ($y = max($minYear, $maxYear - 3); $y <= $maxYear; $y++) {
            $years[] = $y;
        }
        return $years ?: [date('Y')];
    }
}
