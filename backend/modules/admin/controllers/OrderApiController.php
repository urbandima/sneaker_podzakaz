<?php

/**
 * OrderApiController — AJAX endpoints для карточки заказа
 *
 * B5.5: Заметки
 * B5.7: История изменений
 *
 * CMP-462: actionUpdateField/actionChangeStatus удалены — дублировали живые
 * OrderController::actionUpdateField/actionChangeStatus (реальные колонки
 * full_address/china_track_number, проверка переходов через
 * OrderStateMachine), но не имели ни одного вызова из UI/JS во всём
 * репозитории и были сломаны схемным рассинхроном с order_history (падали
 * 500 на каждом вызове, при этом статус уже успевал сохраниться — см.
 * докрепорт docs/route-audit/CMP-456-report.md).
 */

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\backend\modules\checkout\models\Order;
use app\backend\modules\checkout\models\OrderHistory;

class OrderApiController extends BaseAdminController
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'add-note' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * B5.5: Добавление заметки к заказу
     */
    public function actionAddNote($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($id);
        if (!$order) {
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        // CMP-465: тот же logist-scoping, что и в admin/OrderController::actionView —
        // без него любой залогиненный сотрудник мог добавить заметку к чужому заказу.
        if ($this->isLogist() && $order->assigned_logist != Yii::$app->user->id) {
            Yii::warning('Попытка добавить заметку к чужому заказу: пользователь #' . Yii::$app->user->id . ' к заказу #' . $id, 'security');
            return ['success' => false, 'message' => 'Заказ не найден'];
        }

        $note = Yii::$app->request->post('note');
        if (empty($note)) {
            return ['success' => false, 'message' => 'Заметка не может быть пустой'];
        }

        // Сохраняем в историю как комментарий
        // CMP-456: same schema mismatch as logChange() below — order_history has no
        // `status`/`created_by` columns, so this threw UnknownPropertyException and
        // 500'd on every call; no note was ever written.
        $history = new OrderHistory();
        $history->order_id   = $order->id;
        $history->action     = 'note_added';
        $history->new_status = $order->status;
        $history->comment    = $note;
        $history->changed_by = Yii::$app->user->id;
        $history->created_at = time();

        if ($history->save(false)) {
            return [
                'success' => true,
                'message' => 'Заметка добавлена',
                'note' => $note,
                'date' => Yii::$app->formatter->asDatetime(time(), 'short'),
                'user' => Yii::$app->user->identity->username ?? 'Admin'
            ];
        }

        return ['success' => false, 'message' => 'Ошибка сохранения'];
    }

    /**
     * B5.7: Получение истории изменений заказа
     */
    public function actionHistory($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $order = Order::findOne($id);
        if (!$order) {
            return ['history' => []];
        }

        // CMP-465: тот же logist-scoping, что и в admin/OrderController::actionView.
        if ($this->isLogist() && $order->assigned_logist != Yii::$app->user->id) {
            Yii::warning('Попытка просмотреть историю чужого заказа: пользователь #' . Yii::$app->user->id . ' к заказу #' . $id, 'security');
            return ['history' => []];
        }

        $history = OrderHistory::find()
            ->where(['order_id' => $id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($history as $h) {
            // CMP-430/H: у модели нет ни `status`/`getStatusLabel()`, ни связи
            // `creator` — реальные поля денормализованы (new_status/user_name),
            // из-за чего этот action падал c UnknownPropertyException на любом
            // заказе с непустой историей.
            $result[] = [
                'id' => $h->id,
                'status' => $h->new_status,
                'status_label' => $h->new_status ? $h->getNewStatusLabel() : $h->getActionLabel(),
                'comment' => $h->comment,
                'created_at' => Yii::$app->formatter->asDatetime($h->created_at, 'short'),
                'created_by' => $h->user_name ?: 'Система',
            ];
        }

        return ['history' => $result];
    }
}
