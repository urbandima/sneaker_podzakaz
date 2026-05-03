<?php
/** @var yii\web\View $this */
/** @var array $items                  Позиции корзины */
/** @var float $total                  Итого по товарам */
/** @var \app\backend\modules\account\models\Customer|null $customer */
/** @var array $paymentMethods         Способы оплаты из DB */
/** @var array $shippingMethods        Способы доставки из DB */
/** @var array $europochtaPoints       Пункты выдачи Европочты из DB */
/** @var string $pickupAddress         Адрес самовывоза из DB */
/** @var float  $freeDeliveryThreshold Порог бесплатной доставки из DB */

use yii\helpers\Html;
use yii\helpers\Url;
use app\frontend\assets\AppAsset;

AppAsset::register($this);

$this->title = 'Оформление заказа';

$createUrl = Url::to(['/order/create']);
$csrfToken = Yii::$app->request->csrfToken;

// Russian plural for "товар"
$cnt = count($items);
if ($cnt % 10 === 1 && $cnt % 100 !== 11) {
    $itemWord = 'товар';
} elseif ($cnt % 10 >= 2 && $cnt % 10 <= 4 && ($cnt % 100 < 10 || $cnt % 100 >= 20)) {
    $itemWord = 'товара';
} else {
    $itemWord = 'товаров';
}
?>

