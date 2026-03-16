<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var bool $sent */

$this->title = 'Восстановление пароля - СНИКЕРХЭД';
?>

<style>
.auth-page {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
}

.auth-container {
    width: 100%;
    max-width: 440px;
}

.auth-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    overflow: hidden;
}

.auth-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 2.5rem 2rem;
    text-align: center;
    color: #fff;
}

.auth-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.auth-logo img {
    width: 50px;
    height: 50px;
    border-radius: 12px;
}

.auth-logo span {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: 1px;
}

.auth-header h1 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    opacity: 0.9;
}

.auth-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.25rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.2s;
    background: #f9fafb;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
}

.btn-auth {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-auth:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(59,130,246,0.3);
}

.auth-footer {
    text-align: center;
    padding: 1.5rem;
    background: #f9fafb;
}

.auth-footer a {
    color: #3b82f6;
    font-weight: 600;
    text-decoration: none;
}

.back-to-site {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
    transition: color 0.2s;
}

.back-to-site:hover {
    color: #374151;
}

.success-message {
    text-align: center;
    padding: 2rem 1rem;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.success-icon i {
    font-size: 2.5rem;
    color: #fff;
}

.success-message h2 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.success-message p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.info-text {
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1.5rem;
}
</style>

<div class="auth-page">
    <div class="auth-container">
        <a href="<?= Url::to(['/account/login']) ?>" class="back-to-site">
            <i class="bi bi-arrow-left"></i> Вернуться ко входу
        </a>
        
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <img src="https://sneaker-head.by/images/logo.png" alt="СНИКЕРХЭД">
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
