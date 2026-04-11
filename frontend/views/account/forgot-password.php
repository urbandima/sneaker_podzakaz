<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var bool $sent */

$this->title = 'Восстановление пароля - СНИКЕРХЭД';

// Используем общие стили авторизации (вместо 176 строк inline CSS)
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
                <h1>Восстановление пароля</h1>
            </div>

            <div class="auth-body">
                <?php if ($sent): ?>
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="bi bi-envelope-check"></i>
                        </div>
                        <h2>Письмо отправлено!</h2>
                        <p>Инструкции по восстановлению пароля отправлены на указанный email. Проверьте папку «Спам», если письмо не пришло.</p>
                        <a href="<?= Url::to(['/account/login']) ?>" class="btn-auth">
                            <i class="bi bi-arrow-left"></i> Вернуться ко входу
                        </a>
                    </div>
                <?php else: ?>
                    <p class="info-text">
                        Введите email, указанный при регистрации. Мы отправим вам инструкции по восстановлению пароля.
                    </p>

                    <form method="post">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="example@mail.com" required autofocus>
                        </div>

                        <button type="submit" class="btn-auth">
                            <i class="bi bi-envelope"></i> Отправить инструкции
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="auth-footer">
                <a href="<?= Url::to(['/account/login']) ?>">Вспомнили пароль? Войти</a>
            </div>
        </div>
    </div>
</div>
