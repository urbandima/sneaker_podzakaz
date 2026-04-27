<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\data\Pagination;
use app\backend\modules\procurement\models\Receiving;
use app\backend\modules\procurement\models\ReceivingItem;
use app\backend\modules\procurement\models\ReceivingExpense;
use app\backend\modules\procurement\models\ReceivingDocument;
use app\backend\modules\procurement\models\Supplier;
use app\backend\modules\procurement\models\Buyout;
use app\backend\modules\procurement\services\ReceivingService;
use app\backend\modules\catalog\models\Product;

class ReceivingController extends BaseAdminController
{
    // ── Index ──────────────────────────────────────────────────────────────────

    public function actionIndex()
    {
        $status    = Yii::$app->request->get('status', '');
        $supplierId= (int)Yii::$app->request->get('supplier_id', 0);
        $dateFrom  = Yii::$app->request->get('date_from', '');
        $dateTo    = Yii::$app->request->get('date_to', '');
        $search    = trim(Yii::$app->request->get('q', ''));

        $query = Receiving::find()->orderBy(['id' => SORT_DESC]);

        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        if ($supplierId) {
            $query->andWhere(['supplier_id' => $supplierId]);
        }
        if ($dateFrom) {
            $query->andWhere(['>=', 'expected_date', $dateFrom . ' 00:00:00']);
        }
        if ($dateTo) {
            $query->andWhere(['<=', 'expected_date', $dateTo . ' 23:59:59']);
        }
        if ($search) {
            $query->andWhere(['like', 'number', $search]);
        }

        // KPIs
        $kpi = [
            'in_transit' => Receiving::find()->where(['status' => Receiving::STATUS_IN_TRANSIT])->count(),
            'arrived'    => Receiving::find()->where(['status' => [Receiving::STATUS_ARRIVED, Receiving::STATUS_INSPECTING]])->count(),
            'accepted_month' => Receiving::find()
                ->where(['status' => [Receiving::STATUS_ACCEPTED, Receiving::STATUS_PARTIAL]])
                ->andWhere(['>=', 'accepted_date', date('Y-m-01')])
                ->count(),
            'total_month_byn' => (float)Receiving::find()
                ->where(['status' => [Receiving::STATUS_ACCEPTED, Receiving::STATUS_PARTIAL]])
                ->andWhere(['>=', 'accepted_date', date('Y-m-01')])
                ->sum('total_with_expenses_byn'),
        ];

        $totalCount = $query->count();
        $pagination = new Pagination(['totalCount' => $totalCount, 'pageSize' => 30]);
        $receivings = $query->offset($pagination->offset)->limit($pagination->limit)->with('supplier')->all();

        $suppliers = Supplier::find()->where(['is_active' => 1])->orderBy('name')->all();

        return $this->render('index', compact('receivings', 'pagination', 'kpi', 'suppliers',
            'status', 'supplierId', 'dateFrom', 'dateTo', 'search'));
    }

    // ── View ───────────────────────────────────────────────────────────────────

    public function actionView(int $id)
    {
        $receiving = $this->findReceiving($id);
        $receiving->refresh();

        $mode = Yii::$app->request->get('mode', 'view');

        return $this->render('view', [
            'receiving' => $receiving,
            'mode'      => $mode,
            'items'     => $receiving->items,
            'expenses'  => $receiving->expenses,
            'documents' => $receiving->documents,
            'history'   => $receiving->history,
            'suppliers' => Supplier::find()->where(['is_active' => 1])->orderBy('name')->all(),
        ]);
    }

    // ── Create ─────────────────────────────────────────────────────────────────

    /**
     * Single-screen creation: immediately create a draft and redirect to view in create mode.
     * All further editing is done via inline AJAX (same as /admin/receiving/<id>).
     */
    public function actionCreate()
    {
        $receiving = new Receiving();
        $receiving->status        = Receiving::STATUS_DRAFT;
        $receiving->number        = Receiving::generateNumber();
        $receiving->expected_date = date('Y-m-d', strtotime('+7 days'));

        if (!$receiving->save(false)) {
            Yii::$app->session->setFlash('error', 'Не удалось создать черновик приёмки');
            return $this->redirect(['/admin/receiving']);
        }

        return $this->redirect(['/admin/receiving/view', 'id' => $receiving->id, 'mode' => 'create']);
    }

    // ── Update basic fields ────────────────────────────────────────────────────

