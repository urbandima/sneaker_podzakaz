<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\backend\modules\returns\models\ReturnRequest $model */
/** @var array $orders */
/** @var array $reasonList */

$this->title = 'Создать возврат';

// Подключаем стили для admin-return
$this->registerCssFile('@web/css/pages/admin-return.css');
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> К списку', ['index'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<div class="admin-card">
    <h2 class="admin-card-title"><i class="bi bi-arrow-return-left"></i> Новая заявка на возврат</h2>
    <div class="admin-card-content">
        <?php $form = ActiveForm::begin(['options' => ['class' => 'admin-form']]) ?>

        <div class="admin-form-grid">
            <div class="admin-form-column">
                <?= $form->field($model, 'order_id')->dropDownList(
                    array_combine(
                        array_column($orders, 'id'),
                        array_map(function($o) {
                            return ($o['order_number'] ?: '#' . $o['id']) . ' — ' . ($o['client_name'] ?: 'Клиент') . ' — ' . number_format($o['total_amount'], 2) . ' BYN';
                        }, $orders)
                    ),
                    ['prompt' => '— Выберите заказ —', 'class' => 'form-select', 'required' => true]
                )->label('Заказ <span class="text-danger">*</span>', ['encode' => false]) ?>

                <?= $form->field($model, 'reason')->dropDownList(
                    $reasonList,
                    ['prompt' => '— Выберите причину —', 'class' => 'form-select', 'required' => true]
                )->label('Причина возврата <span class="text-danger">*</span>', ['encode' => false]) ?>

                <?= $form->field($model, 'refund_amount')->input('number', [
                    'min' => 0, 'step' => '0.01', 'placeholder' => '0.00'
                ])->label('Сумма возврата (BYN)') ?>

                <?= $form->field($model, 'refund_method')->dropDownList([
                    'card' => 'На карту',
                    'cash' => 'Наличными',
                    'wallet' => 'Электронный кошелёк',
                    'store_credit' => 'Бонусы магазина',
                ], ['prompt' => '— Способ возврата —', 'class' => 'form-select'])->label('Способ возврата') ?>
            </div>

            <div>
                <?= $form->field($model, 'comment')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Комментарий клиента или описание ситуации...'
                ])->label('Комментарий') ?>

                <?= $form->field($model, 'admin_comment')->textarea([
                    'rows' => 4,
                    'placeholder' => 'Внутренняя заметка для команды...'
                ])->label('Заметка администратора') ?>

                <?= $form->field($model, 'pickup_address')->textInput([
                    'placeholder' => 'Адрес для забора товара'
                ])->label('Адрес забора') ?>
            </div>
        </div>

        <div class="admin-form-actions">
            <?= Html::submitButton('<i class="bi bi-check-circle"></i> Создать заявку', [
                'class' => 'admin-btn admin-btn-primary'
            ]) ?>
            <a href="<?= Url::to(['index']) ?>" class="admin-btn admin-btn-secondary">Отмена</a>
        </div>

        <?php ActiveForm::end() ?>
    </div>
</div>
