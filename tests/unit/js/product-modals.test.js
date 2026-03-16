/**
 * Unit-тесты для product-modals.js
 * Запуск: npm test или jest tests/unit/js/product-modals.test.js
 */

// Мокаем DOM окружение
document.body.innerHTML = `
    <!-- Модалка быстрого заказа -->
    <div id="quickOrderModal" style="display: none;">
        <form id="quickOrderForm" data-product-id="123">
            <input type="text" name="name" />
            <input type="tel" name="phone" />
            <button type="submit" id="quickOrderSubmitBtn">Отправить</button>
        </form>
    </div>
    
    <!-- Модалка таблицы размеров -->
    <div id="sizeTableModal" style="display: none;"></div>
    
    <!-- Кнопки размеров -->
    <div class="size-options">
        <button class="size-option" data-size="42">42</button>
        <button class="size-option disabled" data-size="43">43</button>
        <button class="size-option" data-size="44">44</button>
    </div>
    
    <input type="hidden" id="selectedSize" value="" />
    
    <meta name="csrf-token" content="test-csrf-token" />
`;

// Подключаем тестируемый модуль
require('../../../web/js/product-modals.js');

describe('Product Modals - Quick Order Modal', () => {
    
    beforeEach(() => {
        // Сбрасываем состояние перед каждым тестом
        document.getElementById('quickOrderModal').style.display = 'none';
        document.body.style.overflow = '';
    });
    
    test('openQuickOrderModal открывает модалку', () => {
        window.openQuickOrderModal();
        
        const modal = document.getElementById('quickOrderModal');
        expect(modal.style.display).toBe('flex');
        expect(document.body.style.overflow).toBe('hidden');
    });
    
    test('closeQuickOrderModal закрывает модалку', () => {
        // Открываем модалку
        const modal = document.getElementById('quickOrderModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Закрываем
        window.closeQuickOrderModal();
        
        expect(modal.style.display).toBe('none');
        expect(document.body.style.overflow).toBe('');
    });
    
    test('closeQuickOrderModal сбрасывает форму', () => {
        const form = document.getElementById('quickOrderForm');
        const nameInput = form.querySelector('input[name="name"]');
        const phoneInput = form.querySelector('input[name="phone"]');
        
        // Заполняем форму
        nameInput.value = 'Test User';
        phoneInput.value = '+375291234567';
        
        // Закрываем модалку
        window.closeQuickOrderModal();
        
        // Проверяем, что форма сброшена
        expect(nameInput.value).toBe('');
        expect(phoneInput.value).toBe('');
    });
    
    test('submitQuickOrder отправляет данные с product_id', async () => {
        // Мокаем fetch
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ success: true })
            })
        );
        
        const form = document.getElementById('quickOrderForm');
        const event = new Event('submit', { bubbles: true, cancelable: true });
        
        // Заполняем форму
        form.querySelector('input[name="name"]').value = 'Test User';
        form.querySelector('input[name="phone"]').value = '+375291234567';
        
        // Отправляем
        await window.submitQuickOrder(event);
        
        // Проверяем, что fetch был вызван
        expect(global.fetch).toHaveBeenCalledWith(
            '/catalog/quick-order',
            expect.objectContaining({
                method: 'POST',
                headers: expect.objectContaining({
                    'X-Requested-With': 'XMLHttpRequest'
                })
            })
        );
        
        // Проверяем, что FormData содержит product_id
        const callArgs = global.fetch.mock.calls[0][1];
        const formData = callArgs.body;
        expect(formData.get('product_id')).toBe('123');
    });
    
    test('submitQuickOrder показывает ошибку при неудаче', async () => {
        // Мокаем fetch с ошибкой
        global.fetch = jest.fn(() =>
            Promise.resolve({
                json: () => Promise.resolve({ success: false, message: 'Test error' })
            })
        );
        
        // Мокаем alert
        global.alert = jest.fn();
        
        const form = document.getElementById('quickOrderForm');
        const event = new Event('submit', { bubbles: true, cancelable: true });
        
        await window.submitQuickOrder(event);
        
        // Проверяем, что показана ошибка
        const submitBtn = document.getElementById('quickOrderSubmitBtn');
        expect(submitBtn.classList.contains('error')).toBe(true);
    });
});