<div class="checkout-page cart-blur-surface">
    <div class="container">
        <h1><i class="bi bi-bag-check"></i> Оформление заказа</h1>

        <!-- Mobile: collapsible order summary (hidden on desktop) -->
        <div class="mobile-summary-toggle" id="mobileSummaryToggle">
            <div class="mobile-summary-header" onclick="toggleMobileSummary()">
                <div class="mobile-summary-header-left">
                    <i class="bi bi-bag"></i>
                    Ваш заказ (<?= $cnt ?> <?= $itemWord ?>)
                </div>
                <div class="mobile-summary-header-right">
                    <span class="mobile-summary-price" id="mobileFinalTotal"><?= number_format($total, 2) ?> BYN</span>
                    <i class="bi bi-chevron-down mobile-toggle-icon"></i>
                </div>
            </div>
            <div class="mobile-summary-body">
                <?php foreach ($items as $item): ?>
                    <?php if (!$item->product) continue; ?>
                    <div class="summary-item">
                        <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>"
                             alt="<?= Html::encode($item->product->name) ?>"
                             class="summary-item-img">
                        <div class="summary-item-info">
                            <span class="summary-item-title"><?= Html::encode(mb_strimwidth($item->product->name, 0, 35, '…')) ?></span>
                            <?php if ($item->size): ?>
                                <span class="summary-item-meta">Размер: <?= Html::encode($item->size) ?></span>
                            <?php endif; ?>
                            <span class="summary-item-meta"><?= (int)$item->quantity ?> × <?= number_format($item->price, 2) ?> BYN</span>
                        </div>
                        <span class="summary-item-price"><?= number_format($item->price * $item->quantity, 2) ?> BYN</span>
                    </div>
                <?php endforeach; ?>
                <div class="summary-totals" style="margin-top:8px">
                    <div class="summary-row">
                        <span>Доставка:</span>
                        <span id="mobileDeliveryCost" class="delivery-cost">Бесплатно</span>
                    </div>
                    <div class="summary-total">
                        <span>Итого:</span>
                        <span id="mobileFinalTotal2"><?= number_format($total, 2) ?> BYN</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="checkout-layout">
            <!-- Левая колонка: контакты + доставка + оплата + комментарий -->
            <div class="checkout-left">

                <!-- Контактные данные — 3 колонки на одной строке -->
                <div class="checkout-section">
                    <h2><i class="bi bi-person"></i> Контактные данные</h2>
                    <div class="checkout-contact-grid">
                        <div class="form-group">
                            <label for="field-name">ФИО <span class="text-danger">*</span></label>
                            <input type="text" id="field-name" name="name" class="form-control"
                                   placeholder="Иванов Иван Иванович"
                                   value="<?= Html::encode($customer ? $customer->getFullName() : '') ?>"
                                   required maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="field-phone">Телефон <span class="text-danger">*</span></label>
                            <input type="tel" id="field-phone" name="phone" class="form-control"
                                   placeholder="+375 (__) ___-__-__"
                                   value="<?= Html::encode($customer?->phone ?? '') ?>"
                                   required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="field-email">Email</label>
                            <input type="email" id="field-email" name="email" class="form-control"
                                   placeholder="email@example.com"
                                   value="<?= Html::encode($customer?->email ?? '') ?>"
                                   maxlength="255">
                        </div>
                    </div>
                </div>

                <!-- Доставка -->
                <div class="checkout-section">
                    <h2><i class="bi bi-truck"></i> Доставка</h2>

                    <?php
                    $shippingMethods  = $shippingMethods  ?? [];
                    $europochtaPoints = $europochtaPoints ?? [];
                    // Single unified list — country toggle removed (CMP-19).
                    // The country field is set server-side via deliveryCountryMap below.
                    $firstShippingId = !empty($shippingMethods) ? $shippingMethods[0]['id'] : '';
                    // Map id → country so JS can sync the hidden country field on change.
                    $deliveryCountryMap = [];
                    foreach ($shippingMethods as $sm) {
                        $deliveryCountryMap[$sm['id']] = $sm['country'] ?? 'belarus';
                    }
                    ?>

                    <input type="hidden" name="country" id="selectedCountry" value="<?= Html::encode($deliveryCountryMap[$firstShippingId] ?? 'belarus') ?>">

                    <div class="choice-card-list shipping-options" id="deliveryMethods">
                        <?php foreach ($shippingMethods as $smIdx => $sm): ?>
                        <?php
                            $smPrice = (float)($sm['price'] ?? 0);
                            $smIcon  = Html::encode($sm['icon'] ?? 'truck');
                            $smId    = Html::encode($sm['id']);
                            $smName  = Html::encode($sm['name']);
                            $smDesc  = Html::encode($sm['description'] ?? '');
                            $smTime  = Html::encode($sm['delivery_time'] ?? '');
                            $smPriceLabel = isset($sm['price_label'])
                                ? Html::encode($sm['price_label'])
                                : ($smPrice > 0 ? $smPrice . ' BYN' : 'Бесплатно');
                            $isFree = ($smPrice <= 0 && stripos($sm['price_label'] ?? '', 'тариф') === false);
                            $badgeClass = $isFree ? 'badge-success' : 'badge-light';
                        ?>
                        <label class="choice-card shipping-option">
                            <input type="radio" name="delivery" value="<?= $smId ?>"
                                   data-price="<?= $smPrice ?>"
                                   data-country="<?= Html::encode($sm['country'] ?? 'belarus') ?>"
                                   data-price-label="<?= $smPriceLabel ?>"
                                   <?= $smIdx === 0 ? 'checked' : '' ?>
                                   onchange="updateDelivery(<?= $smPrice ?>, '<?= Html::encode($sm['id']) ?>')">
                            <div class="choice-card-body">
                                <div class="choice-card-icon"><i class="bi bi-<?= $smIcon ?>"></i></div>
                                <div class="choice-card-text">
                                    <span class="choice-card-title"><?= $smName ?></span>
                                    <?php if ($smDesc): ?><span class="choice-card-desc"><?= $smDesc ?></span><?php endif; ?>
                                    <?php if ($smTime): ?><span class="choice-card-eta"><?= $smTime ?></span><?php endif; ?>
                                </div>
                                <span class="badge <?= $badgeClass ?>"><?= $smPriceLabel ?></span>
                                <span class="choice-card-radio"></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- Европочта ПВЗ — показывается при выборе europochta -->
                    <style>
                    .pvz-option{padding:9px 14px;cursor:pointer;font-size:13px;border-bottom:1px solid #f3f4f6;line-height:1.4}
                    .pvz-option:last-child{border-bottom:none}
                    .pvz-option:hover,.pvz-option:active{background:#f0f9ff;color:#0369a1}
                    .pvz-option strong{color:#1d4ed8}
                    </style>
                    <div class="pvz-select-wrap" id="europochtaPvz" style="display:none">
                        <label for="pvzSearch"><i class="bi bi-geo-alt-fill"></i> Пункт выдачи Европочты</label>
                        <div class="pvz-autocomplete-wrap" style="position:relative">
                            <input type="text" id="pvzSearch" class="pvz-search-input form-control"
                                   placeholder="Начните вводить город или адрес..." autocomplete="off"
                                   oninput="filterPvz(this.value)" onfocus="showPvzDropdown()" onblur="hidePvzDropdown()">
                            <div id="pvzDropdown" class="pvz-dropdown" style="display:none;position:absolute;left:0;right:0;top:100%;z-index:100;background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.1);max-height:240px;overflow-y:auto;"></div>
                        </div>
                        <div id="pvzSelected" style="display:none;margin-top:6px;padding:8px 12px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;font-size:13px;color:#166534">
                            <i class="bi bi-check-circle-fill"></i> <span id="pvzSelectedText"></span>
                            <button type="button" onclick="clearPvz()" style="float:right;background:none;border:none;color:#dc2626;cursor:pointer;font-size:12px">✕ Изменить</button>
                        </div>
                        <!-- Hidden field sent to server -->
                        <input type="hidden" id="pvzHidden" name="pvz_address" value="">
                        <?php
                        // Build flat JS array of all PVZ points for autocomplete
                        $pvzJs = [];
                        foreach ($europochtaPoints as $pt) {
                            $num  = $pt['num']  ?? '';
                            $city = $pt['city'] ?? '';
                            $name = $pt['name'] ?? '';
                            $addr = $pt['address'] ?? $pt['full'] ?? ($city . ', ' . $name);
                            $label = '№' . $num . ': ' . $city . ', ' . $name;
                            $value = '№' . $num . ': ' . $addr;
                            $pvzJs[] = ['num' => $num, 'label' => $label, 'value' => $value];
                        }
                        ?>
                        <script>
                        var _pvzPoints = <?= json_encode($pvzJs, JSON_UNESCAPED_UNICODE) ?>;

                        function _renderPvzDropdown(filtered, query) {
                            var dropdown = document.getElementById('pvzDropdown');
                            if (!filtered.length) {
                                dropdown.innerHTML = '<div style="padding:10px 14px;color:#9ca3af;font-size:13px">Ничего не найдено</div>';
                            } else {
                                var esc = query ? query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') : '';
                                dropdown.innerHTML = filtered.slice(0, 50).map(function(p){
                                    var display = p.label;
                                    if (esc) display = display.replace(new RegExp('(' + esc + ')', 'gi'), '<strong>$1</strong>');
                                    return '<div class="pvz-option" onmousedown="selectPvzItem(\'' + p.value.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\',\'' + p.label.replace(/\\/g,'\\\\').replace(/'/g,"\\'") + '\')">' + display + '</div>';
                                }).join('');
                            }
                            dropdown.style.display = 'block';
                        }

                        function filterPvz(q) {
                            var query = q.trim().toLowerCase();
                            var filtered = query
                                ? _pvzPoints.filter(function(p){ return p.label.toLowerCase().indexOf(query) !== -1; })
                                : _pvzPoints;
                            _renderPvzDropdown(filtered, query);
                        }

                        function selectPvzItem(value, label) {
                            document.getElementById('pvzHidden').value = value;
                            document.getElementById('pvzSearch').value = '';
                            document.getElementById('pvzSearch').style.display = 'none';
                            document.getElementById('pvzSelectedText').textContent = label;
                            document.getElementById('pvzSelected').style.display = 'block';
                            document.getElementById('pvzDropdown').style.display = 'none';
                            selectedPvz = value;
                        }

                        function clearPvz() {
                            document.getElementById('pvzHidden').value = '';
                            document.getElementById('pvzSearch').value = '';
                            document.getElementById('pvzSearch').style.display = '';
                            document.getElementById('pvzSelected').style.display = 'none';
                            selectedPvz = '';
                        }

                        function showPvzDropdown() {
                            filterPvz(document.getElementById('pvzSearch').value);
                        }

                        function hidePvzDropdown() {
                            setTimeout(function(){ document.getElementById('pvzDropdown').style.display = 'none'; }, 200);
                        }
                        </script>
                        <?php if (empty($pvzJs)): ?>
                        <p style="color:#9ca3af;font-size:12px;margin-top:4px">Пункты выдачи не загружены — настройте в плагине Европочта</p>
                        <?php endif; ?>
                    </div>

                    <!-- Самовывоз: адрес из настроек (read-only) -->
                    <?php
                    $pickupAddress  = $pickupAddress  ?? 'пр.Победителей 5 офис 9';
                    $pickupWorkTime = $pickupWorkTime ?? 'Пн-Вс: 10:00-22:00';
                    ?>
                    <div id="pickupInfo" style="display:none;margin-top:10px;padding:10px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;font-size:13px;color:#166534">
                        <i class="bi bi-shop" style="margin-right:6px"></i>
                        <strong>Адрес самовывоза:</strong> <?= Html::encode($pickupAddress) ?>
                        <span style="margin-left:8px;color:#6b7280" title="Время работы: <?= Html::encode($pickupWorkTime) ?>">
                            <i class="bi bi-clock"></i> <?= Html::encode($pickupWorkTime) ?>
                        </span>
                        <input type="hidden" id="pickupAddressHidden" value="<?= Html::encode($pickupAddress) ?>">
                    </div>

                    <!-- Адрес: город + индекс на одной строке, затем улица -->
                    <div id="addressGroup" style="display:none">
                        <div class="address-row">
                            <div class="form-group">
                                <label for="field-city">Город <span class="text-danger">*</span></label>
                                <input type="text" id="field-city" name="city" class="form-control"
                                       placeholder="Минск" maxlength="100">
                            </div>
                            <div class="form-group">
                                <label for="field-postal">Индекс</label>
                                <input type="text" id="field-postal" name="postal_code" class="form-control"
                                       placeholder="220000" maxlength="20">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="field-address" id="addressLabel">Улица, дом, квартира <span class="text-danger">*</span></label>
                            <textarea id="field-address" name="address" class="form-control"
                                      rows="2" placeholder="Улица, дом, квартира"
                                      maxlength="500"><?= Html::encode($customer?->default_address ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Оплата — динамически из DB (с дефолтами из контроллера) -->
                <?php
                $firstPaymentId = !empty($paymentMethods) ? ($paymentMethods[0]['id'] ?? '') : '';
                ?>
                <div class="checkout-section">
                    <h2><i class="bi bi-credit-card"></i> Оплата</h2>
                    <div class="choice-card-list payment-options">
                        <?php foreach ($paymentMethods as $i => $pm): ?>
                        <label class="choice-card payment-option">
                            <input type="radio" name="payment"
                                   value="<?= Html::encode($pm['id']) ?>"
                                   <?= $i === 0 ? 'checked' : '' ?>
                                   onchange="selectPayment(<?= json_encode($pm['id']) ?>)">
                            <div class="choice-card-body">
                                <div class="choice-card-icon"><i class="bi bi-<?= Html::encode($pm['icon'] ?? 'credit-card') ?>"></i></div>
                                <div class="choice-card-text">
                                    <span class="choice-card-title"><?= Html::encode($pm['name']) ?></span>
                                    <?php if (!empty($pm['description'])): ?>
                                    <span class="choice-card-desc"><?= Html::encode($pm['description']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="choice-card-radio"></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Комментарий -->
                <div class="checkout-section">
                    <h2><i class="bi bi-chat-left-text"></i> Комментарий</h2>
                    <div class="form-group">
                        <textarea id="field-comment" name="comment" class="form-control"
                                  rows="2" placeholder="Дополнительные пожелания к заказу"
                                  maxlength="1000"></textarea>
                    </div>
                </div>
            </div>

            <!-- Правая колонка: Итого (sticky) — только на desktop/tablet -->
            <div class="checkout-right">
                <div class="order-summary">
                    <h3>Ваш заказ</h3>

                    <div class="summary-items">
                        <?php foreach ($items as $item): ?>
                            <?php if (!$item->product) continue; ?>
                            <div class="summary-item">
                                <img src="<?= Html::encode($item->product->getMainImageUrl()) ?>"
                                     alt="<?= Html::encode($item->product->name) ?>"
                                     class="summary-item-img">
                                <div class="summary-item-info">
                                    <span class="summary-item-title"><?= Html::encode(mb_strimwidth($item->product->name, 0, 40, '…')) ?></span>
                                    <?php if ($item->size): ?>
                                        <span class="summary-item-meta">Размер: <?= Html::encode($item->size) ?></span>
                                    <?php endif; ?>
                                    <span class="summary-item-meta"><?= (int)$item->quantity ?> × <?= number_format($item->price, 2) ?> BYN</span>
                                </div>
                                <span class="summary-item-price"><?= number_format($item->price * $item->quantity, 2) ?> BYN</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Товары (<?= count($items) ?>):</span>
                            <span id="productsTotal"><?= number_format($total, 2) ?> BYN</span>
                        </div>
                        <div class="summary-row" id="deliveryCostRow">
                            <span>Доставка:</span>
                            <span id="deliveryCost" class="delivery-cost">Бесплатно</span>
                        </div>

                        <div class="coupon-section" id="couponSection">
                            <div class="coupon-input-row">
                                <input type="text" id="couponCode" class="coupon-input" placeholder="Промокод" maxlength="20">
                                <button type="button" class="coupon-btn" onclick="applyCoupon()">Применить</button>
                            </div>
                            <div class="coupon-message" id="couponMessage"></div>
                        </div>
                        <div class="summary-row coupon-discount-row" id="couponDiscountRow" style="display:none">
                            <span>Скидка:</span>
                            <span id="couponDiscount">0 BYN</span>
                        </div>

                        <div class="summary-total">
                            <span>Итого:</span>
                            <span id="finalTotal"><?= number_format($total, 2) ?> BYN</span>
                        </div>
                    </div>

                    <?php if ($total < $freeDeliveryThreshold): ?>
                        <div class="delivery-info">
                            <i class="bi bi-truck"></i>
                            До бесплатной доставки: <?= number_format($freeDeliveryThreshold - $total, 2) ?> BYN
                        </div>
                    <?php endif; ?>

                    <button class="btn-place-order" onclick="submitOrder()">
                        <i class="bi bi-check-circle-fill"></i>
                        Оформить заказ — <span id="orderBtnTotal"><?= number_format($total, 0) ?></span> BYN
                    </button>

                    <a href="<?= Url::to(['/catalog']) ?>" class="btn-continue">Продолжить покупки</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile: sticky bottom CTA -->
<div class="mobile-cta-bar">
    <button class="btn-place-order" onclick="submitOrder()">
        <i class="bi bi-check-circle-fill"></i>
        Оформить заказ — <span id="mobileBtnTotal"><?= number_format($total, 0) ?></span> BYN
    </button>
</div>

<!-- Модал успеха -->
<div class="checkout-success-modal" id="successModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="success-icon"><i class="bi bi-check-lg"></i></div>
        <h2>Заказ оформлен!</h2>
        <p>Ваш заказ <strong>#<span id="orderNumber">—</span></strong> успешно создан</p>
        <p class="text-muted">Мы свяжемся с вами для подтверждения</p>
        <div class="success-actions">
            <a href="<?= Url::to(['/account/orders']) ?>" class="btn-success-primary">
                <i class="bi bi-box-seam"></i> Мои заказы
            </a>
            <a href="<?= Url::to(['/catalog']) ?>" class="btn-success-secondary">
                <i class="bi bi-grid"></i> Продолжить покупки
            </a>
        </div>
    </div>
</div>

<style>
.field-error { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,.1) !important; }
.field-valid { border-color: #22c55e !important; }
.field-error-msg { color: #ef4444; font-size: 12px; margin-top: 4px; animation: fadeIn .2s; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
var orderTotal        = <?= (float)$total ?>;
var orderDeliveryCost = 0;
var orderDiscount     = 0;
var selectedDelivery  = <?= json_encode($firstShippingId) ?>;
var selectedCountry   = <?= json_encode($deliveryCountryMap[$firstShippingId] ?? 'belarus') ?>;
var selectedPayment   = <?= json_encode($firstPaymentId) ?>;
var selectedPvz       = '';
var csrfToken         = <?= json_encode($csrfToken) ?>;
var createUrl         = <?= json_encode($createUrl) ?>;
// id → country mapping (auto-syncs hidden country field on delivery change)
var deliveryCountryMap = <?= json_encode($deliveryCountryMap, JSON_UNESCAPED_UNICODE) ?>;

// ===== Phone mask +375 =====
(function() {
    var phoneInput = document.getElementById('field-phone');
    if (!phoneInput) return;

    phoneInput.addEventListener('focus', function() {
        if (!this.value) this.value = '+375 (';
    });

    phoneInput.addEventListener('input', function(e) {
        var digits = this.value.replace(/\D/g, '');
        // Ensure starts with 375
        if (digits.length < 3 || digits.substring(0, 3) !== '375') {
            digits = '375' + digits.replace(/^375/, '');
        }
        // Format: +375 (XX) XXX-XX-XX
        var formatted = '+375';
        if (digits.length > 3) formatted += ' (' + digits.substring(3, 5);
        if (digits.length >= 5) formatted += ') ';
        if (digits.length > 5) formatted += digits.substring(5, 8);
        if (digits.length > 8) formatted += '-' + digits.substring(8, 10);
        if (digits.length > 10) formatted += '-' + digits.substring(10, 12);
        this.value = formatted;
    });

    phoneInput.addEventListener('keydown', function(e) {
        // Allow backspace, delete, tab, arrows
        if ([8, 46, 9, 37, 39].indexOf(e.keyCode) !== -1) return;
        // Block non-digit
        if (e.key && e.key.length === 1 && !/\d/.test(e.key)) e.preventDefault();
    });
})();

// ===== Inline validation =====
(function() {
    function showError(el, msg) {
        clearError(el);
        el.classList.add('field-error');
        var err = document.createElement('div');
        err.className = 'field-error-msg';
        err.textContent = msg;
        err.style.cssText = 'color:#ef4444;font-size:12px;margin-top:4px;';
        el.parentNode.appendChild(err);
    }
    function clearError(el) {
        el.classList.remove('field-error');
        var prev = el.parentNode.querySelector('.field-error-msg');
        if (prev) prev.remove();
    }
    function validateField(el) {
        var val = el.value.trim();
        var name = el.getAttribute('name');
        clearError(el);
        if (name === 'name') {
            if (!val) { showError(el, 'Укажите ФИО'); return false; }
            if (val.length < 3) { showError(el, 'ФИО слишком короткое'); return false; }
        }
        if (name === 'phone') {
            var digits = val.replace(/\D/g, '');
            if (digits.length < 12) { showError(el, 'Введите полный номер телефона'); return false; }
        }
        if (name === 'email' && val) {
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) { showError(el, 'Некорректный email'); return false; }
        }
        el.classList.add('field-valid');
        return true;
    }

    ['field-name', 'field-phone', 'field-email'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('blur', function() { validateField(this); });
            el.addEventListener('input', function() {
                if (this.classList.contains('field-error')) validateField(this);
            });
        }
    });

    // Expose for use in submitOrder
    window.validateCheckoutFields = function() {
        var valid = true;
        ['field-name', 'field-phone'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el && !validateField(el)) { valid = false; if (!document.querySelector('.field-error:focus')) el.focus(); }
        });
        var emailEl = document.getElementById('field-email');
        if (emailEl && emailEl.value.trim() && !validateField(emailEl)) valid = false;
        return valid;
    };
})();

function selectPvz(val) {
    selectedPvz = val;
}

// Init delivery state on page load — read price from data-price set by controller defaults / DB
document.addEventListener('DOMContentLoaded', function() {
    var checked = document.querySelector('input[name="delivery"]:checked');
    if (checked) {
        var cost = parseFloat(checked.getAttribute('data-price') || '0') || 0;
        updateDelivery(cost, checked.value);
    }
});

// Mobile summary toggle
function toggleMobileSummary() {
    document.getElementById('mobileSummaryToggle').classList.toggle('expanded');
}

// Выбор метода оплаты
function selectPayment(method) {
    selectedPayment = method;
}

// Обновление стоимости доставки. Source of truth: data-* attributes on the
// checked radio (set server-side by OrderController defaults / DB settings).
function updateDelivery(cost, method) {
    orderDeliveryCost = cost;
    selectedDelivery  = method;

    var checked = document.querySelector('input[name="delivery"]:checked');

    // Sync country hidden field from selected method
    var country = (checked && checked.getAttribute('data-country')) || deliveryCountryMap[method] || 'belarus';
    selectedCountry = country;
    var countryHidden = document.getElementById('selectedCountry');
    if (countryHidden) countryHidden.value = country;

    // Prefer server-rendered chip text; fall back to derived label
    var label = checked && checked.getAttribute('data-price-label');
    if (!label) {
        if (method === 'pickup' || method === 'pickup_minsk') label = 'Бесплатно';
        else if (method === 'sdek' || method === 'cdek')      label = 'По тарифам СДЭК';
        else                                                  label = cost.toFixed(2) + ' BYN';
    }

    ['deliveryCost', 'mobileDeliveryCost'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = label;
    });

    // Address-region label cue when shipping to Russia
    var addressLabel = document.getElementById('addressLabel');
    if (addressLabel) {
        addressLabel.innerHTML = (country === 'russia')
            ? 'Адрес пункта выдачи СДЭК <span class="text-danger">*</span>'
            : 'Улица, дом, квартира <span class="text-danger">*</span>';
    }

    // Show Europochta ПВЗ dropdown
    var pvzWrap = document.getElementById('europochtaPvz');
    if (pvzWrap) pvzWrap.style.display = (method === 'europochta') ? 'block' : 'none';

    var isPickup = (method === 'pickup' || method === 'pickup_minsk' || method === 'local_pickup');
    var pickupInfoEl = document.getElementById('pickupInfo');
    if (pickupInfoEl) pickupInfoEl.style.display = isPickup ? 'block' : 'none';

    var needAddress = (!isPickup && method !== 'europochta');
    var addressGroup = document.getElementById('addressGroup');
    if (addressGroup) addressGroup.style.display = needAddress ? 'block' : 'none';
    var addressInput = document.getElementById('field-address');
    if (addressInput) addressInput.required = needAddress;

    updateTotal();
}

function updateTotal() {
    var total = orderTotal + orderDeliveryCost - orderDiscount;
    var fmt = total.toFixed(2) + ' BYN';
    ['finalTotal', 'mobileFinalTotal', 'mobileFinalTotal2', 'stickyTotal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = fmt;
    });
    var fmtShort = Math.round(total);
    ['orderBtnTotal', 'mobileBtnTotal'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.textContent = fmtShort;
    });
}

