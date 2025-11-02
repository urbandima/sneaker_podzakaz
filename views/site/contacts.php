<?php
/** @var yii\web\View $this */

$this->title = 'Контакты - СНИКЕРХЭД';
$this->registerCssFile('@web/css/mobile-first.css', ['position' => \yii\web\View::POS_HEAD]);
?>

<div class="page-wrapper">
    <header class="catalog-header">
        <div class="container">
            <button type="button" class="back-btn" onclick="history.back()">
                <i class="bi bi-arrow-left"></i>
            </button>
            <a href="/" class="logo">СНИКЕРХЭД</a>
            <a href="/catalog/favorites" class="favorites">
                <i class="bi bi-heart"></i>
            </a>
        </div>
    </header>
    
    <div class="page-content">
        <div class="container">
            <h1 class="page-title">Контакты</h1>
            
            <div class="contacts-grid">
                <div class="contact-info-section">
                    <h2 class="section-title">📞 Свяжитесь с нами</h2>
                    
                    <div class="contact-items">
                        <div class="contact-item">
                            <h4 class="contact-label">Телефон:</h4>
                            <p class="contact-value">
                                <a href="tel:+375291234567" class="contact-link">+375 29 123-45-67</a>
                            </p>
                            <p class="contact-note">Пн-Сб: 10:00 - 20:00, Вс: 11:00 - 18:00</p>
                        </div>
                        
                        <div class="contact-item">
                            <h4 class="contact-label">Email:</h4>
                            <p class="contact-value">
                                <a href="mailto:info@sneakerhead.by" class="contact-link">info@sneakerhead.by</a>
                            </p>
                        </div>
                        
                        <div class="contact-item">
                            <h4 class="contact-label">Адрес:</h4>
                            <p class="contact-address">
                                г. Минск, пр-т Независимости, 47<br>
                                ТЦ "Столица", 3 этаж
                            </p>
                        </div>
                        
                        <div class="contact-item">
                            <h4 class="contact-label">Мессенджеры:</h4>
                            <div class="messenger-buttons">
                                <a href="#" class="messenger-btn whatsapp">
                                    <i class="bi bi-whatsapp"></i> WhatsApp
                                </a>
                                <a href="#" class="messenger-btn telegram">
                                    <i class="bi bi-telegram"></i> Telegram
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form-section">
                    <h2 class="section-title">✉️ Напишите нам</h2>
                    <form class="contact-form">
                        <div class="form-group">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-input" placeholder="Ваше имя">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-input" placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Сообщение</label>
                            <textarea rows="5" class="form-textarea" placeholder="Ваше сообщение..."></textarea>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="bi bi-send"></i>
                            <span>Отправить сообщение</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Используем стили из about.php + специфичные для contacts */
.contacts-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-xl, 32px);
}

.contact-info-section,
.contact-form-section {
    background: white;
    padding: var(--spacing-lg, 24px);
    border-radius: var(--radius-lg, 16px);
    box-shadow: var(--shadow-sm, 0 1px 2px rgba(0,0,0,0.05));
}

.contact-items {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg, 24px);
}

.contact-item {
    padding-bottom: var(--spacing-lg, 24px);
    border-bottom: 1px solid var(--gray-light, #f3f4f6);
}

.contact-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.contact-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--dark, #111827);
    font-size: 0.9375rem;
}

.contact-value {
    font-size: 1.125rem;
    margin-bottom: 0.25rem;
}

.contact-link {
    color: var(--dark, #111827);
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition, 0.2s);
}

.contact-link:hover {
    color: var(--primary, #2563eb);
}

.contact-note {
    color: var(--gray, #6b7280);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.contact-address {
    color: var(--gray, #6b7280);
    line-height: 1.6;
}

.messenger-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.messenger-btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-md, 12px);
    font-weight: 600;
    text-decoration: none;
    color: white;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: var(--transition, 0.2s);
    justify-content: center;
}

.messenger-btn.whatsapp {
    background: #25D366;
}

.messenger-btn.whatsapp:hover {
    background: #1fbd5a;
}

.messenger-btn.telegram {
    background: #0088cc;
}

.messenger-btn.telegram:hover {
    background: #006ba3;
}

/* Form Styles */
.contact-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md, 16px);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--dark, #111827);
    font-size: 0.9375rem;
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.875rem;
    border: 1px solid var(--gray-light, #e5e7eb);
    border-radius: var(--radius-md, 12px);
    font-size: 1rem;
    font-family: inherit;
    transition: var(--transition, 0.2s);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.btn-submit {
    padding: 1rem;
    background: var(--dark, #111827);
    color: white;
    border: none;
    border-radius: var(--radius-md, 12px);
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: var(--transition, 0.2s);
}

.btn-submit:hover {
    background: var(--gray, #6b7280);
}

.btn-submit:active {
    transform: scale(0.98);
}

@media (min-width: 768px) {
    .contacts-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .messenger-buttons {
        flex-direction: row;
    }
    
    .contact-info-section,
    .contact-form-section {
        padding: var(--spacing-xl, 32px);
    }
}
</style>
