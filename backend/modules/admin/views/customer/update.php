<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var app\backend\modules\account\models\Customer $customer
 */

$this->title = 'Редактирование: ' . $customer->getFullName();

$this->params['headerActions'] = [
    Html::a('<i class="bi bi-eye"></i> Просмотр', ['view', 'id' => $customer->id], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<div class="customer-view-page">

    <?php $form = ActiveForm::begin([
        'options' => ['class' => 'admin-form'],
        'fieldConfig' => [
            'template' => "{label}\n{input}\n{error}",
            'labelOptions' => ['class' => 'admin-form-label'],
            'inputOptions' => ['class' => 'admin-form-input'],
            'errorOptions' => ['class' => 'form-error', 'tag' => 'div'],
        ],
    ]); ?>

    <div class="content-grid">

        <!-- Main content -->
        <div class="content-main">

            <!-- Personal data -->
            <div class="content-card">
                <h2><i class="bi bi-person-badge"></i> Персональные данные</h2>
                <div class="info-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem">
                    <div><?= $form->field($customer, 'first_name')->textInput(['maxlength' => true, 'placeholder' => 'Имя']) ?></div>
                    <div><?= $form->field($customer, 'last_name')->textInput(['maxlength' => true, 'placeholder' => 'Фамилия']) ?></div>
                    <div><?= $form->field($customer, 'middle_name')->textInput(['maxlength' => true, 'placeholder' => 'Отчество']) ?></div>
                    <div><?= $form->field($customer, 'birth_date')->input('date') ?></div>
                    <div><?= $form->field($customer, 'gender')->dropDownList([
                        ''       => 'Не выбран',
                        'male'   => 'Мужской',
                        'female' => 'Женский',
                    ], ['class' => 'admin-form-input']) ?></div>
                </div>
            </div>

            <!-- Contacts -->
            <div class="content-card">
                <h2><i class="bi bi-telephone"></i> Контакты</h2>
                <div class="info-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem">
                    <div>
                        <?= $form->field($customer, 'email')->textInput(['maxlength' => true, 'disabled' => true]) ?>
                        <div style="font-size:.75rem;color:var(--admin-text-secondary,#6b7280);margin-top:3px"><i class="bi bi-info-circle"></i> Email нельзя изменить из админки</div>
                    </div>
                    <div><?= $form->field($customer, 'phone')->textInput(['maxlength' => true, 'placeholder' => '+375 XX XXX-XX-XX']) ?></div>
                </div>
            </div>

            <!-- Address -->
            <div class="content-card">
                <h2><i class="bi bi-geo-alt"></i> Адрес по умолчанию</h2>
                <div class="info-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem">
                    <div><?= $form->field($customer, 'default_country')->textInput(['maxlength' => true, 'placeholder' => 'Страна']) ?></div>
                    <div><?= $form->field($customer, 'default_city')->textInput(['maxlength' => true, 'placeholder' => 'Город']) ?></div>
                    <div><?= $form->field($customer, 'default_postal_code')->textInput(['maxlength' => true, 'placeholder' => 'Индекс']) ?></div>
                </div>
                <div style="margin-top:1rem"><?= $form->field($customer, 'default_address')->textarea(['rows' => 2, 'placeholder' => 'Улица, дом, квартира…', 'class' => 'admin-form-input']) ?></div>
            </div>

            <!-- Documents -->
            <div class="content-card">
                <h2><i class="bi bi-person-vcard"></i> Документы</h2>
                <div class="info-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem">
                    <div><?= $form->field($customer, 'passport_series')->textInput(['maxlength' => true, 'placeholder' => 'Серия']) ?></div>
                    <div><?= $form->field($customer, 'passport_number')->textInput(['maxlength' => true, 'placeholder' => 'Номер']) ?></div>
                    <div><?= $form->field($customer, 'passport_issue_date')->input('date') ?></div>
                    <div><?= $form->field($customer, 'inn')->textInput(['maxlength' => true, 'placeholder' => 'Идентификационный номер']) ?></div>
                </div>
            </div>

            <!-- Subscriptions -->
            <div class="content-card">
                <h2><i class="bi bi-bell"></i> Подписки и коммуникации</h2>
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.875rem">
                        <?= Html::activeCheckbox($customer, 'subscribe_news', ['label' => false]) ?>
                        <span style="font-weight:500">Подписка на новости</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.875rem">
                        <?= Html::activeCheckbox($customer, 'subscribe_promo', ['label' => false]) ?>
                        <span style="font-weight:500">Подписка на акции и промокоды</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;padding:.25rem 0 1rem">
                <?= Html::submitButton('<i class="bi bi-check2-circle"></i> Сохранить изменения', ['class' => 'admin-btn admin-btn-primary']) ?>
                <?= Html::a('<i class="bi bi-x-lg"></i> Отмена', ['view', 'id' => $customer->id], ['class' => 'admin-btn admin-btn-secondary']) ?>
            </div>

        </div><!-- /content-main -->

        <!-- Sidebar -->
        <div class="content-sidebar">

            <!-- Customer profile mini-card -->
            <div class="content-card">
                <div class="profile-header" style="padding-bottom:.75rem;margin-bottom:.75rem;border-bottom:1px solid var(--admin-border,#e5e7eb)">
                    <div class="profile-avatar">
                        <?= mb_strtoupper(mb_substr($customer->first_name ?: $customer->email, 0, 1)) ?>
                    </div>
                    <div class="profile-info">
                        <h3 style="font-size:.9375rem"><?= Html::encode($customer->getFullName()) ?></h3>
                        <div class="email"><?= Html::encode($customer->email) ?></div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div style="text-align:center;padding:8px;background:var(--admin-surface-hover,#f9fafb);border-radius:8px">
                        <div style="font-size:1.125rem;font-weight:800;color:var(--admin-text-primary,#111)"><?= $customer->orders_count ?? 0 ?></div>
                        <div style="font-size:.65rem;text-transform:uppercase;color:var(--admin-text-secondary,#6b7280);letter-spacing:.04em">Заказов</div>
                    </div>
                    <div style="text-align:center;padding:8px;background:var(--admin-surface-hover,#f9fafb);border-radius:8px">
                        <div style="font-size:1.125rem;font-weight:800;color:var(--admin-text-primary,#111)">
                            <?= $customer->total_spent ? number_format($customer->total_spent, 0, '.', ' ') . ' Br' : '—' ?>
                        </div>
                        <div style="font-size:.65rem;text-transform:uppercase;color:var(--admin-text-secondary,#6b7280);letter-spacing:.04em">Потрачено</div>
                    </div>
                </div>
                <?php if ($customer->created_at): ?>
                <div style="margin-top:10px;font-size:.75rem;color:var(--admin-text-secondary,#6b7280)">
                    <i class="bi bi-calendar3"></i>
                    Зарегистрирован <?= date('d.m.Y', is_numeric($customer->created_at) ? $customer->created_at : strtotime($customer->created_at)) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tips / hints -->
            <div class="content-card" style="background:var(--admin-info-bg,#e0f2fe);border-color:var(--admin-info,#0369a1)">
                <div style="display:flex;gap:8px;align-items:flex-start">
                    <i class="bi bi-lightbulb" style="color:var(--admin-info,#0369a1);margin-top:2px;flex-shrink:0"></i>
                    <div style="font-size:.8125rem;color:var(--admin-text-primary,#111)">
                        <strong>Подсказка:</strong> Изменения сохраняются только после нажатия кнопки «Сохранить изменения». Email-адрес изменить нельзя.
                    </div>
                </div>
            </div>

        </div><!-- /content-sidebar -->

    </div><!-- /content-grid -->

    <?php ActiveForm::end(); ?>

</div><!-- /customer-view-page -->
