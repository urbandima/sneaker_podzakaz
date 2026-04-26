<?php
namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\data\Pagination;
use app\backend\modules\procurement\models\Supplier;
use app\backend\modules\procurement\models\PurchaseOrder;
use app\backend\modules\procurement\models\PurchaseOrderItem;
use app\backend\modules\procurement\models\SupplierReturn;
use app\backend\modules\procurement\models\SupplierReturnItem;
use app\backend\modules\finance\models\Expense;
use app\backend\modules\catalog\models\Brand;

class ProcurementController extends BaseAdminController
{
    protected bool $procureOnly = true;

    // ─── Suppliers ────────────────────────────────────────────────────────────

    public function actionSuppliers()
    {
        // Load all suppliers (filtering done client-side in PHP render)
        $suppliers = Supplier::find()->orderBy(['name' => SORT_ASC])->all();

        // All brands for filter chips and brand display
        $allBrands = Brand::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();

        return $this->render('suppliers', [
            'suppliers' => $suppliers,
            'allBrands' => $allBrands,
        ]);
    }

    public function actionSupplier(int $id)
    {
        $supplier = Supplier::findOne($id);
        if (!$supplier) throw new NotFoundHttpException('Поставщик не найден.');

        $allBrands = Brand::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();

        return $this->render('supplier-view', [
            'supplier'  => $supplier,
            'allBrands' => $allBrands,
        ]);
    }

    public function actionSupplierSave()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id   = (int)($data['id'] ?? 0);

        $supplier = $id ? Supplier::findOne($id) : new Supplier();
        if (!$supplier) return ['success' => false, 'message' => 'Не найдено'];

        // Only update fields that were explicitly sent
        if (array_key_exists('name', $data))           $supplier->name           = $data['name'];
        if (array_key_exists('contact_person', $data)) $supplier->contact_person = $data['contact_person'] ?: null;
        if (array_key_exists('phone', $data))          $supplier->phone          = $data['phone'] ?: null;
        if (array_key_exists('email', $data))          $supplier->email          = $data['email'] ?: null;
        if (array_key_exists('address', $data))        $supplier->address        = $data['address'] ?: null;
        if (array_key_exists('country', $data))        $supplier->country        = $data['country'] ?: null;
        if (array_key_exists('region', $data))         $supplier->region         = $data['region'] ?: null;
        if (array_key_exists('payment_terms', $data))  $supplier->payment_terms  = $data['payment_terms'] ?: null;
        if (array_key_exists('notes', $data))          $supplier->notes          = $data['notes'] ?: null;
        if (array_key_exists('brands', $data))         $supplier->brands         = $data['brands'] ?: null;
        if (array_key_exists('contract_type', $data))  $supplier->contract_type  = $data['contract_type'] ?: null;
        if (array_key_exists('contract_terms', $data)) $supplier->contract_terms = $data['contract_terms'] ?: null;
        if (array_key_exists('is_active', $data))      $supplier->is_active      = (int)$data['is_active'];

        // New supplier defaults
        if (!$id) {
            if (empty($supplier->name)) return ['success' => false, 'errors' => ['name' => ['Обязательное поле']]];
            $supplier->is_active = $supplier->is_active ?? 1;
        }