    public function actionUpdate(int $id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $receiving = $this->findReceiving($id);
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();

        $receiving->supplier_id   = isset($data['supplier_id']) ? ((int)$data['supplier_id'] ?: null) : $receiving->supplier_id;
        $receiving->expected_date = $data['expected_date'] ?? $receiving->expected_date;
        $receiving->notes         = $data['notes'] ?? $receiving->notes;

        if ($receiving->save()) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => $receiving->errors];
    }

    // ── Save single field (inline-edit) ────────────────────────────────────────

    public function actionSaveField()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data      = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $id        = (int)($data['id'] ?? 0);
        $field     = $data['field'] ?? '';
        $value     = $data['value'] ?? null;

        $allowed = ['supplier_id', 'expected_date', 'receiving_date', 'notes', 'buyout_id', 'receiver_user_id'];
        if (!in_array($field, $allowed, true)) {
            return ['success' => false, 'message' => 'Недопустимое поле'];
        }

        $receiving = $this->findReceiving($id);
        if ($field === 'supplier_id' || $field === 'buyout_id' || $field === 'receiver_user_id') {
            $value = $value ? (int)$value : null;
        }
        $receiving->$field = $value;

        if ($receiving->save(false)) {
            return ['success' => true];
        }
        return ['success' => false, 'errors' => $receiving->errors];
    }

    // ── Status change ──────────────────────────────────────────────────────────

    public function actionSetStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data      = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $id        = (int)($data['id'] ?? 0);
        $newStatus = $data['status'] ?? '';
        $comment   = $data['comment'] ?? null;

        $receiving = $this->findReceiving($id);

        if (!$receiving->canTransitionTo($newStatus)) {
            return ['success' => false, 'message' => 'Переход недопустим'];
        }

        if ($receiving->transitionTo($newStatus, $comment)) {
            return [
                'success'       => true,
                'status'        => $receiving->status,
                'status_label'  => $receiving->getStatusLabel(),
                'status_color'  => $receiving->getStatusColor(),
            ];
        }
        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }

    // ── Items ──────────────────────────────────────────────────────────────────

    public function actionAddItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();

        $receiving = $this->findReceiving((int)($data['receiving_id'] ?? 0));

        try {
            $item = $receiving->addItem($data);
            return ['success' => true, 'item_id' => $item->id, 'totals' => $this->getTotals($receiving)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionUpdateItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $item = ReceivingItem::findOne((int)($data['id'] ?? 0));
        if (!$item) return ['success' => false, 'message' => 'Не найдено'];

        foreach (['qty_arrived', 'qty_defected', 'qty_expected', 'unit_cost_source', 'unit_cost_byn', 'notes'] as $field) {
            if (isset($data[$field])) {
                $item->$field = $data[$field];
            }
        }
        $item->save(false);

        $receiving = $item->receiving;
        $receiving->redistributeExpenses();

        return ['success' => true, 'totals' => $this->getTotals($receiving)];
    }

    public function actionRemoveItem()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $item = ReceivingItem::findOne((int)($data['id'] ?? 0));
        if (!$item) return ['success' => false];

        $receiving = $item->receiving;
        $item->delete();
        $receiving->redistributeExpenses();

        return ['success' => true, 'totals' => $this->getTotals($receiving)];
    }

    // ── Expenses ───────────────────────────────────────────────────────────────

    public function actionAddExpense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();

        $receiving = $this->findReceiving((int)($data['receiving_id'] ?? 0));
        $expense = $this->saveExpense($receiving->id, $data);

        if ($expense) {
            $receiving->redistributeExpenses();
            return ['success' => true, 'expense_id' => $expense->id, 'totals' => $this->getTotals($receiving)];
        }
        return ['success' => false, 'message' => 'Ошибка сохранения расхода'];
    }

    public function actionUpdateExpense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data    = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $expense = ReceivingExpense::findOne((int)($data['id'] ?? 0));
        if (!$expense) return ['success' => false, 'message' => 'Не найдено'];

        foreach (['type', 'amount', 'currency', 'exchange_rate', 'distribution_method', 'notes'] as $field) {
            if (isset($data[$field])) {
                $expense->$field = $data[$field];
            }
        }
        $expense->save();

        $receiving = $expense->receiving;
        $receiving->redistributeExpenses();

        return ['success' => true, 'totals' => $this->getTotals($receiving)];
    }

    public function actionRemoveExpense()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data    = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $expense = ReceivingExpense::findOne((int)($data['id'] ?? 0));
        if (!$expense) return ['success' => false];

        $receiving = $expense->receiving;
        $expense->delete();
        $receiving->redistributeExpenses();

        return ['success' => true, 'totals' => $this->getTotals($receiving)];
    }

    public function actionRedistribute()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $receiving = $this->findReceiving((int)($data['id'] ?? 0));
        $receiving->redistributeExpenses();

        return ['success' => true, 'totals' => $this->getTotals($receiving)];
    }

    // ── Documents ──────────────────────────────────────────────────────────────

    public function actionUploadDocument()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $receivingId = (int)Yii::$app->request->post('receiving_id', 0);
        $receiving   = $this->findReceiving($receivingId);
        $docType     = Yii::$app->request->post('type', ReceivingDocument::TYPE_OTHER);

        $uploadedFile = UploadedFile::getInstanceByName('file');
        if (!$uploadedFile) {
            return ['success' => false, 'message' => 'Файл не загружен'];
        }

        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx'];
        $ext = strtolower($uploadedFile->extension);
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Недопустимый тип файла'];
        }

        $dir = Yii::getAlias('@app') . '/../frontend/web/uploads/receiving/' . $receivingId . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename  = uniqid('doc_') . '.' . $ext;
        $path      = $dir . $filename;
        $publicPath = 'uploads/receiving/' . $receivingId . '/' . $filename;

        if (!$uploadedFile->saveAs($path)) {
            return ['success' => false, 'message' => 'Ошибка сохранения файла'];
        }

        $doc = new ReceivingDocument();
        $doc->receiving_id  = $receivingId;
        $doc->type          = $docType;
        $doc->file_path     = $publicPath;
        $doc->original_name = $uploadedFile->name;
        $doc->mime_type     = $uploadedFile->type;
        $doc->size_bytes    = $uploadedFile->size;
        $doc->uploaded_by   = Yii::$app->user->id ?? null;
        $doc->uploaded_at   = time();
        $doc->save(false);

        return [
            'success'       => true,
            'document_id'   => $doc->id,
            'original_name' => $doc->original_name,
            'type_label'    => $doc->getTypeLabel(),
            'size'          => $doc->getFormattedSize(),
            'url'           => $doc->getPublicUrl(),
        ];
    }

    public function actionDeleteDocument()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $doc  = ReceivingDocument::findOne((int)($data['id'] ?? 0));
        if (!$doc) return ['success' => false];

        $fullPath = Yii::getAlias('@app') . '/../frontend/web/' . $doc->file_path;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $doc->delete();

        return ['success' => true];
    }

    // ── Accept ─────────────────────────────────────────────────────────────────

    public function actionAccept()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data      = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $receiving = $this->findReceiving((int)($data['id'] ?? 0));

        if (!$receiving->canTransitionTo(Receiving::STATUS_ACCEPTED) &&
            !$receiving->canTransitionTo(Receiving::STATUS_PARTIAL)) {
            return ['success' => false, 'message' => 'Нельзя принять в текущем статусе'];
        }

        try {
            ReceivingService::accept($receiving);
            return [
                'success'      => true,
                'status'       => $receiving->status,
                'status_label' => $receiving->getStatusLabel(),
            ];
        } catch (\Throwable $e) {
            Yii::error('Receiving accept error: ' . $e->getMessage(), 'receiving');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Cancel ─────────────────────────────────────────────────────────────────

    public function actionCancel()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data      = json_decode(Yii::$app->request->rawBody, true) ?: Yii::$app->request->post();
        $receiving = $this->findReceiving((int)($data['id'] ?? 0));
        $comment   = $data['comment'] ?? 'Отменена';

        if ($receiving->transitionTo(Receiving::STATUS_CANCELLED, $comment)) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Отмена недопустима'];
    }

    // ── Create from Buyout ─────────────────────────────────────────────────────

    public function actionFromBuyout(int $buyoutId)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $buyout = Buyout::findOne($buyoutId);
        if (!$buyout) {
            return ['success' => false, 'message' => 'Выкуп не найден'];
        }

        try {
            $receiving = ReceivingService::createFromBuyout($buyout);
            return [
                'success'      => true,
                'receiving_id' => $receiving->id,
                'number'       => $receiving->number,
                'url'          => \yii\helpers\Url::to(['/admin/receiving/view', 'id' => $receiving->id]),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Products autocomplete ──────────────────────────────────────────────────

    public function actionProducts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $q = trim(Yii::$app->request->get('q', ''));

        $query = Product::find()->where(['is_active' => 1])->limit(20)->orderBy('name');
        if ($q) {
            $query->andWhere(['or', ['like', 'name', $q], ['like', 'sku', $q]]);
        }

        return array_map(fn($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'sku'   => $p->sku ?? '',
            'price' => (float)$p->price,
            'sizes' => array_map(fn($s) => ['id' => $s->id, 'size' => $s->size, 'price' => (float)$s->price],
                $p->sizes ?? []),
        ], $query->all());
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function findReceiving(int $id): Receiving
    {
        $r = Receiving::findOne($id);
        if (!$r) throw new NotFoundHttpException("Приёмка #{$id} не найдена");
        return $r;
    }

    private function saveExpense(int $receivingId, array $data): ?ReceivingExpense
    {
        $expense = new ReceivingExpense();
        $expense->receiving_id        = $receivingId;
        $expense->type                = $data['type'] ?? ReceivingExpense::TYPE_OTHER;
        $expense->amount              = (float)($data['amount'] ?? 0);
        $expense->currency            = strtoupper($data['currency'] ?? 'BYN');
        $expense->exchange_rate       = (float)($data['exchange_rate'] ?? 1);
        $expense->distribution_method = $data['distribution_method'] ?? ReceivingExpense::DIST_EQUAL;
        $expense->notes               = $data['notes'] ?? null;

        return $expense->save() ? $expense : null;
    }

    private function getTotals(Receiving $receiving): array
    {
        $receiving->refresh();
        return [
            'subtotal_byn'            => (float)$receiving->subtotal_byn,
            'expenses_total_byn'      => (float)$receiving->expenses_total_byn,
            'total_with_expenses_byn' => (float)$receiving->total_with_expenses_byn,
            'total_qty_arrived'       => (int)$receiving->total_qty_arrived,
            'total_qty_expected'      => (int)$receiving->total_qty_expected,
            'total_qty_defected'      => (int)$receiving->total_qty_defected,
        ];
    }
}
