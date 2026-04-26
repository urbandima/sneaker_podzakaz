<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use app\backend\modules\procurement\models\Buyout;
use app\backend\modules\procurement\models\BuyoutOrderLink;
use app\backend\modules\procurement\models\BuyoutHistory;
use app\backend\modules\procurement\models\PurchaseOrder;
use app\backend\modules\procurement\services\BuyoutUrlParserService;
use app\backend\modules\procurement\services\BuyoutStatusSyncService;
use app\backend\modules\checkout\models\Order;

class BuyoutController extends BaseAdminController
{
    // ─── Index ────────────────────────────────────────────────────────────────

    public function actionIndex()
    {
        $filterStatus = Yii::$app->request->get('status', '');
        $filterSource = Yii::$app->request->get('source', '');
        $filterBuyer  = Yii::$app->request->get('buyer_user_id', '');
        $filterFrom   = Yii::$app->request->get('date_from', '');
        $filterTo     = Yii::$app->request->get('date_to', '');

        $query = Buyout::find()
            ->with(['orderLinks'])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($filterStatus) $query->andWhere(['status' => $filterStatus]);
        if ($filterSource) $query->andWhere(['source' => $filterSource]);
        if ($filterBuyer)  $query->andWhere(['buyer_user_id' => (int)$filterBuyer]);
        if ($filterFrom)   $query->andWhere(['>=', 'created_at', $filterFrom . ' 00:00:00']);
        if ($filterTo)     $query->andWhere(['<=', 'created_at', $filterTo . ' 23:59:59']);

        $all     = $query->limit(500)->all();
        $buyouts = $all;

        // KPI
        $kpi = [
            'total'        => count($all),
            'draft'        => 0,
            'ordered'      => 0,
            'in_transit'   => 0,
            'arrived'      => 0,
            'accepted'     => 0,
            'total_cost'   => 0,
        ];
        foreach ($all as $b) {
            $kpi[$b->status] = ($kpi[$b->status] ?? 0) + 1;
            $kpi['total_cost'] += (float)$b->total_cost_byn;
        }

        return $this->render('buyout/index', [
            'buyouts'      => $buyouts,
            'kpi'          => $kpi,
            'statuses'     => Buyout::getStatuses(),
            'sources'      => Buyout::getSources(),
            'filterStatus' => $filterStatus,
            'filterSource' => $filterSource,
            'filterBuyer'  => $filterBuyer,
            'filterFrom'   => $filterFrom,
            'filterTo'     => $filterTo,
        ]);
    }

    // ─── View ─────────────────────────────────────────────────────────────────