// Промокод
function applyCoupon() {
    var code = document.getElementById('couponCode').value.trim().toUpperCase();
    if (!code) { showCouponMessage('Введите промокод', 'error'); return; }

    var testCoupons = {
        'SALE10':  { type: 'percent',  value: 10, name: 'Скидка 10%' },
        'SALE20':  { type: 'percent',  value: 20, name: 'Скидка 20%' },
        'WELCOME': { type: 'fixed',    value: 50, name: 'Скидка 50 BYN' },
        'FREESHIP':{ type: 'shipping', value: 0,  name: 'Бесплатная доставка' }
    };
    var coupon = testCoupons[code];
    if (!coupon) { showCouponMessage('Промокод не найден', 'error'); return; }

    if (coupon.type === 'percent')       orderDiscount = orderTotal * (coupon.value / 100);
    else if (coupon.type === 'fixed')    orderDiscount = Math.min(coupon.value, orderTotal);
    else if (coupon.type === 'shipping') orderDiscount = orderDeliveryCost;

    document.getElementById('couponDiscount').textContent = orderDiscount.toFixed(2) + ' BYN';
    document.getElementById('couponDiscountRow').style.display = 'flex';
    updateTotal();
    showCouponMessage(coupon.name + ' применена!', 'success');
}

function showCouponMessage(text, type) {
    var el = document.getElementById('couponMessage');
    el.textContent = text;
    el.className = 'coupon-message ' + (type === 'success' ? 'coupon-success' : 'coupon-error');
    setTimeout(function() { el.textContent = ''; el.className = 'coupon-message'; }, 5000);
}

