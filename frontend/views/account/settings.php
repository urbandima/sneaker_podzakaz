<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Customer $customer */
/** @var bool $passwordChanged */

$this->title = 'Настройки аккаунта - СНИКЕРХЭД';

// Стили вынесены из inline в отдельный CSS-файл
$this->registerCssFile('@web/css/pages/settings.css');
?>

<div class="account-page">
    <div class="account-container">
        <div class="account-header">
            <h1><i class="bi bi-gear"></i> Настройки</h1>
        </div>

        <div class="account-grid">
            <?= $this->render('_sidebar', [
                'customer' => $customer,
                'activePage' => 'settings',
            ]) ?>

            <main class="account-content">
                <div class="content-card">
                    <h2><i class="bi bi-info-circle"></i> Информация об аккаунте</h2>

                    <div class="account-info">
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= Html::encode($customer->email) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Дата регистрации</div>
                            <div class="info-value"><?= Yii::$app->formatter->asDate($customer->created_at, 'long') ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Последний вход</div>
                            <div class="info-value">
                                <?= $customer->last_login_at
                                    ? Yii::$app->formatter->asDatetime($customer->last_login_at, 'medium')
                                    : 'Неизвестно' ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Статус</div>
                            <div class="info-value">
                                <?php if ($customer->email_verified): ?>
                                    <span class="status-verified"><i class="bi bi-check-circle-fill"></i> Подтвержден</span>
                                <?php else: ?>
                                    <span class="status-unverified"><i class="bi bi-exclamation-circle"></i> Не подтвержден</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-shield-lock"></i> Смена пароля</h2>

                    <div class="info-card">
                        <h4><i class="bi bi-info-circle"></i> Рекомендации по безопасности</h4>
                        <p>Используйте пароль длиной не менее 8 символов, включающий буквы, цифры и специальные символы.</p>
                    </div>

                    <form method="post">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

                        <div class="form-group">
                            <label for="current_password">Текущий пароль</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Подтверждение нового пароля</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                        </div>

                        <button type="submit" class="btn-save">Изменить пароль</button>
                    </form>
                </div>

                <div class="content-card">
                    <h2><i class="bi bi-exclamation-triangle"></i> Опасная зона</h2>

                    <div class="danger-zone">
                        <h3><i class="bi bi-trash3"></i> Удаление аккаунта</h3>
                        <p>После удаления аккаунта все ваши данные будут безвозвратно удалены. История заказов сохранится, но не будет привязана к аккаунту.</p>
                        <button type="button" class="btn-danger" onclick="alert('Для удаления аккаунта свяжитесь с поддержкой')">
                            Удалить аккаунт
                        </button>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
