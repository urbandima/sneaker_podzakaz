/**
 * Cookies Consent functionality
 * Handles GDPR cookie consent banner
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeCookieConsent();
});

function initializeCookieConsent() {
    const consentKey = 'cookie_consent_given';
    const consentBanner = document.querySelector('.cookie-consent');
    const acceptButton = document.querySelector('.cookie-accept');
    const declineButton = document.querySelector('.cookie-decline');
    
    // Check if consent already given
    if (localStorage.getItem(consentKey)) {
        hideConsentBanner();
        return;
    }
    
    // Show banner after 1 second
    setTimeout(() => {
        if (consentBanner) {
            consentBanner.classList.add('show');
        }
    }, 1000);
    
    // Handle accept button
    if (acceptButton) {
        acceptButton.addEventListener('click', function() {
            acceptCookies();
        });
    }
    
    // Handle decline button
    if (declineButton) {
        declineButton.addEventListener('click', function() {
            declineCookies();
        });
    }
}

function acceptCookies() {
    localStorage.setItem('cookie_consent_given', 'accepted');
    localStorage.setItem('analytics_consent', 'true');
    localStorage.setItem('marketing_consent', 'true');
    
    // Load analytics scripts
    loadAnalyticsScripts();
    
    hideConsentBanner();
    showNotification('Спасибо за согласие на использование cookies!', 'success');
}

function declineCookies() {
    localStorage.setItem('cookie_consent_given', 'declined');
    localStorage.setItem('analytics_consent', 'false');
    localStorage.setItem('marketing_consent', 'false');
    
    hideConsentBanner();
    showNotification('Вы отказались от использования cookies. Некоторые функции могут быть недоступны.', 'info');
}

function hideConsentBanner() {
    const consentBanner = document.querySelector('.cookie-consent');
    if (consentBanner) {
        consentBanner.classList.remove('show');
        setTimeout(() => {
            consentBanner.style.display = 'none';
        }, 300);
    }
}

function loadAnalyticsScripts() {
    // Load Google Analytics if consent given
    if (localStorage.getItem('analytics_consent') === 'true') {
        // Add Google Analytics or other analytics scripts here
        console.log('Analytics scripts loaded');
    }
    
    // Load marketing scripts if consent given
    if (localStorage.getItem('marketing_consent') === 'true') {
        // Add marketing scripts here
        console.log('Marketing scripts loaded');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Export functions
window.acceptCookies = acceptCookies;
window.declineCookies = declineCookies;
window.hideConsentBanner = hideConsentBanner;