    public function actionView(int $id)
    {
        $buyout = $this->findBuyout($id);
        $orders = $buyout->getOrderLinks()->with('order')->all();

        return $this->render('buyout/view', [
            'buyout'    => $buyout,
            'links'     => $orders,
            'histories' => $buyout->histories,
            'statuses'  => Buyout::getStatuses(),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────────

    public function actionCreate()
    {
        $buyout = new Buyout([
            'source'          => Buyout::SOURCE_MANUAL,
            'status'          => Buyout::STATUS_DRAFT,
            'qty'             => 1,
            'source_currency' => 'CNY',
            'buyer_user_id'   => Yii::$app->user->id,
        ]);

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $buyout->load($post, '') || $buyout->setAttributes($post['Buyout'] ?? $post);

            // Recalc total before save
            $buyout->recalcTotal();

            if ($buyout->save()) {
                // Link orders if provided
                $orderIds = array_filter(array_map('intval', (array)($post['order_ids'] ?? [])));
                foreach ($orderIds as $oid) {
                    $link = new BuyoutOrderLink(['buyout_id' => $buyout->id, 'order_id' => $oid, 'qty' => $buyout->qty]);
                    $link->save(false);
                }

                // Log creation
                $history = new BuyoutHistory([
                    'buyout_id' => $buyout->id,
                    'user_id'   => Yii::$app->user->id,
                    'action'    => 'status_changed',
                    'old_value' => null,
                    'new_value' => ['status' => $buyout->status],
                ]);
                $history->save(false);

                $this->flashSuccess('Выкуп создан.');
                return $this->redirect(['/admin/procurement/buyout/' . $buyout->id]);
            }

            $this->flashError('Ошибка: ' . implode(', ', $buyout->getFirstErrors()));
        }

        return $this->render('buyout/create', [
            'buyout'   => $buyout,
            'statuses' => Buyout::getStatuses(),
            'sources'  => Buyout::getSources(),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────────

    public function actionUpdate(int $id)
    {
        $buyout = $this->findBuyout($id);

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $buyout->load($post, '') || $buyout->setAttributes($post['Buyout'] ?? $post);
            $buyout->recalcTotal();

            if ($buyout->save()) {
                $this->flashSuccess('Выкуп сохранён.');
                return $this->redirect(['/admin/procurement/buyout/' . $buyout->id]);
            }
            $this->flashError('Ошибка: ' . implode(', ', $buyout->getFirstErrors()));
        }

        return $this->render('buyout/create', [
            'buyout'   => $buyout,
            'statuses' => Buyout::getStatuses(),
            'sources'  => Buyout::getSources(),
        ]);
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    public function actionDelete(int $id)
    {
        $buyout = $this->findBuyout($id);
        if (!in_array($buyout->status, [Buyout::STATUS_DRAFT, Buyout::STATUS_CANCELLED], true)) {
            $this->flashError('Удалить можно только черновик или отменённый выкуп.');
            return $this->redirect(['/admin/procurement/buyout/' . $id]);
        }
        BuyoutOrderLink::deleteAll(['buyout_id' => $id]);
        BuyoutHistory::deleteAll(['buyout_id'   => $id]);
        $buyout->delete();
        $this->flashSuccess('Выкуп удалён.');
        return $this->redirect(['/admin/procurement/buyouts']);
    }

    // ─── Parse URL ────────────────────────────────────────────────────────────

    public function actionParseUrl()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $url = trim(Yii::$app->request->post('url', ''));
        if (!$url) return ['success' => false, 'message' => 'URL не указан'];

        $service = new BuyoutUrlParserService();
        $data    = $service->parse($url);

        if ($data === null) {
            return ['success' => false, 'message' => 'Не удалось распарсить URL'];
        }

        return ['success' => true, 'data' => $data];
    }

    // ─── Link / Unlink Order ──────────────────────────────────────────────────

    public function actionLinkOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data     = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $buyoutId = (int)($data['buyout_id'] ?? 0);
        $orderId  = (int)($data['order_id']  ?? 0);
        $qty      = (int)($data['qty']        ?? 1);

        $buyout = Buyout::findOne($buyoutId);
        $order  = Order::findOne($orderId);
        if (!$buyout || !$order) return ['success' => false, 'message' => 'Не найдено'];

        $existing = BuyoutOrderLink::find()
            ->where(['buyout_id' => $buyoutId, 'order_id' => $orderId, 'order_item_id' => null])
            ->one();

        if ($existing) return ['success' => false, 'message' => 'Заказ уже привязан'];

        $link             = new BuyoutOrderLink();
        $link->buyout_id  = $buyoutId;
        $link->order_id   = $orderId;
        $link->order_item_id = null;
        $link->qty        = $qty;

        if (!$link->save()) {
            return ['success' => false, 'errors' => $link->errors];
        }

        // History
        $h = new BuyoutHistory(['buyout_id' => $buyoutId, 'user_id' => Yii::$app->user->id,
            'action' => 'order_linked', 'new_value' => ['order_id' => $orderId]]);
        $h->save(false);

        return ['success' => true, 'order_number' => $order->order_number ?? $order->id];
    }

    public function actionUnlinkOrder()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data     = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $buyoutId = (int)($data['buyout_id'] ?? 0);
        $orderId  = (int)($data['order_id']  ?? 0);

        $deleted = BuyoutOrderLink::deleteAll(['buyout_id' => $buyoutId, 'order_id' => $orderId]);

        if ($deleted) {
            $h = new BuyoutHistory(['buyout_id' => $buyoutId, 'user_id' => Yii::$app->user->id,
                'action' => 'order_unlinked', 'new_value' => ['order_id' => $orderId]]);
            $h->save(false);
        }

        return ['success' => $deleted > 0];
    }

    // ─── Accept (create Receiving) ────────────────────────────────────────────

    public function actionAccept(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $buyout = $this->findBuyout($id);

        if ($buyout->status !== Buyout::STATUS_ARRIVED) {
            return ['success' => false, 'message' => 'Принять можно только выкуп со статусом "Прибыл"'];
        }

        $db = Yii::$app->db;
        $tx = $db->beginTransaction();
        try {
            // Create a PurchaseOrder as receiving record (reusing existing model)
            $po = new PurchaseOrder();
            $po->purchase_number = 'BY-RCV-' . $buyout->id;
            $po->order_type      = PurchaseOrder::TYPE_COMMISSION;
            $po->status          = PurchaseOrder::STATUS_RECEIVED;
            $po->notes           = 'Приёмка выкупа #' . $buyout->id . ': ' . $buyout->getProductName();
            $po->received_at     = date('Y-m-d H:i:s');
            $po->created_by      = Yii::$app->user->id;
            // Use dummy supplier_id=0 if no supplier — find or create placeholder
            $po->supplier_id     = 1; // will be created if needed; admin can edit
            $po->total_amount_byn = (float)$buyout->total_cost_byn;
            $po->save(false);

            $buyout->receiving_id = $po->id;
            $buyout->accepted_at  = date('Y-m-d H:i:s');
            $buyout->status       = Buyout::STATUS_ACCEPTED;
            $buyout->save(false);

            // History
            $h = new BuyoutHistory([
                'buyout_id' => $buyout->id,
                'user_id'   => Yii::$app->user->id,
                'action'    => 'receiving_created',
                'new_value' => ['purchase_order_id' => $po->id],
            ]);
            $h->save(false);

            $tx->commit();
            return ['success' => true, 'receiving_id' => $po->id, 'redirect' => '/admin/procurement/view/' . $po->id];

        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::error('BuyoutAccept error: ' . $e->getMessage(), 'buyout');
            return ['success' => false, 'message' => 'Внутренняя ошибка: ' . $e->getMessage()];
        }
    }

    // ─── Cancel ───────────────────────────────────────────────────────────────

    public function actionCancel(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $buyout = $this->findBuyout($id);
        $notes  = Yii::$app->request->post('notes', '');

        if (!$buyout->canTransitionTo(Buyout::STATUS_CANCELLED)) {
            return ['success' => false, 'message' => 'Невозможно отменить в текущем статусе'];
        }

        $old = $buyout->status;
        $buyout->status = Buyout::STATUS_CANCELLED;
        if ($notes) $buyout->notes = trim(($buyout->notes ? $buyout->notes . "\n" : '') . '[Отмена] ' . $notes);
        $buyout->save(false);

        return ['success' => true, 'status_label' => $buyout->getStatusLabel()];
    }

    // ─── Bulk Status ──────────────────────────────────────────────────────────

    public function actionBulkStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data   = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $ids    = array_map('intval', (array)($data['ids'] ?? []));
        $status = $data['status'] ?? '';

        if (empty($ids) || !array_key_exists($status, Buyout::getStatuses())) {
            return ['success' => false, 'message' => 'Некорректные данные'];
        }

        $updated = 0;
        foreach ($ids as $id) {
            $buyout = Buyout::findOne($id);
            if ($buyout && $buyout->canTransitionTo($status)) {
                $buyout->status = $status;
                if ($buyout->save(false)) {
                    $updated++;
                }
            }
        }

        return ['success' => true, 'updated' => $updated];
    }

    // ─── History (JSON) ───────────────────────────────────────────────────────

    public function actionHistory(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $buyout = $this->findBuyout($id);

        $rows = BuyoutHistory::find()
            ->where(['buyout_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(100)
            ->all();

        return array_map(fn($h) => [
            'id'         => $h->id,
            'action'     => $h->getActionLabel(),
            'old_value'  => $h->old_value,
            'new_value'  => $h->new_value,
            'created_at' => $h->created_at,
        ], $rows);
    }

    // ─── Update Status (single, AJAX) ─────────────────────────────────────────

    public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data   = json_decode(Yii::$app->request->getRawBody(), true) ?: Yii::$app->request->post();
        $id     = (int)($data['id'] ?? 0);
        $status = $data['status'] ?? '';

        $buyout = Buyout::findOne($id);
        if (!$buyout) return ['success' => false, 'message' => 'Не найдено'];

        if (!$buyout->canTransitionTo($status)) {
            return ['success' => false, 'message' => 'Переход недоступен из статуса: ' . $buyout->getStatusLabel()];
        }

        $buyout->status = $status;
        if ($status === Buyout::STATUS_ORDERED)  $buyout->ordered_at  = date('Y-m-d H:i:s');
        if ($status === Buyout::STATUS_ARRIVED)   $buyout->arrived_at  = date('Y-m-d H:i:s');
        if ($status === Buyout::STATUS_ACCEPTED)  $buyout->accepted_at = date('Y-m-d H:i:s');
        $buyout->save(false);

        return [
            'success'      => true,
            'status'       => $buyout->status,
            'status_label' => $buyout->getStatusLabel(),
            'status_color' => $buyout->getStatusColor(),
        ];
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function findBuyout(int $id): Buyout
    {
        $buyout = Buyout::find()->with(['orderLinks', 'histories'])->where(['id' => $id])->one();
        if (!$buyout) throw new NotFoundHttpException('Выкуп не найден.');
        return $buyout;
    }
}
