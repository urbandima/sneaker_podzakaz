<?php
/** @var yii\web\View $this */
/** @var app\models\CompanySettings $settings */
/** @var app\models\OrderStatus[] $statuses */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\ArrayHelper;

$this->title = 'Настройки компании';
?>

<div class="admin-settings">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('← К заказам', ['/admin/order/index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Реквизиты компании</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($settings, 'name')->textInput() ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($settings, 'unp')->textInput() ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($settings, 'bic')->textInput() ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($settings, 'account')->textInput() ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($settings, 'bank')->textInput() ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($settings, 'address')->textInput() ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($settings, 'phone')->textInput() ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($settings, 'email')->textInput(['type' => 'email']) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?= $form->field($settings, 'offer_url')->textInput(['placeholder' => 'https://example.com/offer.pdf']) ?>
                            <div class="form-text">Ссылка на публичную оферту (если пусто — кнопка на странице клиента будет скрыта)</div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <?= Html::submitButton('Сохранить реквизиты', ['class' => 'btn btn-success']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Статусы заказов</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                                <code><?= Html::encode($st->key) ?></code>
                                            </td>
                                            <td>
                                                <input type="text" name="statuses[<?= Html::encode($st->key) ?>][label]" class="form-control" value="<?= Html::encode($st->label) ?>">
                                            </td>
                                            <td>
                                                <input type="number" name="statuses[<?= Html::encode($st->key) ?>][sort]" class="form-control" value="<?= (int)$st->sort ?>">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="statuses[<?= Html::encode($st->key) ?>][logist_available]" value="1" <?= $st->logist_available ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="statuses[<?= Html::encode($st->key) ?>][is_active]" value="1" <?= $st->is_active ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="card mb-3">
                            <div class="card-body bg-light">
                                <h6 class="mb-3">Добавить новый статус</h6>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <input type="text" name="new_status[key]" class="form-control" placeholder="Ключ (латиница)">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="new_status[label]" class="form-control" placeholder="Название">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="new_status[sort]" class="form-control" placeholder="Порядок" value="999">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-center">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="new_status[logist_available]" value="1" id="new_status_logist">
                                            <label class="form-check-label" for="new_status_logist">Логист</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Сохранить статусы</button>
                            <a href="<?= Html::encode(Yii::$app->request->url) ?>" class="btn btn-outline-secondary">Отменить</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
