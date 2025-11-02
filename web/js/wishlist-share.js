/**
 * Функционал "Поделиться списком желаний"
 */

(function() {
    'use strict';

    /**
     * Поделиться списком желаний
     */
    function shareWishlist() {
        // Получаем список ID избранных товаров
        const favoriteIds = getFavoriteIds();
        
        if (favoriteIds.length === 0) {
            alert('Ваш список желаний пуст. Добавьте товары в избранное!');
            return;
        }

        // Генерируем ссылку
        const shareUrl = generateShareUrl(favoriteIds);
        
        // Показываем модальное окно с опциями шеринга
        showShareModal(shareUrl, favoriteIds.length);
    }

    /**
     * Получить ID избранных товаров
     */
    function getFavoriteIds() {
        // Из localStorage (для незалогиненных)
        try {
            const favorites = localStorage.getItem('favorites');
            if (favorites) {
                return JSON.parse(favorites);
            }
        } catch (e) {
            console.error('Error reading favorites:', e);
        }

        // Из DOM (для залогиненных)
        const favoriteCards = document.querySelectorAll('.favorite-product[data-id]');
        return Array.from(favoriteCards).map(card => parseInt(card.dataset.id));
    }

    /**
     * Генерировать ссылку для шеринга
     */
    function generateShareUrl(ids) {
        const baseUrl = window.location.origin;
        const params = new URLSearchParams({ wishlist: ids.join(',') });
        return `${baseUrl}/catalog?${params.toString()}`;
    }

    /**
     * Показать модальное окно шеринга
     */
    function showShareModal(url, count) {
        const modal = document.createElement('div');
        modal.className = 'share-modal';
        modal.innerHTML = `
            <div class="share-modal-content">
                <button class="share-close" onclick="this.closest('.share-modal').remove()">
                    <i class="bi bi-x"></i>
                </button>
                
                <div class="share-header">
                    <i class="bi bi-share-fill"></i>
                    <h3>Поделиться списком желаний</h3>
                    <p>Поделитесь вашими любимыми товарами (${count} ${pluralize(count, 'товар', 'товара', 'товаров')})</p>
                </div>

                <div class="share-link-box">
                    <input type="text" value="${url}" readonly id="shareUrlInput" onclick="this.select()">
                    <button class="btn-copy" onclick="wishlistShare.copyLink('${url}')">
                        <i class="bi bi-clipboard"></i>
                        Копировать
                    </button>
                </div>

                <div class="share-options">
                    <button class="share-btn share-whatsapp" onclick="wishlistShare.shareVia('whatsapp', '${url}', ${count})">
                        <i class="bi bi-whatsapp"></i>
                        WhatsApp
                    </button>
                    <button class="share-btn share-telegram" onclick="wishlistShare.shareVia('telegram', '${url}', ${count})">
                        <i class="bi bi-telegram"></i>
                        Telegram
                    </button>
                    <button class="share-btn share-vk" onclick="wishlistShare.shareVia('vk', '${url}', ${count})">
                        VK
                    </button>
                    <button class="share-btn share-email" onclick="wishlistShare.shareVia('email', '${url}', ${count})">
                        <i class="bi bi-envelope"></i>
                        Email
                    </button>
                </div>

                <div class="share-qr">
                    <button class="btn-generate-qr" onclick="wishlistShare.generateQR('${url}')">
                        <i class="bi bi-qr-code"></i>
                        Создать QR-код
                    </button>
                    <div id="qrCodeContainer"></div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Закрытие по клику вне модалки
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    /**
     * Копировать ссылку в буфер обмена
     */
    function copyLink(url) {
        const input = document.getElementById('shareUrlInput');
        input.select();
        input.setSelectionRange(0, 99999); // Для мобильных

        try {
            document.execCommand('copy');
            showCopySuccess();
        } catch (err) {
            // Fallback для современных браузеров
            navigator.clipboard.writeText(url).then(() => {
                showCopySuccess();
            }).catch(err => {
                alert('Не удалось скопировать ссылку');
            });
        }
    }

    /**
     * Показать успешное копирование
     */
    function showCopySuccess() {
        const btn = document.querySelector('.btn-copy');
        const originalHTML = btn.innerHTML;
        
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Скопировано!';
        btn.style.background = '#10b981';
        btn.style.color = '#fff';
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.style.background = '';
            btn.style.color = '';
        }, 2000);
    }

    /**
     * Поделиться через соц. сеть
     */
    function shareVia(platform, url, count) {
        const text = `Посмотрите мои любимые товары (${count} ${pluralize(count, 'товар', 'товара', 'товаров')})!`;
        const encodedUrl = encodeURIComponent(url);
        const encodedText = encodeURIComponent(text);
        
        let shareUrl;
        
        switch(platform) {
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodedText}%20${encodedUrl}`;
                break;
            case 'telegram':
                shareUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`;
                break;
            case 'vk':
                shareUrl = `https://vk.com/share.php?url=${encodedUrl}&title=${encodedText}`;
                break;
            case 'email':
                shareUrl = `mailto:?subject=${encodedText}&body=${encodedUrl}`;
                break;
            default:
                return;
        }
        
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }

    /**
     * Генерировать QR-код
     */
    function generateQR(url) {
        const container = document.getElementById('qrCodeContainer');
        const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(url)}`;
        
        container.innerHTML = `
            <div class="qr-code">
                <img src="${qrUrl}" alt="QR Code" style="max-width:200px;border-radius:8px">
                <p style="margin-top:0.5rem;font-size:0.875rem;color:#666">Отсканируйте для открытия</p>
            </div>
        `;
    }

    /**
     * Pluralize helper
     */
    function pluralize(number, one, two, five) {
        let n = Math.abs(number);
        n %= 100;
        if (n >= 5 && n <= 20) {
            return five;
        }
        n %= 10;
        if (n === 1) {
            return one;
        }
        if (n >= 2 && n <= 4) {
            return two;
        }
        return five;
    }

    // Экспортируем API
    window.wishlistShare = {
        share: shareWishlist,
        copyLink: copyLink,
        shareVia: shareVia,
        generateQR: generateQR
    };

})();
