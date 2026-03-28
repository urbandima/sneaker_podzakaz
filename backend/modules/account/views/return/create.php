<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\backend\modules\checkout\models\Order $order */

$this->title = 'Оформить возврат по заказу #' . ($order->order_number ?? $order->id);
$this->params['breadcrumbs'][] = ['label' => 'Мои возвраты', 'url' => ['/account/returns']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="return-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger">
        <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
    </div>
    <?php endif; ?>

    <?php $form = \yii\widgets\ActiveForm::begin([
        'id' => 'return-create-form',
        'method' => 'post',
    ]); ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Информация о заказе</h5>
        </div>
        <div class="card-body">
            <p><strong>Заказ:</strong> #<?= Html::encode($order->order_number ?? $order->id) ?></p>
            <p><strong>Дата:</strong> <?= Yii::$app->formatter->asDate($order->created_at, 'dd.MM.yyyy') ?></p>
            <p><strong>Сумма:</strong> <?= Yii::$app->formatter->asCurrency($order->total_amount, 'BYN') ?></p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Выберите товары для возврата</h5>
        </div>
        <div class="card-body">
            <?php foreach ($order->items as $item): ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox"
                    name="items[<?= $item->id ?>][selected]"
                    value="1"
                    id="item_<?= $item->id ?>">
                <label class="form-check-label" for="item_<?= $item->id ?>">
                    <?= Html::encode($item->product_name ?? 'Товар #' . $item->id) ?>
                    <?php if ($item->size): ?>
                        (размер: <?= Html::encode($item->size) ?>)
                    <?php endif; ?>
                    — <?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?>
                    × <?= $item->quantity ?> шт.
                </label>
                <input type="hidden"
                    name="items[<?= $item->id ?>][quantity]"
                    value="<?= $item->quantity ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Причина возврата</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Причина <span class="text-danger">*</span></label>
                <select class="form-select" name="reason" required>
                    <option value="">— Выберите причину —</option>
                    <option value="defect">Брак / дефект товара</option>
                    <option value="wrong_size">Не подошёл размер</option>
                    <option value="wrong_item">Прислали не тот товар</option>
                    <option value="not_as_described">Не соответствует описанию</option>
                    <option value="changed_mind">Передумал(а)</option>
                    <option value="other">Другая причина</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Комментарий</label>
                <textarea class="form-control" name="comment" rows="4"
                    placeholder="Опишите подробнее причину возврата..."></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle"></i> Оформить возврат
        </button>
        <a href="<?= Url::to(['/account/returns']) ?>" class="btn btn-outline-secondary">
            Отмена
        </a>
    </div>

    <?php \yii\widgets\ActiveForm::end(); ?>
</div>
