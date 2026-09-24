<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var string $token */
/** @var bool $done */
/** @var string|null $error */

$this->title = 'Новый пароль — СНИКЕРХЭД';
$this->registerMetaTag(['name' => 'robots', 'content' => 'noindex, nofollow']);

echo $this->render('_auth-style');
?>

<div class="auth-page">
    <div class="auth-container">
        <a href="<?= Url::to(['/account/login']) ?>" class="back-to-site">
            <i class="bi bi-arrow-left"></i> Вернуться ко входу
        </a>

        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <img src="/images/logo.png" alt="СНИКЕРХЭД">
                    <span>СНИКЕРХЭД</span>
                </div>
                <h1>Новый пароль</h1>
            </div>

            <div class="auth-body">
                <?php if ($done) : ?>
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h2>Пароль изменён!</h2>
                        <p>Теперь вы можете войти с новым паролем.</p>
                        <a href="<?= Url::to(['/account/login']) ?>" class="btn-auth">
                            <i class="bi bi-box-arrow-in-right"></i> Войти
                        </a>
                    </div>
                <?php else : ?>
                    <?php if ($error) : ?>
                        <div class="alert-error"><?= Html::encode($error) ?></div>
                    <?php endif; ?>

                    <p class="info-text">Придумайте новый пароль для входа.</p>

                    <form method="post" novalidate>
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                        <input type="hidden" name="token" value="<?= Html::encode($token) ?>">

                        <div class="form-group">
                            <label for="new_password">Новый пароль</label>
                            <input type="password" id="new_password" name="new_password" class="form-control"
                                   required minlength="6" autocomplete="new-password" autofocus>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Повторите пароль</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                                   required minlength="6" autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn-auth">
                            <i class="bi bi-check-lg"></i> Сохранить пароль
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
