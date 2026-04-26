<?php

use yii\helpers\Html;

$this->title = 'Политика конфиденциальности';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.info-page-wrap{max-width:760px;margin:0 auto}
.page-edit-admin-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:#0f0f0f;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;opacity:.7;transition:opacity .15s}
.page-edit-admin-btn:hover{opacity:1;color:#fff}
.alert{padding:14px 18px;border-radius:10px;margin-bottom:16px}
.alert-primary{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
.alert-warning{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.alert-info{background:#f0f9ff;border:1px solid #bae6fd;color:#0c4a6e}
.alert-heading{font-size:14px;font-weight:700;margin:0 0 6px}
.mb-0{margin-bottom:0!important}.mb-3{margin-bottom:1rem}
.text-success{color:#16a34a}.text-primary{color:#2563eb}.text-info{color:#0284c7}.text-warning{color:#d97706}.text-muted{color:var(--color-text-secondary,#666)}
.small{font-size:.85em}.list-unstyled{list-style:none;padding-left:0}
.h4{font-size:1.1rem;font-weight:700}.h6{font-size:.9rem;font-weight:700}
.content-section h2{font-size:1.1rem!important;font-weight:700!important;margin-bottom:.75rem!important}
</style>
<div class="container" style="padding-top:var(--space-12,3rem);padding-bottom:var(--space-16,4rem)">
    <div class="info-page-wrap">
        <?php if (!Yii::$app->user->isGuest && Yii::$app->user->identity && Yii::$app->user->identity->isAdmin()): ?>
        <div style="margin-bottom:1rem;text-align:right">
            <a href="/admin/page/edit?slug=privacy" class="page-edit-admin-btn" target="_blank">
                <i class="bi bi-pencil-square"></i> Редактировать страницу
            </a>
        </div>
        <?php endif; ?>
            <div style="margin-bottom:var(--space-10,2.5rem)">
                <h1 style="font-size:clamp(1.75rem,4vw,2.5rem);font-weight:900;letter-spacing:-0.03em;margin-bottom:var(--space-2,.5rem)">Политика конфиденциальности</h1>
                <p style="color:var(--color-text-secondary,#666);font-size:var(--text-base,1rem)">Информация о сборе, обработке и защите персональных данных в соответствии с законодательством РБ</p>
            </div>

            <div class="content-section">
                <div class="alert alert-primary">
                    <h6 class="alert-heading"><i class="bi bi-shield-lock"></i> Ваша конфиденциальность — наш приоритет</h6>
                    <p class="mb-0">
                        Мы уважаем вашу частную жизнь и обязуемся защищать персональные данные в соответствии 
                        с Законом РБ "О персональных данных" и другими нормативными актами.
                    </p>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-clipboard"></i> Основные положения</h2>
                <div class="policy-basics">
                    <div class="policy-item">
                        <div class="policy-icon">
                            <i class="bi bi-calendar-date"></i>
                        </div>
                        <div class="policy-content">
                            <h5>Дата вступления в силу</h5>
                            <p>15 марта 2026 года</p>
                        </div>
                    </div>

                    <div class="policy-item">
                        <div class="policy-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="policy-content">
                            <h5>Оператор персональных данных</h5>
                            <p>ООО "СНИКЕРХЭД", УНП 123456789</p>
                        </div>
                    </div>

                    <div class="policy-item">
                        <div class="policy-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="policy-content">
                            <h5>Контакты по вопросам конфиденциальности</h5>
                            <p>privacy@snikered.by</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Какие данные мы собираем</h2>
                <div class="data-types">
                    <div class="data-category">
                        <div class="category-header">
                            <i class="bi bi-person"></i>
                            <h5>Персональные данные</h5>
                        </div>
                        <ul class="data-list">
                            <li>ФИО</li>
                            <li>Телефон</li>
                            <li>Email</li>
                            <li>Адрес доставки</li>
                            <li>Дата рождения (при регистрации)</li>
                        </ul>
                    </div>

                    <div class="data-category">
                        <div class="category-header">
                            <i class="bi bi-cart"></i>
                            <h5>Данные о заказах</h5>
                        </div>
                        <ul class="data-list">
                            <li>История заказов</li>
                            <li>Состав заказа</li>
                            <li>Способ оплаты</li>
                            <li>Способ доставки</li>
                            <li>Предпочтения</li>
                        </ul>
                    </div>

                    <div class="data-category">
                        <div class="category-header">
                            <i class="bi bi-globe"></i>
                            <h5>Технические данные</h5>
                        </div>
                        <ul class="data-list">
                            <li>IP-адрес</li>
                            <li>Тип браузера</li>
                            <li>Операционная система</li>
                            <li>Cookie</li>
                            <li>История посещений</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Цели сбора данных</h2>
                <div class="purposes-grid">
                    <div class="purpose-card">
                        <div class="purpose-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h6>Исполнение заказов</h6>
                        <p>Обработка и доставка ваших заказов</p>
                    </div>

                    <div class="purpose-card">
                        <div class="purpose-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <h6>Обслуживание клиентов</h6>
                        <p>Поддержка и консультации</p>
                    </div>

                    <div class="purpose-card">
                        <div class="purpose-icon">
                            <i class="bi bi-gift"></i>
                        </div>
                        <h6>Персонализация</h6>
                        <p>Рекомендации и персональные предложения</p>
                    </div>

                    <div class="purpose-card">
                        <div class="purpose-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h6>Безопасность</h6>
                        <p>Защита от мошенничества</p>
                    </div>

                    <div class="purpose-card">
                        <div class="purpose-icon">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <h6>Аналитика</h6>
                        <p>Улучшение работы сайта</p>
                    </div>

                    <div class="purpose-card">
                        <div class="purpose-icon">
                            <i class="bi bi-megaphone"></i>
                        </div>
                        <h6>Маркетинг</h6>
                        <p>Информация об акциях и новинках</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Файлы cookie</h2>
                <div class="cookie-info">
                    <div class="cookie-section">
                        <h6>Что такое cookie?</h6>
                        <p>
                            Cookie — это небольшие текстовые файлы, которые сохраняются на вашем устройстве 
                            при посещении сайта. Они помогают нам запоминать ваши предпочтения и улучшать работу сайта.
                        </p>
                    </div>

                    <div class="cookie-types">
                        <h6>Типы cookie, которые мы используем:</h6>
                        <div class="cookie-type-item">
                            <div class="cookie-type-header">
                                <i class="bi bi-toggle-on text-success"></i>
                                <span>Необходимые cookie</span>
                            </div>
                            <p class="small text-muted">
                                Обязательные для работы сайта (корзина, авторизация, оплата)
                            </p>
                        </div>

                        <div class="cookie-type-item">
                            <div class="cookie-type-header">
                                <i class="bi bi-bar-chart text-info"></i>
                                <span>Аналитические cookie</span>
                            </div>
                            <p class="small text-muted">
                                Помогают понять, как вы используете сайт (Google Analytics)
                            </p>
                        </div>

                        <div class="cookie-type-item">
                            <div class="cookie-type-header">
                                <i class="bi bi-bullseye text-warning"></i>
                                <span>Маркетинговые cookie</span>
                            </div>
                            <p class="small text-muted">
                                Используются для показа релевантной рекламы
                            </p>
                        </div>
                    </div>

                    <div class="cookie-control">
                        <h6>Управление cookie</h6>
                        <p>
                            Вы можете управлять cookie через настройки браузера или через наш баннер согласия. 
                            Отключение необходимых cookie может повлиять на работу сайта.
                        </p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Срок хранения данных</h2>
                <div class="storage-periods">
                    <div class="period-item">
                        <div class="period-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="period-details">
                            <h6>Данные заказов</h6>
                            <p>5 лет с момента последнего заказа</p>
                        </div>
                    </div>

                    <div class="period-item">
                        <div class="period-icon">
                            <i class="bi bi-person-check"></i>
                        </div>
                        <div class="period-details">
                            <h6>Учётная запись</h6>
                            <p>До удаления аккаунта пользователем</p>
                        </div>
                    </div>

                    <div class="period-item">
                        <div class="period-icon">
                            <i class="bi bi-browser-chrome"></i>
                        </div>
                        <div class="period-details">
                            <h6>Cookie</h6>
                            <p>От 1 дня до 1 года в зависимости от типа</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Защита данных</h2>
                <div class="security-measures">
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="bi bi-shield-lock text-success"></i>
                        </div>
                        <div class="security-content">
                            <h6>SSL-шифрование</h6>
                            <p>Все данные передаются по защищённому протоколу HTTPS</p>
                        </div>
                    </div>

                    <div class="security-item">
                        <div class="security-icon">
                            <i class="bi bi-server text-primary"></i>
                        </div>
                        <div class="security-content">
                            <h6>Серверы в РБ</h6>
                            <p>Данные хранятся на серверах, расположенных в Беларуси</p>
                        </div>
                    </div>

                    <div class="security-item">
                        <div class="security-icon">
                            <i class="bi bi-lock text-warning"></i>
                        </div>
                        <div class="security-content">
                            <h6>Ограниченный доступ</h6>
                            <p>Только уполномоченные сотрудники имеют доступ к данным</p>
                        </div>
                    </div>

                    <div class="security-item">
                        <div class="security-icon">
                            <i class="bi bi-arrow-repeat text-info"></i>
                        </div>
                        <div class="security-content">
                            <h6>Регулярное резервное копирование</h6>
                            <p>Ежедневное резервирование данных для предотвращения потерь</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Передача данных третьим лицам</h2>
                <div class="data-sharing">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">Важно знать</h6>
                        <p class="mb-0">
                            Мы не передаём ваши персональные данные третьим лицам для маркетинговых целей. 
                            Данные передаются только в следующих случаях:
                        </p>
                    </div>

                    <div class="sharing-cases">
                        <div class="case-item">
                            <i class="bi bi-truck"></i>
                            <div>
                                <h6>Службы доставки</h6>
                                <p>Для выполнения заказа (СДЭК, Почта России, курьерские службы)</p>
                            </div>
                        </div>

                        <div class="case-item">
                            <i class="bi bi-credit-card"></i>
                            <div>
                                <h6>Платёжные системы</h6>
                                <p>Для обработки платежей (банки, платёжные шлюзы)</p>
                            </div>
                        </div>

                        <div class="case-item">
                            <i class="bi bi-bank"></i>
                            <div>
                                <h6>Государственные органы</h6>
                                <p>По законному требованию правоохранительных органов</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Ваши права</h2>
                <div class="rights-grid">
                    <div class="right-card">
                        <div class="right-icon">
                            <i class="bi bi-eye"></i>
                        </div>
                        <h6>Право на доступ</h6>
                        <p>Вы можете запросить информацию о ваших данных</p>
                    </div>

                    <div class="right-card">
                        <div class="right-icon">
                            <i class="bi bi-pencil"></i>
                        </div>
                        <h6>Право на исправление</h6>
                        <p>Вы можете исправить неточные данные</p>
                    </div>

                    <div class="right-card">
                        <div class="right-icon">
                            <i class="bi bi-trash"></i>
                        </div>
                        <h6>Право на удаление</h6>
                        <p>Вы можете удалить свой аккаунт и данные</p>
                    </div>

                    <div class="right-card">
                        <div class="right-icon">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <h6>Право на отозвать согласие</h6>
                        <p>Вы можете отозвать согласие на обработку данных</p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Защита данных детей</h2>
                <div class="children-protection">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Возрастные ограничения</h6>
                        <p class="mb-0">
                            Мы не собираем сознательно персональные данные детей младше 16 лет. 
                            Если мы обнаружим, что собрали данные ребёнка, мы примем меры для их удаления.
                        </p>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-telephone"></i> Связь с нами</h2>
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="bi bi-envelope text-primary"></i>
                        <div>
                            <h6>Email по вопросам конфиденциальности</h6>
                            <p>privacy@snikered.by</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="bi bi-telephone text-primary"></i>
                        <div>
                            <h6>Телефон</h6>
                            <p>+375 (29) 123-45-67</p>
                        </div>
                    </div>

                    <div class="contact-item">
                        <i class="bi bi-geo-alt text-primary"></i>
                        <div>
                            <h6>Юридический адрес</h6>
                            <p>г. Минск, ул. Купревича 1, корп. 1</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3">Законодательство</h2>
                <div class="legal-info">
                    <p>
                        Наша политика конфиденциальности разработана в соответствии с:
                    </p>
                    <ul class="legal-list">
                        <li>Закон РБ "О персональных данных" от 14.07.2008 № 398-З</li>
                        <li>Закон РБ "О защите прав потребителей"</li>
                        <li>Указ Президента РБ № 60 от 01.02.2010</li>
                        <li>Общий регламент о защите данных (GDPR) для международных пользователей</li>
                    </ul>
                </div>
            </div>

            <div class="content-section">
                <h2 class="h4 mb-3"><i class="bi bi-arrow-repeat"></i> Изменения в политике</h2>
                <div class="changes-info">
                    <p>
                        Мы можем периодически обновлять эту политику. Все изменения вступают в силу 
                        с момента публикации на сайте. Мы уведомим вас о существенных изменениях 
                        через email или уведомление на сайте.
                    </p>
                    <div class="last-updated">
                        <strong>Последнее обновление:</strong> 15 марта 2026 года
                    </div>
                </div>
            </div>
    </div>
</div>

<style>
.policy-basics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
    margin-bottom: 30px;
}
.policy-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border-left: 3px solid var(--c-black, #111);
}
.policy-icon { font-size: 1.4rem; color: var(--color-text-primary, #111); flex-shrink: 0; }
.data-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}
.data-category {
    padding: 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border-top: 3px solid var(--c-black, #111);
}
.category-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.category-header i { font-size: 1.2rem; color: var(--color-text-primary, #111); }
.data-list { list-style: none; padding: 0; margin: 0; }
.data-list li { padding: 5px 0; border-bottom: 1px solid var(--color-border, #e5e5e5); font-size: var(--text-sm, 0.875rem); }
.data-list li:last-child { border-bottom: none; }
.purposes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}
.purpose-card {
    text-align: center;
    padding: 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border: 1px solid var(--color-border, #e5e5e5);
    transition: transform 0.2s, box-shadow 0.2s;
}
.purpose-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.purpose-icon { font-size: 1.5rem; color: var(--color-text-primary, #111); margin-bottom: 10px; }
.cookie-info { display: grid; gap: 16px; }
.cookie-section,
.cookie-types,
.cookie-control {
    padding: 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
}
.cookie-type-item { margin-bottom: 14px; }
.cookie-type-header { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
.storage-periods { display: grid; gap: 12px; }
.period-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
}
.period-icon { font-size: 1.3rem; color: var(--color-text-primary, #111); flex-shrink: 0; }
.security-measures { display: grid; gap: 12px; }
.security-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
}
.security-icon { font-size: 1.3rem; flex-shrink: 0; }
.sharing-cases { display: grid; gap: 12px; }
.case-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 18px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
}
.case-item i { font-size: 1.3rem; color: var(--color-text-primary, #111); flex-shrink: 0; }
.rights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
}
.right-card {
    text-align: center;
    padding: 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
    border: 1px solid var(--color-border, #e5e5e5);
    transition: transform 0.2s, box-shadow 0.2s;
}
.right-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.06); }
.right-icon { font-size: 1.5rem; color: var(--color-text-primary, #111); margin-bottom: 10px; }
.contact-info { display: grid; gap: 12px; }
.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px 20px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-lg, 12px);
}
.contact-item i { font-size: 1.2rem; flex-shrink: 0; }
.legal-list { list-style: none; padding: 0; }
.legal-list li {
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border, #e5e5e5);
    font-size: var(--text-sm, 0.875rem);
    color: var(--color-text-secondary, #666);
}
.legal-list li:last-child { border-bottom: none; }
.last-updated {
    margin-top: 15px;
    padding: 14px;
    background: var(--color-bg-secondary, #f5f5f5);
    border-radius: var(--radius-md, 8px);
    text-align: center;
    font-size: var(--text-sm, 0.875rem);
}
.content-section { margin-bottom: 36px; }
.content-section h2 { font-size: var(--text-lg, 1.125rem) !important; font-weight: 700 !important; }

@media (max-width: 768px) {
    .policy-item, .period-item, .security-item, .case-item, .contact-item {
        flex-direction: column; text-align: center;
    }
}
</style>
