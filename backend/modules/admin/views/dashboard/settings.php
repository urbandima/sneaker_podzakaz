<?php
/** @var yii\web\View $this */
/** @var app\backend\modules\admin\models\CompanySettings $settings */
/** @var app\backend\modules\checkout\models\OrderStatus[] $statuses */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;

$this->title = 'Настройки компании';
?>

<div class="admin-settings">
    <div class="admin-page-header">
        <div class="admin-page-header-content">
            <div class="admin-page-eyebrow">Настройки</div>
            <h1 class="admin-page-title"><?= Html::encode($this->title) ?></h1>
        </div>
        <div class="admin-page-header-actions">
            <?= Html::a('← К заказам', ['/admin/order/index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="admin-grid admin-grid--2">
        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="bi bi-building"></i>
                    Реквизиты компании
                </h2>
            </div>
            <div class="admin-card-body">
                <?php $form = ActiveForm::begin([
                    'options' => ['class' => 'admin-form'],
                    'fieldConfig' => [
                        'options' => ['class' => 'admin-form-group'],
                        'labelOptions' => ['class' => 'admin-form-label'],
                        'inputOptions' => ['class' => 'admin-form-input'],
                        'errorOptions' => ['class' => 'admin-form-error']
                    ]
                ]); ?>

                <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'name')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'unp')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'bic')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'account')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                </div>

                    <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'bank')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'address')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'phone')->textInput(['class' => 'admin-form-input']) ?>
                    </div>
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'email')->textInput(['class' => 'admin-form-input', 'type' => 'email']) ?>
                    </div>
                </div>

                <div class="admin-form-row">
                    <div class="admin-form-col">
                        <?= $form->field($settings, 'offer_url')->textInput(['class' => 'admin-form-input', 'placeholder' => 'https://example.com/offer.pdf']) ?>
                        <div class="admin-form-help">Ссылка на публичную оферту (если пусто — кнопка на странице клиента будет скрыта)</div>
                    </div>
                </div>

                <div class="admin-form-actions">
                    <?= Html::submitButton('Сохранить реквизиты', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h2 class="admin-card-title">
                    <i class="bi bi-gear"></i>
                    Статусы заказов
                </h2>
            </div>
            <div class="admin-card-body">
                <form method="post" class="admin-form">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th style="width: 18%">Ключ</th>
                                        <th>Название</th>
                                        <th style="width: 15%">Порядок</th>
                                        <th style="width: 18%">Доступ логисту</th>
                                        <th style="width: 12%">Активен</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statuses as $st): ?>
                                        <tr>
                                            <td>
                                                <code class="admin-code"><?= Html::encode($st->key) ?></code>
                                            </td>
                                            <td>
                                                <input type="text" name="statuses[<?= Html::encode($st->key) ?>][label]" class="admin-form-input" value="<?= Html::encode($st->label) ?>">
                                            </td>
                                            <td>
                                                <input type="number" name="statuses[<?= Html::encode($st->key) ?>][sort]" class="admin-form-input" value="<?= (int)$st->sort ?>">
                                            </td>
                                            <td>
                                                <div class="admin-switch">
                                                    <input class="admin-switch-input" type="checkbox" name="statuses[<?= Html::encode($st->key) ?>][logist_available]" value="1" <?= $st->logist_available ? 'checked' : '' ?>>
                                                    <label class="admin-switch-label"></label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="admin-switch">
                                                    <input class="admin-switch-input" type="checkbox" name="statuses[<?= Html::encode($st->key) ?>][is_active]" value="1" <?= $st->is_active ? 'checked' : '' ?>>
                                                    <label class="admin-switch-label"></label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="admin-card admin-card--secondary">
                            <div class="admin-card-header">
                                <h3 class="admin-card-title">
                                    <i class="bi bi-plus-circle"></i>
                                    Добавить новый статус
                                </h3>
                            </div>
                            <div class="admin-card-body">
                                <div class="admin-form-row">
                                    <div class="admin-form-col">
                                        <input type="text" name="new_status[key]" class="admin-form-input" placeholder="Ключ (латиница)">
                                    </div>
                                    <div class="admin-form-col">
                                        <input type="text" name="new_status[label]" class="admin-form-input" placeholder="Название">
                                    </div>
                                    <div class="admin-form-col">
                                        <input type="number" name="new_status[sort]" class="admin-form-input" placeholder="Порядок" value="999">
                                    </div>
                                    <div class="admin-form-col">
                                        <div class="admin-switch">
                                            <input class="admin-switch-input" type="checkbox" name="new_status[logist_available]" value="1" id="new_status_logist">
                                            <label class="admin-switch-label" for="new_status_logist"></label>
                                        </div>
                                        <label for="new_status_logist" class="admin-switch-text">Логист</label>
                                    </div>
                                </div>

                                <div class="admin-form-actions">
                                    <?= Html::submitButton('Добавить статус', ['class' => 'btn btn-accent']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="admin-form-actions">
                            <?= Html::submitButton('Сохранить статусы', ['class' => 'btn btn-primary']) ?>
                        </div>
                    </form>
            </div>
        </div>
    </div>
</div>
