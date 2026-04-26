<?php

namespace app\backend\modules\admin\controllers;

use Yii;
use yii\web\Response;
use app\backend\modules\common\models\Feedback;

class FeedbackController extends BaseAdminController
{
    public function actionIndex()
    {
        $status = Yii::$app->request->get('status', '');
        $query  = Feedback::find()->orderBy(['created_at' => SORT_DESC]);
        if ($status !== '') {
            $query->andWhere(['status' => $status]);
        }
        $feedbacks = $query->all();

        // Count by status for badges
        $counts = [
            'new'     => (int)Feedback::find()->where(['status' => Feedback::STATUS_NEW])->count(),
            'read'    => (int)Feedback::find()->where(['status' => Feedback::STATUS_READ])->count(),
            'replied' => (int)Feedback::find()->where(['status' => Feedback::STATUS_REPLIED])->count(),
        ];

        return $this->render('index', [
            'feedbacks'    => $feedbacks,
            'activeStatus' => $status,
            'counts'       => $counts,
        ]);
    }

    public function actionReply(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id        = (int)Yii::$app->request->post('id', 0);
        $replyText = trim(Yii::$app->request->post('reply_text', ''));

        if (!$id || !$replyText) {
            return ['success' => false, 'message' => 'Заполните текст ответа'];
        }

        $fb = Feedback::findOne($id);
        if (!$fb) {
            return ['success' => false, 'message' => 'Сообщение не найдено'];
        }

        // Send email if customer email is available
        if ($fb->customer_email) {
            try {
                Yii::$app->mailer->compose()
                    ->setTo($fb->customer_email)
                    ->setSubject('Ответ на ваш отзыв')
                    ->setTextBody($replyText)
                    ->send();
            } catch (\Exception $e) {
                Yii::warning('Feedback reply email error: ' . $e->getMessage(), 'feedback');
            }
        }

        $fb->reply_text  = $replyText;
        $fb->replied_at  = time();
        $fb->status      = Feedback::STATUS_REPLIED;
        $fb->is_read     = 1;
        $fb->save(false);

        return ['success' => true, 'message' => 'Ответ отправлен'];
    }

    public function actionDelete(int $id)
    {
        Feedback::findOne($id)?->delete();
        return $this->redirect(['feedback/index']);
    }
}
