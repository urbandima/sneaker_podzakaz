<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\returns\models\ReturnRequest $model */

$this->title = 'Заявка на возврат №' . $model->return_number;
$this->params['breadcrumbs'][] = ['label' => 'Мои возвраты', 'url' => ['/account/returns']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="return-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <a href="<?= Url::to(['/account/returns']) ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Все возвраты
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Информация о заявке</h5>
            <span class="badge bg-<?= $model->getStatusClass() ?>"><?= $model->getStatusName() ?></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th>Номер заявки:</th>
                            <td><?= Html::encode($model->return_number) ?></td>
                        </tr>
                        <tr>
                            <th>Заказ:</th>
                            <td>
                                <a href="<?= Url::to(['/account/order', 'id' => $model->order_id]) ?>">
                                    #<?= $model->order->order_number ?? $model->order_id ?>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Причина возврата:</th>
                            <td><?= Html::encode($model->getReasonName()) ?></td>
                        </tr>
                        <tr>
                            <th>Сумма возврата:</th>
                            <td><?= Yii::$app->formatter->asCurrency($model->refund_amount, 'BYN') ?></td>
                        </tr>
                        <tr>
                            <th>Дата создания:</th>
                            <td><?= Yii::$app->formatter->asDate($model->created_at, 'dd.MM.yyyy') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <?php if ($model->comment) : ?>
                    <div class="mb-3">
                        <strong>Ваш комментарий:</strong>
                        <p class="text-muted"><?= Html::encode($model->comment) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($model->admin_comment) : ?>
                    <div class="alert alert-info">
                        <strong>Комментарий менеджера:</strong>
                        <p class="mb-0"><?= Html::encode($model->admin_comment) ?></p>
                    </div>
                    <?php endif; ?>

                    <?php if ($model->status === 'approved' && $model->tracking_number) : ?>
                    <div class="alert alert-success">
                        <i class="bi bi-truck"></i>
                        Трек-номер для отправки: <strong><?= Html::encode($model->tracking_number) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($model->status === 'pending') : ?>
    <div class="alert alert-warning">
        <i class="bi bi-clock-history"></i>
        Ваша заявка находится на рассмотрении. Мы свяжемся с вами в течение 1-2 рабочих дней.
    </div>
    <?php endif; ?>

    <div id="return-cancel-error" class="alert alert-danger d-none"></div>

    <a href="<?= Url::to(['/account/returns']) ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Вернуться к списку возвратов
    </a>
    <?php if ($model->status === 'pending') : ?>
    <button type="button" id="return-cancel-btn" class="btn btn-outline-danger" onclick="cancelReturnRequest(<?= (int) $model->id ?>)">
        <i class="bi bi-x-circle"></i> Отменить заявку
    </button>
    <?php endif; ?>
</div>

<?php if ($model->status === 'pending') :
$this->registerJs("
function cancelReturnRequest(id) {
    if (!confirm('Отменить заявку на возврат?')) return;

    var btn = document.getElementById('return-cancel-btn');
    var errorBox = document.getElementById('return-cancel-error');
    errorBox.classList.add('d-none');
    btn.disabled = true;

    var csrf = document.querySelector('meta[name=\"csrf-token\"]').content;

    fetch('/account/returns/' + id + '/cancel', {
        method: 'POST',
        headers: {
            'X-CSRF-Token': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            location.reload();
        } else {
            errorBox.textContent = data.message || 'Не удалось отменить заявку';
            errorBox.classList.remove('d-none');
            btn.disabled = false;
        }
    })
    .catch(function () {
        errorBox.textContent = 'Не удалось отменить заявку. Попробуйте позже.';
        errorBox.classList.remove('d-none');
        btn.disabled = false;
    });
}
", \yii\web\View::POS_END);
endif; ?>
