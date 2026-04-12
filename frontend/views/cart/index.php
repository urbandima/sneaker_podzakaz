<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\CartAsset;

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Cart[] $items */
/** @var float $total */
/** @var app\backend\modules\catalog\models\Customer|null $customer */

$this->title = 'Корзина';
$this->params['breadcrumbs'][] = $this->title;

// Подключаем AssetBundle для корзины (все стили автоматически с версионированием)
CartAsset::register($this);
?>

<div class="cart-page cart-blur-surface">
    <div class="container">
        <h1><i class="bi bi-cart3"></i> Корзина</h1>
        
        <?php if (empty($items)): ?>
            <div class="cart-empty">
                <i class="bi bi-cart-x"></i>
                <h2>Корзина пуста</h2>
                <p>Добавьте товары из каталога</p>
                <a href="<?= Url::to(['/catalog']) ?>" class="btn-catalog">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item" data-cart-id="<?= $item->id ?>">
                            <div class="item-image">
                                <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>" alt="<?= Html::encode($item->product->name) ?>">
                            </div>
                            
                            <div class="item-info">
                                <div class="item-brand"><?= Html::encode($item->product->brand->name ?? '') ?></div>
                                <h3 class="item-name"><?= Html::encode($item->product->name) ?></h3>
                                
                                <?php if ($item->size): ?>
                                    <div class="item-size">Размер: <?= Html::encode($item->size) ?></div>
                                <?php endif; ?>
                                
                                <?php if ($item->color): ?>
                                    <div class="item-color">Цвет: <?= Html::encode($item->color) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-quantity">
                                <button onclick="updateCartItem(<?= $item->id ?>, <?= $item->quantity - 1 ?>)" <?= $item->quantity <= 1 ? 'disabled' : '' ?>>
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" value="<?= $item->quantity ?>" min="1" max="99" 
                                       onchange="updateCartItem(<?= $item->id ?>, this.value)">
                                <button onclick="updateCartItem(<?= $item->id ?>, <?= $item->quantity + 1 ?>)">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            
                            <div class="item-price">
                                <span class="subtotal"><?= Yii::$app->formatter->asCurrency($item->getSubtotal(), 'BYN') ?></span>
                                <span class="price-per-item"><?= Yii::$app->formatter->asCurrency($item->price, 'BYN') ?> × <?= $item->quantity ?></span>
                            </div>
                            
                            <button class="item-remove" onclick="removeCartItem(<?= $item->id ?>)">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Информация о ценах -->
                <div class="cart-summary">
                    <!-- Блок промокода -->
                    <div class="promo-code-section">
                        <h4><i class="bi bi-ticket-perforated"></i> Есть промокод?</h4>
                        <div class="promo-input-group">
                            <input type="text" id="promoCodeInput" placeholder="Введите код" class="promo-input">
                            <button class="btn-promo-apply" onclick="applyPromoCode()">
                                Применить
                            </button>
                        </div>
                        <div id="promoApplied" class="promo-applied" style="display: none;">
                            <div class="promo-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <span id="promoCodeText"></span>
                            </div>
                            <div class="promo-discount">
                                Скидка: <span id="promoDiscountAmount"></span>
                            </div>
                            <button class="promo-remove" onclick="removePromoCode()">
                                <i class="bi bi-x"></i> Удалить
                            </button>
                        </div>
                        <div id="promoError" class="promo-error" style="display: none;"></div>
                    </div>
                    
                    <?php if ($customer): ?>
                    <!-- Блок баллов лояльности -->
                    <div class="loyalty-points-section">
                        <h4><i class="bi bi-star-fill"></i> Баллы лояльности</h4>
                        <div class="points-balance">
                            Ваш баланс: <strong id="loyaltyBalance">0</strong> баллов
                        </div>
                        <div class="points-info">
                            Можно оплатить до 50% заказа
                        </div>
                        <div class="points-slider-container">
                            <input type="range" id="pointsSlider" min="0" max="0" value="0" 
                                   class="points-slider" oninput="updatePointsRedeem(this.value)">
                            <div class="points-slider-labels">
                                <span>0</span>
                                <span id="maxPointsLabel">0</span>
                            </div>
                        </div>
                        <div class="points-redeem-info">
                            <span>Скидка: </span>
                            <strong id="pointsDiscount">0 BYN</strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cart-summary-info">
                        <h2>Итого</h2>
                        
                        <div class="summary-row">
                            <span>Товары (<?= count($items) ?>):</span>
                            <span class="cart-total" id="productsTotal"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                        </div>
                        
                        <div class="summary-row discount-row" id="promoDiscountRow" style="display: none;">
                            <span>Скидка по промокоду:</span>
                            <span class="discount-value" id="promoDiscountValue">-0 BYN</span>
                        </div>
                        
                        <div class="summary-row discount-row" id="pointsDiscountRow" style="display: none;">
                            <span>Оплата баллами:</span>
                            <span class="discount-value" id="pointsDiscountValue">-0 BYN</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span id="deliveryCost"><?= $total >= 100 ? 'Бесплатно' : Yii::$app->formatter->asCurrency(10, 'BYN') ?></span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Всего:</span>
                            <span class="cart-total" id="finalTotal"><?= Yii::$app->formatter->asCurrency($total >= 100 ? $total : $total + 10, 'BYN') ?></span>
                        </div>
                        
                        <?php if ($total < 100): ?>
                            <div class="delivery-info">
                                <i class="bi bi-truck"></i>
                                До бесплатной доставки: <span id="toFreeDelivery"><?= Yii::$app->formatter->asCurrency(100 - $total, 'BYN') ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Кнопки действий -->
                    <div class="cart-summary-actions">
                        <a href="<?= Url::to(['/checkout']) ?>" class="btn-checkout">
                            <i class="bi bi-check-circle"></i>
                            Оформить заказ
                        </a>
                        
                        <a href="<?= Url::to(['/catalog']) ?>" class="btn-continue">Продолжить покупки</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Checkout Modal -->