// Отправка заказа
function submitOrder() {
    var name   = document.getElementById('field-name').value.trim();
    var phone  = document.getElementById('field-phone').value.trim();
    var email  = document.getElementById('field-email').value.trim();
    var comment = document.getElementById('field-comment').value.trim();
    var city   = document.getElementById('field-city') ? document.getElementById('field-city').value.trim() : '';
    var postal = document.getElementById('field-postal') ? document.getElementById('field-postal').value.trim() : '';
    var street = document.getElementById('field-address').value.trim();
    var delivery = document.querySelector('input[name="delivery"]:checked');

    // Combine address parts
    var address = [city, postal, street].filter(Boolean).join(', ');

    // Inline validation
    if (typeof window.validateCheckoutFields === 'function' && !window.validateCheckoutFields()) return;
    if (!name)  { alert('Укажите ФИО'); document.getElementById('field-name').focus(); return; }
    if (!phone) { alert('Укажите телефон'); document.getElementById('field-phone').focus(); return; }
    if (!delivery) { alert('Выберите способ доставки'); return; }

    // For Europochta: use selected ПВЗ as pickup_point and address
    if (delivery.value === 'europochta') {
        var pvzVal = document.getElementById('pvzHidden') ? document.getElementById('pvzHidden').value : selectedPvz;
        if (!pvzVal) {
            alert('Выберите пункт выдачи Европочты');
            var pvzSearchEl = document.getElementById('pvzSearch');
            if (pvzSearchEl) pvzSearchEl.focus();
            return;
        }
        address = pvzVal;
        selectedPvz = pvzVal;
    }

    // For pickup: use stored pickup address
    var isPickupMethod = (delivery.value === 'pickup' || delivery.value === 'pickup_minsk' || delivery.value === 'local_pickup');
    if (isPickupMethod) {
        var pickupHidden = document.getElementById('pickupAddressHidden');
        address = pickupHidden ? pickupHidden.value : 'Самовывоз';
    }

    if (!isPickupMethod && delivery.value !== 'europochta' && !address) {
        alert('Укажите адрес доставки');
        var cityEl = document.getElementById('field-city');
        if (cityEl) cityEl.focus(); else document.getElementById('field-address').focus();
        return;
    }

    document.querySelectorAll('.btn-place-order').forEach(function(btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Оформляем...';
    });

    var params = new URLSearchParams();
    params.append('name',     name);
    params.append('phone',    phone);
    params.append('email',    email);
    params.append('comment',  comment);
    params.append('delivery', delivery.value);
    params.append('address',  address);
    params.append('country',  selectedCountry);
    params.append('payment',  selectedPayment);
    // Send pickup_point separately for Europochta
    if (delivery.value === 'europochta' && selectedPvz) {
        params.append('pickup_point', selectedPvz);
    }

    fetch(createUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: params.toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('orderNumber').textContent = data.order_number || data.order_id;
            document.getElementById('successModal').classList.add('active');
            document.body.style.overflow = 'hidden';

            // GA4 purchase event
            if (typeof gtag === 'function') {
                gtag('event', 'purchase', {
                    transaction_id: data.order_number || data.order_id,
                    value: orderTotal + orderDeliveryCost - orderDiscount,
                    currency: 'BYN',
                    shipping: orderDeliveryCost,
                    items: <?= json_encode(array_map(function($item) {
                        return [
                            'item_id' => $item->product ? $item->product->id : '',
                            'item_name' => $item->product ? $item->product->name : '',
                            'price' => (float)$item->price,
                            'quantity' => (int)$item->quantity,
                        ];
                    }, $items), JSON_UNESCAPED_UNICODE) ?>
                });
            }
        } else {
            document.querySelectorAll('.btn-place-order').forEach(function(btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Оформить заказ';
            });
            alert(data.message || 'Ошибка оформления заказа');
        }
    })
    .catch(function() {
        document.querySelectorAll('.btn-place-order').forEach(function(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Оформить заказ';
        });
        alert('Ошибка соединения. Попробуйте позже.');
    });
}

</script>