describe('Product Modals - Size Table Modal', () => {
    
    beforeEach(() => {
        document.getElementById('sizeTableModal').style.display = 'none';
        document.body.style.overflow = '';
    });
    
    test('openSizeTableModal открывает модалку', () => {
        window.openSizeTableModal();
        
        const modal = document.getElementById('sizeTableModal');
        expect(modal.style.display).toBe('flex');
        expect(document.body.style.overflow).toBe('hidden');
    });
    
    test('closeSizeTableModal закрывает модалку', () => {
        const modal = document.getElementById('sizeTableModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        window.closeSizeTableModal();
        
        expect(modal.style.display).toBe('none');
        expect(document.body.style.overflow).toBe('');
    });
    
    test('selectSizeFromTable выбирает доступный размер', () => {
        // Мокаем NotificationManager
        global.NotificationManager = {
            success: jest.fn(),
            warning: jest.fn()
        };
        
        // Добавляем текст в кнопки
        const buttons = document.querySelectorAll('.size-option');
        buttons[0].textContent = '42';
        buttons[1].textContent = '43';
        buttons[2].textContent = '44';
        
        // Выбираем размер
        window.selectSizeFromTable('42');
        
        // Проверяем, что размер выбран
        const selectedButton = document.querySelector('.size-option.selected');
        expect(selectedButton.textContent.trim()).toBe('42');
        
        // Проверяем, что скрытое поле обновлено
        const sizeInput = document.getElementById('selectedSize');
        expect(sizeInput.value).toBe('42');
        
        // Проверяем, что показано уведомление
        expect(NotificationManager.success).toHaveBeenCalledWith('Выбран размер: 42');
    });
    
    test('selectSizeFromTable не выбирает недоступный размер', () => {
        global.NotificationManager = {
            success: jest.fn(),
            warning: jest.fn()
        };
        
        const buttons = document.querySelectorAll('.size-option');
        buttons[0].textContent = '42';
        buttons[1].textContent = '43';
        buttons[2].textContent = '44';
        
        // Пытаемся выбрать недоступный размер
        window.selectSizeFromTable('43');
        
        // Проверяем, что размер не выбран
        const selectedButton = document.querySelector('.size-option.selected');
        expect(selectedButton).toBeNull();
        
        // Проверяем, что показано предупреждение
        expect(NotificationManager.warning).toHaveBeenCalledWith('Этот размер недоступен');
    });
});

describe('Product Modals - Event Handlers', () => {
    
    test('ESC закрывает открытую модалку быстрого заказа', () => {
        // Открываем модалку
        window.openQuickOrderModal();
        
        // Эмулируем нажатие ESC
        const event = new KeyboardEvent('keydown', { key: 'Escape' });
        document.dispatchEvent(event);
        
        // Проверяем, что модалка закрыта
        const modal = document.getElementById('quickOrderModal');
        expect(modal.style.display).toBe('none');
    });
    
    test('ESC закрывает открытую модалку таблицы размеров', () => {
        // Открываем модалку
        window.openSizeTableModal();
        
        // Эмулируем нажатие ESC
        const event = new KeyboardEvent('keydown', { key: 'Escape' });
        document.dispatchEvent(event);
        
        // Проверяем, что модалка закрыта
        const modal = document.getElementById('sizeTableModal');
        expect(modal.style.display).toBe('none');
    });
    
    test('Клик на фон закрывает модалку быстрого заказа', () => {
        const modal = document.getElementById('quickOrderModal');
        window.openQuickOrderModal();
        
        // Эмулируем клик на фон (target === modal)
        const event = new MouseEvent('click', { bubbles: true });
        Object.defineProperty(event, 'target', { value: modal, enumerable: true });
        modal.dispatchEvent(event);
        
        // Проверяем, что модалка закрыта
        expect(modal.style.display).toBe('none');
    });
    
    test('Клик внутри модалки не закрывает её', () => {
        const modal = document.getElementById('quickOrderModal');
        const form = document.getElementById('quickOrderForm');
        window.openQuickOrderModal();
        
        // Эмулируем клик внутри модалки (target !== modal)
        const event = new MouseEvent('click', { bubbles: true });
        Object.defineProperty(event, 'target', { value: form, enumerable: true });
        modal.dispatchEvent(event);
        
        // Проверяем, что модалка всё ещё открыта
        expect(modal.style.display).toBe('flex');
    });
});

describe('Product Modals - Global Functions', () => {
    
    test('Все функции доступны в window', () => {
        expect(typeof window.openQuickOrderModal).toBe('function');
        expect(typeof window.closeQuickOrderModal).toBe('function');
        expect(typeof window.submitQuickOrder).toBe('function');
        expect(typeof window.openSizeTableModal).toBe('function');
        expect(typeof window.closeSizeTableModal).toBe('function');
        expect(typeof window.selectSizeFromTable).toBe('function');
    });
});