<div class="checkout-modal" id="checkoutModal">
    <div class="modal-overlay" onclick="closeCheckoutModal()"></div>
    <div class="modal-content">
        <button class="modal-close" onclick="closeCheckoutModal()"><i class="bi bi-x-lg"></i></button>
        
        <h2><i class="bi bi-bag-check"></i> Оформление заказа</h2>
        
        <form id="checkoutForm" onsubmit="submitOrder(event)">
            <?php if ($customer): ?>
                <input type="hidden" name="customer_id" value="<?= $customer->id ?>">
            <?php endif; ?>
            
            <div class="checkout-grid">
                <!-- Левая колонка -->
                <div class="checkout-left">
                    <?php if ($customer): ?>
                        <!-- Переключатель режимов для авторизованных -->
                        <div class="form-mode-switcher">
                            <button type="button" class="mode-btn active" onclick="switchFormMode('profile')">
                                <i class="bi bi-person-check"></i> Данные профиля
                            </button>
                            <button type="button" class="mode-btn" onclick="switchFormMode('custom')">
                                <i class="bi bi-pencil"></i> Другие данные
                            </button>
                        </div>
                        
                        <!-- Сохранённые адреса -->
                        <div class="saved-addresses-section" id="savedAddressesSection">
                            <h3>Выберите адрес доставки</h3>
                            <div class="saved-addresses">
                                <?php if ($customer && isset($customer->default_address) && $customer->default_address): ?>
                                    <label class="saved-address-card active">
                                        <input type="radio" name="saved_address" value="default" checked>
                                        <div class="address-content">
                                            <div class="address-header">
                                                <i class="bi bi-house-door"></i>
                                                <strong>Основной адрес</strong>
                                            </div>
                                            <div class="address-details">
                                                <?= Html::encode($customer->default_country ?? 'Беларусь') ?>, 
                                                <?= Html::encode($customer->default_city ?? '') ?><br>
                                                <?= Html::encode($customer->default_address) ?>
                                                <?php if (isset($customer->default_postal_code) && $customer->default_postal_code): ?>
                                                    , <?= Html::encode($customer->default_postal_code) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                <?php endif; ?>
                                <label class="saved-address-card">
                                    <input type="radio" name="saved_address" value="new">
                                    <div class="address-content">
                                        <div class="address-header">
                                            <i class="bi bi-plus-circle"></i>
                                            <strong>Новый адрес</strong>
                                        </div>
                                        <div class="address-details">Указать другой адрес доставки</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-section" id="contactDataSection">
                        <h3>Контактные данные</h3>
                        <div class="form-group">
                            <label>ФИО *</label>
                            <input type="text" name="name" id="nameField" required placeholder="Иванов Иван Иванович" 
                                   value="<?= $customer ? Html::encode($customer->getFullName()) : '' ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Телефон *</label>
                                <input type="tel" name="phone" id="phoneField" required placeholder="+375 (__)___-__-__" 
                                       value="<?= $customer ? Html::encode($customer->phone) : '' ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" id="emailField" placeholder="email@example.com" 
                                       value="<?= $customer ? Html::encode($customer->email) : '' ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Доставка</h3>
                        
                        <!-- Выбор страны -->
                        <div class="country-tabs">
                            <button type="button" class="country-tab active" onclick="selectCountry('belarus', event)">
                                <i class="bi bi-geo-alt-fill"></i> Беларусь
                            </button>
                            <button type="button" class="country-tab" onclick="selectCountry('russia', event)">
                                <i class="bi bi-geo-alt-fill"></i> Россия
                            </button>
                        </div>
                        
                        <input type="hidden" name="country" id="selectedCountry" value="belarus">
                        
                        <!-- Способы доставки для Беларуси -->
                        <div class="delivery-options" id="deliveryBelarus">
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="pickup_minsk" checked>
                                <div class="option-content">
                                    <div>
                                        <strong>Самовывоз</strong>
                                        <small>Минск, пр.Победителей 5 (около Альфа-Банк)</small>
                                    </div>
                                    <span class="price-tag">Бесплатно</span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="courier_minsk">
                                <div class="option-content">
                                    <div>
                                        <strong>Курьер Минск</strong>
                                        <small>Доставка по городу</small>
                                    </div>
                                    <span class="price-tag"><?= Yii::$app->formatter->asCurrency(10, 'BYN') ?></span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="europochta">
                                <div class="option-content">
                                    <div>
                                        <strong>Европочта</strong>
                                        <small>Доставка по Беларуси</small>
                                    </div>
                                    <span class="price-tag"><?= Yii::$app->formatter->asCurrency(5, 'BYN') ?></span>
                                </div>
                            </label>
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="belpochta">
                                <div class="option-content">
                                    <div>
                                        <strong>Белпочта</strong>
                                        <small>Доставка по Беларуси</small>
                                    </div>
                                    <span class="price-tag"><?= Yii::$app->formatter->asCurrency(4, 'BYN') ?></span>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Способы доставки для России -->
                        <div class="delivery-options" id="deliveryRussia" style="display: none;">
                            <label class="delivery-option">
                                <input type="radio" name="delivery" value="sdek">
                                <div class="option-content">
                                    <div>
                                        <strong>СДЭК</strong>
                                        <small>Доставка по России</small>
                                    </div>
                                    <span class="price-tag">Рассчитывается по тарифам СДЭК</span>
                                </div>
                            </label>
                        </div>
                        
                        <div class="form-group" id="addressFieldGroup" style="margin-top: 1rem;<?= $customer && isset($customer->default_address) && $customer->default_address ? ' display: none;' : '' ?>">
                            <label id="addressLabel">Адрес доставки *</label>
                            <textarea name="address" id="addressField" rows="2" placeholder="Укажите адрес доставки"></textarea>
                        </div>
                    </div>
                    
                    <?php if ($customer): ?>
                        <!-- Опция сохранения данных -->
                        <div class="form-section" id="saveDataSection" style="display: none;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="save_to_profile" id="saveToProfile">
                                <span>Сохранить эти данные в профиль</span>
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h3>Комментарий к заказу</h3>
                        <div class="form-group">
                            <textarea name="comment" rows="2" placeholder="Дополнительные пожелания"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Правая колонка - Итого -->
                <div class="checkout-right">
                    <div class="modal-summary">
                        <h3>Ваш заказ</h3>
                        <div class="summary-row">
                            <span>Товары:</span>
                            <span class="goods-cost"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Доставка:</span>
                            <span class="delivery-cost">Бесплатно</span>
                        </div>
                        <div class="summary-total">
                            <span>Итого:</span>
                            <span class="total-cost"><?= Yii::$app->formatter->asCurrency($total, 'BYN') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Кнопка подтверждения - закреплена внизу -->
            <div class="checkout-footer">
                <button type="submit" class="btn-submit-order">
                    <i class="bi bi-check-circle-fill"></i>
                    Подтвердить заказ
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/utils.js"></script>
<script src="/js/cart.js"></script>
<script src="/js/cart-mobile.js"></script>
<script>
function openCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-blur-active');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.remove('active');
    document.body.style.overflow = '';
    document.body.classList.remove('modal-blur-active');
}