        if ($supplier->save()) {
            return ['success' => true, 'id' => $supplier->id];
        }
        return ['success' => false, 'errors' => $supplier->errors];
    }

    public function actionSupplierDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id   = (int)($data['id'] ?? 0);
        $s    = Supplier::findOne($id);
        if (!$s) return ['success' => false, 'message' => 'Не найдено'];
        $s->delete();
        return ['success' => true];
    }

    // ─── Purchase Orders ──────────────────────────────────────────────────────

    public function actionIndex()
    {
        $filterStatus = Yii::$app->request->get('status', '');
        $filterType   = Yii::$app->request->get('type',   '');

        $query = PurchaseOrder::find()
            ->with(['supplier', 'items'])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($filterStatus) $query->andWhere(['status' => $filterStatus]);
        if ($filterType)   $query->andWhere(['order_type' => $filterType]);

        $orders    = $query->limit(200)->all();
        $suppliers = Supplier::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();

        return $this->render('index', [
            'orders'       => $orders,
            'suppliers'    => $suppliers,
            'filterStatus' => $filterStatus,
            'filterType'   => $filterType,
            'statuses'     => PurchaseOrder::getStatuses(),
            'types'        => PurchaseOrder::getTypes(),
        ]);
    }

    public function actionCreate()
    {
        $suppliers = Supplier::find()->where(['is_active' => 1])->orderBy(['name' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $po   = new PurchaseOrder();
            $po->purchase_number = $post['purchase_number'] ?? PurchaseOrder::generateNumber();
            $po->supplier_id     = (int)($post['supplier_id'] ?? 0);
            $po->order_type      = $post['order_type']     ?? PurchaseOrder::TYPE_STOCK;
            $po->status          = $post['status']         ?? PurchaseOrder::STATUS_DRAFT;
            $po->exchange_rate   = $post['exchange_rate']  ? (float)$post['exchange_rate'] : null;
            $po->order_id        = $post['order_id']       ? (int)$post['order_id'] : null;
            $po->notes           = $post['notes']          ?? null;
            $po->ordered_at      = $post['ordered_at']     ?: null;
            $po->created_by      = Yii::$app->user->id;

            if ($po->save()) {
                // Save items
                $names = $post['item_name']     ?? [];
                $sizes = $post['item_size']      ?? [];
                $qtys  = $post['item_qty']       ?? [];
                $cnys  = $post['item_price_cny'] ?? [];
                $byns  = $post['item_price_byn'] ?? [];

                foreach ($names as $i => $name) {
                    if (empty($name)) continue;
                    $item = new PurchaseOrderItem();
                    $item->purchase_order_id = $po->id;
                    $item->product_name      = $name;
                    $item->size              = $sizes[$i] ?? null;
                    $item->quantity          = (int)($qtys[$i] ?? 1);
                    $item->price_cny         = $cnys[$i] ? (float)$cnys[$i] : null;
                    $item->price_byn         = $byns[$i] ? (float)$byns[$i] : null;
                    $item->received_quantity = 0;
                    $item->save(false);
                }

                $po->recalcTotals();
                $po->save(false);

                $this->flashSuccess('Закупка создана.');
                return $this->redirect(['/admin/procurement/view', 'id' => $po->id]);
            }
            $this->flashError('Ошибка: ' . implode(', ', $po->getFirstErrors()));
        }

        $po = new PurchaseOrder([
            'purchase_number' => PurchaseOrder::generateNumber(),
            'order_type'      => PurchaseOrder::TYPE_STOCK,
            'status'          => PurchaseOrder::STATUS_DRAFT,
        ]);

        return $this->render('create', compact('po', 'suppliers'));
    }

    public function actionView(int $id)
    {
        $po = $this->findPo($id);
        return $this->render('view', ['po' => $po]);
    }

    public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data   = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id     = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $po     = PurchaseOrder::findOne($id);
        if (!$po) return ['success' => false, 'message' => 'Не найдено'];

        $po->status = $status;
        if ($status === PurchaseOrder::STATUS_RECEIVED) {
            $po->received_at = date('Y-m-d H:i:s');
        } elseif ($status === PurchaseOrder::STATUS_ORDERED) {
            $po->ordered_at = date('Y-m-d H:i:s');
        }
        $po->save(false);
        return ['success' => true, 'status_label' => $po->getStatusLabel()];
    }

    // ─── Receiving (приёмка) ──────────────────────────────────────────────────

    public function actionReceiving()
    {
        $query = PurchaseOrder::find()
            ->where(['IN', 'status', [PurchaseOrder::STATUS_ORDERED, PurchaseOrder::STATUS_TRANSIT]])
            ->orderBy(['ordered_at' => SORT_ASC]);

        $pagination = new Pagination([
            'totalCount' => $query->count(),
            'pageSize'   => 50,
            'pageSizeParam' => false,
        ]);

        $orders = $query
            ->with(['supplier', 'items'])
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        return $this->render('receiving', [
            'orders'     => $orders,
            'pagination' => $pagination,
        ]);
    }

    public function actionReceiveItems()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data  = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $poId  = (int)($data['purchase_order_id'] ?? 0);
        $items = $data['items'] ?? []; // [{id: N, received_qty: N}, ...]

        $po = PurchaseOrder::findOne($poId);
        if (!$po) return ['success' => false, 'message' => 'Закупка не найдена'];

        foreach ($items as $entry) {
            $item = PurchaseOrderItem::findOne((int)$entry['id']);
            if ($item && $item->purchase_order_id === $po->id) {
                $item->received_quantity = (int)$entry['received_qty'];
                $item->save(false);
            }
        }

        $pct = $po->getReceivedPercent();
        if ($pct >= 100) {
            $po->status      = PurchaseOrder::STATUS_RECEIVED;
            $po->received_at = date('Y-m-d H:i:s');
            $po->save(false);
        }

        return ['success' => true, 'received_pct' => $pct];
    }

    // ─── Supplier Returns ─────────────────────────────────────────────────────

    public function actionReturns()
    {
        $filterStatus = Yii::$app->request->get('status', '');

        $query = SupplierReturn::find()
            ->with(['supplier', 'purchaseOrder', 'items'])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($filterStatus) $query->andWhere(['status' => $filterStatus]);

        $returns = $query->limit(200)->all();

        return $this->render('returns', [
            'returns'      => $returns,
            'filterStatus' => $filterStatus,
            'statuses'     => SupplierReturn::getStatuses(),
        ]);
    }

    public function actionCreateReturn(int $purchaseOrderId)
    {
        $po = $this->findPo($purchaseOrderId);

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            $sr = new SupplierReturn();
            $sr->return_number     = $post['return_number'] ?? SupplierReturn::generateNumber();
            $sr->purchase_order_id = $po->id;
            $sr->supplier_id       = $po->supplier_id;
            $sr->status            = SupplierReturn::STATUS_DRAFT;
            $sr->reason            = $post['reason'] ?? null;
            $sr->notes             = $post['notes'] ?? null;
            $sr->created_by        = Yii::$app->user->id;

            if ($sr->save()) {
                $names  = $post['item_name']     ?? [];
                $sizes  = $post['item_size']      ?? [];
                $qtys   = $post['item_qty']        ?? [];
                $prices = $post['item_price_byn']  ?? [];
                $reasons= $post['item_reason']     ?? [];
                $poiIds = $post['item_poi_id']     ?? [];

                foreach ($names as $i => $name) {
                    if (empty($name)) continue;
                    $item = new SupplierReturnItem();
                    $item->supplier_return_id     = $sr->id;
                    $item->product_name           = $name;
                    $item->size                   = $sizes[$i] ?? null;
                    $item->quantity               = (int)($qtys[$i] ?? 1);
                    $item->price_byn              = $prices[$i] ? (float)$prices[$i] : null;
                    $item->reason                 = $reasons[$i] ?? null;
                    $item->purchase_order_item_id = $poiIds[$i] ? (int)$poiIds[$i] : null;
                    $item->save(false);
                }

                $sr->recalcTotal();
                $sr->save(false);

                $this->flashSuccess('Возврат создан.');
                return $this->redirect(['/admin/procurement/view-return', 'id' => $sr->id]);
            }
            $this->flashError('Ошибка: ' . implode(', ', $sr->getFirstErrors()));
        }

        return $this->render('create-return', [
            'po'          => $po,
            'returnModel' => new SupplierReturn([
                'return_number'     => SupplierReturn::generateNumber(),
                'purchase_order_id' => $po->id,
                'supplier_id'       => $po->supplier_id,
            ]),
            'reasons'     => SupplierReturn::getReasons(),
        ]);
    }

    public function actionViewReturn(int $id)
    {
        $return = $this->findReturn($id);
        return $this->render('view-return', ['return' => $return]);
    }

    public function actionUpdateReturnStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data   = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id     = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';
        $sr     = SupplierReturn::findOne($id);
        if (!$sr) return ['success' => false, 'message' => 'Не найдено'];

        $oldStatus  = $sr->status;
        $sr->status = $status;
        $sr->save(false);

        // When refunded: create negative expense (income from refund)
        if ($status === SupplierReturn::STATUS_REFUNDED && $oldStatus !== SupplierReturn::STATUS_REFUNDED
            && $sr->total_amount > 0) {
            $expense = new Expense();
            $expense->category    = Expense::CAT_OTHER;
            $expense->amount      = -$sr->total_amount;
            $expense->currency    = 'BYN';
            $expense->supplier_id = $sr->supplier_id;
            $expense->description = 'Возврат от поставщика: ' . $sr->return_number;
            $expense->created_by  = Yii::$app->user->id;
            $expense->save(false);
        }

        return ['success' => true, 'status_label' => $sr->getStatusLabel()];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function findReturn(int $id): SupplierReturn
    {
        $sr = SupplierReturn::find()->with(['supplier', 'purchaseOrder', 'items'])->where(['id' => $id])->one();
        if (!$sr) throw new NotFoundHttpException('Возврат не найден.');
        return $sr;
    }

    private function findPo(int $id): PurchaseOrder
    {
        $po = PurchaseOrder::find()->with(['supplier', 'items'])->where(['id' => $id])->one();
        if (!$po) throw new NotFoundHttpException('Закупка не найдена.');
        return $po;
    }
}
