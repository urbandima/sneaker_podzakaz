/**
 * Cookies Consent System
 * Соответствие законодательству РБ о персональных данных
 */

(function() {
    'use strict';

    class CookiesConsent {
        constructor() {
            this.consentKey = 'cookies_consent_given';
            this.consentDateKey = 'cookies_consent_date';
            this.bannerId = 'cookies-consent-banner';
            this.isConsentGiven = this.checkConsent();
            
            this.init();
        }

        init() {
            if (!this.isConsentGiven) {
                this.showBanner();
            }
        }

        checkConsent() {
            const consent = localStorage.getItem(this.consentKey);
            const date = localStorage.getItem(this.consentDateKey);
            
            return consent === 'true' && date && !this.isConsentExpired(date);
        }

        isConsentExpired(dateString) {
            const consentDate = new Date(dateString);
            const now = new Date();
            const daysDiff = (now - consentDate) / (1000 * 60 * 60 * 24);
            
            // Обновляем согласие каждые 365 дней
            return daysDiff > 365;
        }

        showBanner() {
            // Создаём HTML баннера
            const bannerHTML = `
                <div id="${this.bannerId}" class="cookies-consent-banner">
                    <div class="cookies-consent-container">
                        <div class="cookies-consent-content">
                            <div class="cookies-consent-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                                </svg>
                            </div>
                            <div class="cookies-consent-text">
                                <h4>Файлы cookie</h4>
                                <p>
                                    Мы используем файлы cookie для улучшения работы сайта, анализа трафика и персонализации контента. 
                                    Продолжая использовать сайт, вы соглашаетесь с нашей 
                                    <a href="/privacy" target="_blank">Политикой конфиденциальности</a>.
                                </p>
                            </div>
                        </div>
                        <div class="cookies-consent-actions">
                            <button class="btn btn-outline btn-sm" onclick="window.CookiesConsent.reject()">
                                Отклонить
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="window.CookiesConsent.accept()">
                                Принять все
                            </button>
                        </div>
                        <button class="cookies-consent-close" onclick="window.CookiesConsent.hide()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            `;

            // Вставляем баннер в начало body
            document.body.insertAdjacentHTML('afterbegin', bannerHTML);
            
            // Добавляем стили
            this.addStyles();
            
            // Показываем баннер с анимацией
            setTimeout(() => {
                const banner = document.getElementById(this.bannerId);
                if (banner) {
                    banner.classList.add('show');
                }
            }, 100);
        }

        addStyles() {
            const styles = `
                .cookies-consent-banner {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    z-index: 10000;
                    transform: translateY(100%);
                    transition: transform 0.3s ease-in-out;
                    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
                }

                .cookies-consent-banner.show {
                    transform: translateY(0);
                }

                .cookies-consent-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 20px;
                    position: relative;
                }

                .cookies-consent-content {
                    display: flex;
                    align-items: flex-start;
                    gap: 15px;
                    margin-bottom: 15px;
                    padding-right: 40px;
                }

                .cookies-consent-icon {
                    flex-shrink: 0;
                    margin-top: 2px;
                }

                .cookies-consent-text h4 {
                    margin: 0 0 8px 0;
                    font-size: 16px;
                    font-weight: 600;
                }

                .cookies-consent-text p {
                    margin: 0;
                    font-size: 14px;
                    line-height: 1.5;
                    opacity: 0.9;
                }

                .cookies-consent-text a {
                    color: white;
                    text-decoration: underline;
                    font-weight: 500;
                }

                .cookies-consent-text a:hover {
                    opacity: 0.8;
                }

                .cookies-consent-actions {
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                .cookies-consent-close {
                    position: absolute;
                    top: 15px;
                    right: 15px;
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    padding: 5px;
                    border-radius: 4px;
                    transition: background-color 0.2s;
                }

                .cookies-consent-close:hover {
                    background-color: rgba(255, 255, 255, 0.1);
                }

                /* Мобильная адаптация */
                @media (max-width: 768px) {
                    .cookies-consent-container {
                        padding: 15px;
                    }

                    .cookies-consent-content {
                        flex-direction: column;
                        gap: 10px;
                        padding-right: 30px;
                    }

                    .cookies-consent-actions {
                        flex-direction: column;
                        gap: 8px;
                    }

                    .cookies-consent-actions .btn {
                        width: 100%;
                    }

                    .cookies-consent-text h4 {
                        font-size: 15px;
                    }

                    .cookies-consent-text p {
                        font-size: 13px;
                    }
                }

                /* Темная тема */
                [data-theme="dark"] .cookies-consent-banner {
                    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
                }
            `;

            const styleSheet = document.createElement('style');
            styleSheet.textContent = styles;
            document.head.appendChild(styleSheet);
        }

        accept() {
            this.setConsent(true);
            this.hide();
            this.loadAnalytics();
        }

        reject() {
            this.setConsent(false);
            this.hide();
            this.disableAnalytics();
        }

        hide() {
            const banner = document.getElementById(this.bannerId);
            if (banner) {
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.remove();
                }, 300);
            }
        }

        setConsent(accepted) {
            localStorage.setItem(this.consentKey, accepted.toString());
            localStorage.setItem(this.consentDateKey, new Date().toISOString());
            this.isConsentGiven = accepted;
        }

        loadAnalytics() {
            // Загружаем Google Analytics или другие аналитические скрипты
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'granted',
                    'ad_storage': 'granted'
                });
            }
        }

        disableAnalytics() {
            // Отключаем аналитику
            if (typeof gtag !== 'undefined') {
                gtag('consent', 'update', {
                    'analytics_storage': 'denied',
                    'ad_storage': 'denied'
                });
            }
        }

        // Метод для повторного показа баннера (например, из настроек)
        reset() {
            localStorage.removeItem(this.consentKey);
            localStorage.removeItem(this.consentDateKey);
            this.isConsentGiven = false;
            this.showBanner();
        }
    }

    // Инициализация при загрузке DOM
    document.addEventListener('DOMContentLoaded', function() {
        window.CookiesConsent = new CookiesConsent();
    });

})();