// Переключение между странами
function selectCountry(country, event) {
    event.preventDefault();
    
    // Обновляем активную вкладку
    document.querySelectorAll('.country-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.currentTarget.classList.add('active');
    
    // Обновляем скрытое поле
    document.getElementById('selectedCountry').value = country;
    
    // Показываем/скрываем способы доставки
    const belarusDelivery = document.getElementById('deliveryBelarus');
    const russiaDelivery = document.getElementById('deliveryRussia');
    const addressField = document.getElementById('addressField');
    const addressLabel = document.getElementById('addressLabel');
    
    if (country === 'belarus') {
        belarusDelivery.style.display = 'grid';
        russiaDelivery.style.display = 'none';
        
        // Устанавливаем первый вариант для Беларуси
        const firstOption = belarusDelivery.querySelector('input[type="radio"]');
        if (firstOption) {
            firstOption.checked = true;
            updateDeliveryCost(firstOption.value);
        }
        
        addressField.placeholder = 'Укажите адрес доставки';
        addressLabel.textContent = 'Адрес доставки *';
        addressField.required = true;
    } else {
        belarusDelivery.style.display = 'none';
        russiaDelivery.style.display = 'grid';
        
        // Устанавливаем СДЭК для России
        const sdekOption = russiaDelivery.querySelector('input[type="radio"]');
        if (sdekOption) {
            sdekOption.checked = true;
            updateDeliveryCost(sdekOption.value);
        }
        
        addressField.placeholder = 'Город, улица, дом, квартира';
        addressLabel.textContent = 'Адрес пункта выдачи СДЭК *';
        addressField.required = true;
    }
}

// Обновление стоимости доставки
function updateDeliveryCost(deliveryType) {
    const deliveryCostEl = document.querySelector('.delivery-cost');
    const totalCostEl = document.querySelector('.total-cost');
    const goodsCostEl = document.querySelector('.goods-cost');
    const addressField = document.getElementById('addressField');
    const addressLabel = document.getElementById('addressLabel');
    const addressGroup = addressField?.closest('.form-group');
    
    if (!deliveryCostEl || !totalCostEl || !goodsCostEl) return;
    
    // Парсим белорусский формат "X,XX Br" -> число
    const goodsCost = parseFloat(goodsCostEl.textContent.replace(',', '.').replace(/[^\d.]/g, ''));
    let deliveryCost = 0;
    let deliveryCostText = 'Бесплатно';
    
    // Определяем стоимость доставки
    switch(deliveryType) {
        case 'pickup_minsk':
            deliveryCost = 0;
            deliveryCostText = 'Бесплатно';
            // Для самовывоза скрываем поле адреса
            if (addressGroup) addressGroup.style.display = 'none';
            if (addressField) addressField.required = false;
            break;
        case 'courier_minsk':
            deliveryCost = 10;
            deliveryCostText = '10,00 Br';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес доставки в Минске *';
            break;
        case 'europochta':
            deliveryCost = 5;
            deliveryCostText = '5,00 Br';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес пункта выдачи или доставки *';
            break;
        case 'belpochta':
            deliveryCost = 4;
            deliveryCostText = '4,00 Br';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес отделения почты *';
            break;
        case 'sdek':
            deliveryCost = 0;
            deliveryCostText = 'Рассчитывается по тарифам СДЭК';
            if (addressGroup) addressGroup.style.display = 'block';
            if (addressField) addressField.required = true;
            if (addressLabel) addressLabel.textContent = 'Адрес пункта выдачи СДЭК *';
            break;
    }
    
    // Обновляем UI
    deliveryCostEl.textContent = deliveryCostText;
    
    // Обновляем итоговую сумму (только для фиксированных тарифов)
    if (deliveryType !== 'sdek') {
        const total = goodsCost + deliveryCost;
        totalCostEl.textContent = total.toFixed(2).replace('.', ',') + ' Br';
    } else {
        totalCostEl.textContent = 'Уточняется';
    }
}

// Отслеживаем изменение способа доставки
document.addEventListener('DOMContentLoaded', function() {
    const deliveryOptions = document.querySelectorAll('input[name="delivery"]');
    deliveryOptions.forEach(option => {
        option.addEventListener('change', function() {
            updateDeliveryCost(this.value);
        });
    });
});

// Переключение режима формы (профиль / другие данные)
function switchFormMode(mode) {
    const modeBtns = document.querySelectorAll('.mode-btn');
    const savedAddressesSection = document.getElementById('savedAddressesSection');
    const contactDataSection = document.getElementById('contactDataSection');
    const addressFieldGroup = document.getElementById('addressFieldGroup');
    const saveDataSection = document.getElementById('saveDataSection');
    
    const nameField = document.getElementById('nameField');
    const phoneField = document.getElementById('phoneField');
    const emailField = document.getElementById('emailField');
    
    // Обновляем активную кнопку
    modeBtns.forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    if (mode === 'profile') {
        // Режим "Данные профиля"
        if (savedAddressesSection) savedAddressesSection.style.display = 'block';
        if (contactDataSection) contactDataSection.style.display = 'none';
        if (saveDataSection) saveDataSection.style.display = 'none';
        
        // Проверяем выбранный адрес
        const defaultAddressRadio = document.querySelector('input[name="saved_address"][value="default"]');
        if (defaultAddressRadio && defaultAddressRadio.checked) {
            if (addressFieldGroup) addressFieldGroup.style.display = 'none';
        }
    } else {
        // Режим "Другие данные"
        if (savedAddressesSection) savedAddressesSection.style.display = 'none';
        if (contactDataSection) contactDataSection.style.display = 'block';
        if (addressFieldGroup) addressFieldGroup.style.display = 'block';
        if (saveDataSection) saveDataSection.style.display = 'block';
        
        // Разблокируем поля для редактирования
        if (nameField) nameField.disabled = false;
        if (phoneField) phoneField.disabled = false;
        if (emailField) emailField.disabled = false;
    }
}

// Обработка выбора сохранённого адреса
document.addEventListener('DOMContentLoaded', function() {
    const savedAddressRadios = document.querySelectorAll('input[name="saved_address"]');
    const addressFieldGroup = document.getElementById('addressFieldGroup');
    const addressField = document.getElementById('addressField');
    
    savedAddressRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            // Обновляем активную карточку
            document.querySelectorAll('.saved-address-card').forEach(card => {
                card.classList.remove('active');
            });
            this.closest('.saved-address-card').classList.add('active');
            
            if (this.value === 'default') {
                // Используем сохранённый адрес - скрываем поле ввода
                if (addressFieldGroup) addressFieldGroup.style.display = 'none';
                if (addressField) addressField.required = false;
            } else {
                // Новый адрес - показываем поле ввода
                if (addressFieldGroup) addressFieldGroup.style.display = 'block';
                if (addressField) addressField.required = true;
            }
        });
    });
});

