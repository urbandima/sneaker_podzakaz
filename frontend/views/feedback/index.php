<?php
/** @var yii\web\View $this */
/** @var mixed $customer */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Написать директору — СНИКЕРХЭД';
?>

<div class="feedback-page">
    <div class="feedback-container">

        <nav class="breadcrumb">
            <a href="/">Главная</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Написать директору</span>
        </nav>

        <div class="feedback-card">
            <div class="feedback-card__head">
                <div class="feedback-card__icon">
                    <i class="bi bi-chat-heart-fill"></i>
                </div>
                <div>
                    <h1 class="feedback-card__title">Написать директору</h1>
                    <p class="feedback-card__sub">Ваш отзыв будет прочитан лично. Мы ценим каждое мнение.</p>
                </div>
            </div>

            <div id="feedbackSuccess" style="display:none" class="feedback-success">
                <i class="bi bi-check-circle-fill"></i>
                <p>Спасибо! Ваш отзыв передан директору.</p>
            </div>

            <form id="feedbackForm" class="feedback-form" onsubmit="submitFeedback(event)">
                <!-- Rating -->
                <div class="feedback-field">
                    <label class="feedback-label">Ваша оценка</label>
                    <div class="star-rating" id="starRating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="star-btn" data-value="<?= $i ?>" onclick="setRating(<?= $i ?>)" title="<?= $i ?> звёзд">
                            <i class="bi bi-star"></i>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="ratingValue" name="rating" value="5">
                </div>

                <?php if (!$customer): ?>
                <!-- Name + email for guests -->
                <div class="feedback-row">
                    <div class="feedback-field">
                        <label class="feedback-label" for="fbName">Ваше имя</label>
                        <input type="text" id="fbName" name="name" class="feedback-input" placeholder="Иван Петров" maxlength="100">
                    </div>
                    <div class="feedback-field">
                        <label class="feedback-label" for="fbEmail">Email (необязательно)</label>
                        <input type="email" id="fbEmail" name="email" class="feedback-input" placeholder="ivan@example.com" maxlength="200">
                    </div>
                </div>
                <?php else: ?>
                <?php
                    $_fbAuthor = '';
                    if (method_exists($customer, 'getFullName')) {
                        $_fbAuthor = (string) $customer->getFullName();
                    }
                    if ($_fbAuthor === '') {
                        $_fbAuthor = (string) ($customer->name ?? $customer->username ?? $customer->email ?? 'Авторизованный пользователь');
                    }
                ?>
                <div class="feedback-field">
                    <label class="feedback-label">Вы пишете как</label>
                    <div class="feedback-author">
                        <i class="bi bi-person-circle"></i>
                        <span><?= Html::encode($_fbAuthor) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Message -->
                <div class="feedback-field">
                    <label class="feedback-label" for="fbMessage">Сообщение <span style="color:#ef4444">*</span></label>
                    <textarea id="fbMessage" name="message" class="feedback-input feedback-textarea"
                              placeholder="Расскажите о вашем опыте, предложениях или пожеланиях..." rows="5" maxlength="2000" required></textarea>
                    <div class="feedback-counter"><span id="msgLen">0</span>/2000</div>
                </div>

                <button type="submit" class="feedback-submit" id="fbSubmitBtn">
                    <i class="bi bi-send"></i> Отправить директору
                </button>
            </form>
        </div>

        <div class="feedback-note">
            <i class="bi bi-shield-check"></i>
            Ваш отзыв конфиденциален и виден только руководству магазина
        </div>

    </div>
</div>

<style>
.feedback-page {
    min-height: 60vh;
    padding: var(--space-10, 2.5rem) 0 var(--space-16, 4rem);
}

.feedback-container {
    max-width: 640px;
    margin: 0 auto;
    padding: 0 var(--space-4, 1rem);
}

.feedback-card {
    background: #fff;
    border: 1px solid var(--color-border, #e5e7eb);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.05);
}

.feedback-card__head {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.feedback-card__icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    background: #fff0f3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: #e11d48;
    flex-shrink: 0;
}

.feedback-card__title {
    font-size: 1.375rem;
    font-weight: 800;
    margin: 0 0 0.25rem;
    letter-spacing: -0.02em;
}

.feedback-card__sub {
    margin: 0;
    font-size: 0.9rem;
    color: #6b7280;
}

.feedback-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.feedback-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.feedback-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.feedback-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.feedback-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9375rem;
    background: #fafafa;
    transition: border-color 0.18s, background 0.18s;
    outline: none;
    box-sizing: border-box;
}

.feedback-input:focus {
    border-color: #111;
    background: #fff;
}

.feedback-textarea {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
    line-height: 1.5;
}

.feedback-counter {
    font-size: 0.75rem;
    color: #9ca3af;
    text-align: right;
}

/* Stars */
.star-rating {
    display: flex;
    gap: 0.375rem;
}

.star-btn {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    font-size: 1.75rem;
    color: #d1d5db;
    transition: color 0.15s, transform 0.12s;
    line-height: 1;
}

.star-btn.active, .star-btn:hover {
    color: #f59e0b;
    transform: scale(1.12);
}

/* Author chip */
.feedback-author {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

/* Submit */
.feedback-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.875rem;
    background: #111;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.18s, transform 0.12s;
    margin-top: 0.5rem;
}

.feedback-submit:hover {
    background: #333;
    transform: translateY(-1px);
}

.feedback-submit:disabled {
    opacity: 0.6;
    cursor: default;
    transform: none;
}

/* Success */
.feedback-success {
    text-align: center;
    padding: 2rem;
    background: #f0fdf4;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.feedback-success i {
    font-size: 2.5rem;
    color: #16a34a;
    display: block;
    margin-bottom: 0.75rem;
}

.feedback-success p {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: #15803d;
}

/* Bottom note */
.feedback-note {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #9ca3af;
    justify-content: center;
}

.feedback-note i {
    color: #10b981;
}

@media (max-width: 540px) {
    .feedback-card { padding: 1.25rem; }
    .feedback-row { grid-template-columns: 1fr; }
}
</style>

<script>
let currentRating = 5;

// Init stars at 5
document.addEventListener('DOMContentLoaded', function() {
    setRating(5);
    document.getElementById('fbMessage')?.addEventListener('input', function() {
        document.getElementById('msgLen').textContent = this.value.length;
    });
});

function setRating(val) {
    currentRating = val;
    document.getElementById('ratingValue').value = val;
    document.querySelectorAll('.star-btn').forEach(function(btn) {
        const v = parseInt(btn.dataset.value);
        const icon = btn.querySelector('i');
        if (v <= val) {
            btn.classList.add('active');
            icon.className = 'bi bi-star-fill';
        } else {
            btn.classList.remove('active');
            icon.className = 'bi bi-star';
        }
    });
}

function submitFeedback(e) {
    e.preventDefault();
    const btn = document.getElementById('fbSubmitBtn');
    const form = document.getElementById('feedbackForm');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const data = new FormData(form);
    data.set('rating', currentRating);
    data.set('_csrf', csrf);

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Отправляем...';

    fetch('<?= Url::to(['/feedback/submit']) ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': csrf },
        body: data,
    })
    .then(r => r.json())
    .then(function(d) {
        if (d.success) {
            form.style.display = 'none';
            document.getElementById('feedbackSuccess').style.display = 'block';
        } else {
            alert(d.message || 'Ошибка отправки');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send"></i> Отправить директору';
        }
    })
    .catch(function() {
        alert('Ошибка сети. Попробуйте позже.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send"></i> Отправить директору';
    });
}
</script>
