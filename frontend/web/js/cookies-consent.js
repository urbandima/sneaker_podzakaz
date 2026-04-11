/**
 * Cookies Consent functionality
 * Handles GDPR cookie consent banner
 */

document.addEventListener('DOMContentLoaded', function () {
    // Вставляем HTML баннера, если его нет
    if (!document.getElementById('cookiesBanner')) {
        const bannerHtml = `
            <div class="cookies-banner" id="cookiesBanner">
                <div class="cookies-content">
                    <div class="cookies-text">
                        Мы используем файлы cookie для улучшения работы сайта и анализа трафика. 
                        Продолжая использовать сайт, вы соглашаетесь с нашей <a href="/privacy">Политикой конфиденциальности</a>.
                    </div>
                    <div class="cookies-actions">
                        <button class="btn-cookies-settings" onclick="declineCookies()">Отклонить</button>
                        <button class="btn-cookies-accept" onclick="acceptCookies()">Принять</button>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', bannerHtml);
    }

    initializeCookieConsent();
});

function initializeCookieConsent() {
    const consentKey = 'cookie_consent_given';
    const consentBanner = document.getElementById('cookiesBanner');

    // Check if consent already given
    if (localStorage.getItem(consentKey)) {
        if (consentBanner) {
            consentBanner.style.display = 'none';
        }
        return;
    }

    // Show banner after 1 second
    setTimeout(() => {
        if (consentBanner) {
            consentBanner.classList.add('show');
        }
    }, 1000);
}

function acceptCookies() {
    localStorage.setItem('cookie_consent_given', 'accepted');
    localStorage.setItem('analytics_consent', 'true');
    localStorage.setItem('marketing_consent', 'true');

    loadAnalyticsScripts();
    hideConsentBanner();

    if (window.NotificationManager) {
        window.NotificationManager.success('Спасибо за согласие на использование cookies!');
    }
}

function declineCookies() {
    localStorage.setItem('cookie_consent_given', 'declined');
    localStorage.setItem('analytics_consent', 'false');
    localStorage.setItem('marketing_consent', 'false');

    hideConsentBanner();

    if (window.NotificationManager) {
        window.NotificationManager.info('Вы отказались от использования cookies. Некоторые функции могут быть недоступны.');
    }
}

function hideConsentBanner() {
    const consentBanner = document.getElementById('cookiesBanner');
    if (consentBanner) {
        consentBanner.classList.remove('show');
        setTimeout(() => {
            consentBanner.style.display = 'none';
        }, 400); // Wait for transition
    }
}

function loadAnalyticsScripts() {
    if (localStorage.getItem('analytics_consent') === 'true') {
        /* production: analytics scripts loaded */
    }
    if (localStorage.getItem('marketing_consent') === 'true') {
        /* production: marketing scripts loaded */
    }
}

window.acceptCookies = acceptCookies;
window.declineCookies = declineCookies;
window.hideConsentBanner = hideConsentBanner;