function submitOrder(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('_csrf', csrfToken);
    }
    
    // Если используется режим профиля и выбран сохранённый адрес
    const savedAddressRadio = document.querySelector('input[name="saved_address"]:checked');
    if (savedAddressRadio && savedAddressRadio.value === 'default') {
        // Добавляем флаг использования сохранённого адреса
        formData.append('use_saved_address', '1');
    }
    
    // Показываем загрузку
    const btn = e.target.querySelector('.btn-submit-order');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Оформляем...';
    btn.disabled = true;
    
    // Отправляем заказ
    fetch('/order/create', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(async response => {
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await response.text();
            throw new Error(text || 'Сервер вернул неожиданный ответ.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeCheckoutModal();
            alert('Заказ успешно оформлен! Наш менеджер свяжется с вами в ближайшее время.');
            window.location.href = '/order/success?token=' + encodeURIComponent(data.token);
        } else {
            alert('Ошибка: ' + (data.message || 'Попробуйте позже'));
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error('Order submission error:', err);
        alert(err.message || 'Ошибка соединения. Попробуйте позже.');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// removeCartItem уже определен в cart.js - используем его
// Эта функция обновляет UI после изменений корзины
function updateCartTotals(total, count) {
    const cartTotals = document.querySelectorAll('.cart-total');
    cartTotals.forEach(el => {
        el.textContent = total.toFixed(2).replace('.', ',') + ' Br';
    });
    
    // Обновляем сумму товаров в модальном окне
    const goodsCostEl = document.querySelector('.goods-cost');
    if (goodsCostEl) {
        goodsCostEl.textContent = total.toFixed(2).replace('.', ',') + ' Br';
    }
    
    // Пересчитываем итоговую сумму с учетом доставки
    const deliveryRadio = document.querySelector('input[name="delivery"]:checked');
    if (deliveryRadio) {
        updateDeliveryCost(deliveryRadio.value);
    }
    
    // Обновляем badge в header
    const badge = document.getElementById('cartCount');
    if (badge) badge.textContent = count;
}
</script>
