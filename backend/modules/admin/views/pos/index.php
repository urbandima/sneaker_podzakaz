<?php
use yii\helpers\Html;
use yii\helpers\Url;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;

$this->title = 'POS-Терминал';
$categories = Category::find()->where(['is_active' => true])->orderBy(['name' => SORT_ASC])->all();
$brands = Brand::find()->where(['is_active' => true])->orderBy(['name' => SORT_ASC])->all();
$sizes = ['35','36','37','38','39','40','41','42','43','44','45','46'];
?>

<div id="pos-config" style="display:none"
    data-get-products-url="<?= Url::to(['/admin/pos/get-products']) ?>"
    data-get-customers-url="<?= Url::to(['/admin/pos/get-customers']) ?>"
    data-create-customer-url="<?= Url::to(['/admin/pos/create-customer']) ?>"
    data-get-orders-url="<?= Url::to(['/admin/pos/get-customer-orders']) ?>"
    data-complete-order-url="<?= Url::to(['/admin/pos/complete-order']) ?>"
    data-apply-discount-url="<?= Url::to(['/admin/pos/apply-discount']) ?>"
    data-quick-order-url="<?= Url::to(['/admin/pos/quick-order']) ?>"></div>

<div class="admin-header">
<h1 class="admin-header-title"><i class="bi bi-shop"></i> POS-Терминал</h1>
<div class="admin-header-actions">
<button class="admin-btn admin-btn-secondary" onclick="openScan()"><i class="bi bi-upc-scan"></i> Сканер</button>
<button class="admin-btn admin-btn-secondary" onclick="openOrders()"><i class="bi bi-box-seam"></i> Выдача</button>
</div>
</div>

<div class="pos-grid">
<div class="pos-left">
<div class="pos-tabs">
<button class="pos-tab active" onclick="switchTab('products')">Товары</button>
<button class="pos-tab" onclick="switchTab('categories')">Категории</button>
</div>
<div class="pos-filters">
<select class="pos-filter-select" id="cat-filter" onchange="filter()">
<option value="">Все категории</option>
<?php foreach($categories as $cat): ?>
<option value="<?=$cat->id?>"><?=Html::encode($cat->name)?></option>
<?php endforeach; ?>
</select>
<select class="pos-filter-select" id="brand-filter" onchange="filter()">
<option value="">Все бренды</option>
<?php foreach($brands as $brand): ?>
<option value="<?=$brand->id?>"><?=Html::encode($brand->name)?></option>
<?php endforeach; ?>
</select>
<input type="text" class="pos-search" id="search" placeholder="Поиск товара..." oninput="filter()">
</div>
<div class="pos-size-filters">
<button class="pos-size-btn" onclick="toggleSize('all')">Все</button>
<?php foreach($sizes as $size): ?>
<button class="pos-size-btn" onclick="toggleSize('<?=$size?>')"><?=$size?></button>
<?php endforeach; ?>
</div>
<div class="pos-products" id="products"></div>
</div>

<div class="pos-right">
<div class="pos-customer">
<div class="pos-customer-info" id="cust-info">
<div class="pos-customer-name">Гость</div>
<div class="pos-customer-phone"></div>
</div>
<button class="pos-customer-btn" onclick="openCustomer()"><i class="bi bi-search"></i> Выбрать</button>
</div>

<div class="pos-cart" id="cart">
<div class="pos-cart-empty"><i class="bi bi-cart" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>Корзина пуста</div>
</div>

<div class="pos-discount">
<strong>Скидка</strong>
<div class="pos-discount-input">
<input type="number" id="discount-val" placeholder="Сумма/%" min="0" step="0.01">
<select id="discount-type">
<option value="percent">%</option>
<option value="fixed">BYN</option>
</select>
<button class="admin-btn admin-btn-primary admin-btn-sm" onclick="applyDiscount()">Применить</button>
</div>
</div>

<div class="pos-totals">
<div class="pos-total-row"><span>Товаров:</span><span id="count">0</span></div>
<div class="pos-total-row"><span>Сумма:</span><span id="subtotal">0 BYN</span></div>
<div class="pos-total-row" id="discount-row" style="display:none;color:var(--admin-success)"><span>Скидка:</span><span id="discount-amt">0 BYN</span></div>
<div class="pos-total-row grand"><span>ИТОГО:</span><span id="total">0 BYN</span></div>
</div>

<div class="pos-payment">
<div class="pos-payment-grid">
<button class="pos-payment-btn active" data-method="cash" onclick="selectPayment('cash')"><i class="bi bi-cash"></i> Наличные</button>
<button class="pos-payment-btn" data-method="card" onclick="selectPayment('card')"><i class="bi bi-credit-card"></i> Карта</button>
<button class="pos-payment-btn" data-method="split" onclick="selectPayment('split')"><i class="bi bi-wallet2"></i> Смешанная</button>
</div>
<div class="pos-payment-split" id="split-payment">
<input type="number" id="cash-amt" placeholder="Наличные" min="0" step="0.01">
<input type="number" id="card-amt" placeholder="Карта" min="0" step="0.01">
</div>
</div>

<button class="pos-checkout-btn" id="checkout-btn" onclick="checkout()" disabled><i class="bi bi-check-circle"></i> Оформить заказ</button>
</div>
</div>

<div class="pos-modal" id="customer-modal">
<div class="pos-modal-box">
<div class="pos-modal-header"><h3>Выбор клиента</h3><button class="pos-modal-close" onclick="closeCustomer()">&times;</button></div>
<div class="pos-modal-body">
<div style="display:flex;gap:0.5rem;margin-bottom:1rem">
<input type="text" class="pos-search" id="cust-search" placeholder="Поиск по имени или телефону..." oninput="searchCustomers()">
<button class="admin-btn admin-btn-primary" onclick="createCustomer()"><i class="bi bi-plus"></i> Новый</button>
</div>
<div id="cust-list"></div>
</div>
</div>
</div>

<div class="pos-modal" id="orders-modal">
<div class="pos-modal-box">
<div class="pos-modal-header"><h3>Выдача заказов</h3><button class="pos-modal-close" onclick="closeOrders()">&times;</button></div>
<div class="pos-modal-body" id="orders-list"></div>
</div>
</div>

<div class="pos-modal" id="scan-modal">
<div class="pos-modal-box" style="max-width:400px">
<div class="pos-modal-header"><h3>Сканирование</h3><button class="pos-modal-close" onclick="closeScan()">&times;</button></div>
<div class="pos-modal-body">
<div style="display:flex;gap:0.5rem">
<input type="text" class="pos-search" id="barcode" placeholder="Штрихкод..." onkeypress="if(event.key==='Enter')scan()">
<button class="admin-btn admin-btn-primary" onclick="scan()"><i class="bi bi-search"></i></button>
</div>
</div>
</div>
</div>
