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

<script>
let cart=[],customer=null,payment='cash',products=[],customers=[],selectedSize=null,discount={type:'percent',value:0,amount:0};
function load(){fetch('<?=Url::to(["/admin/pos/get-products"])?>').then(r=>r.json()).then(d=>{products=d.products||[];show()})}
function show(list){const g=document.getElementById('products'),l=list||products;if(!l.length){g.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет товаров</div>';return}g.innerHTML=l.map(p=>`<div class="pos-product ${p.total_stock===0?'out':''}" onclick="${p.total_stock>0?`add(${p.id})`:''}"><img src="${p.image}"><div class="name">${p.name}</div><div class="price">${p.price} BYN</div><div class="stock">${p.total_stock>0?p.total_stock+' шт':'Нет'}</div></div>`).join('')}
function filter(){const cat=document.getElementById('cat-filter').value,brand=document.getElementById('brand-filter').value,search=document.getElementById('search').value.toLowerCase();let filtered=products;if(cat)filtered=filtered.filter(p=>p.category_id==cat);if(brand)filtered=filtered.filter(p=>p.brand_id==brand);if(search)filtered=filtered.filter(p=>p.name.toLowerCase().includes(search)||p.article.toLowerCase().includes(search));if(selectedSize&&selectedSize!=='all')filtered=filtered.filter(p=>p.sizes&&p.sizes.some(s=>s.size==selectedSize&&s.stock>0));show(filtered)}
function toggleSize(size){selectedSize=size==='all'?null:size;document.querySelectorAll('.pos-size-btn').forEach(b=>b.classList.toggle('active',b.textContent===size||(!selectedSize&&b.textContent==='Все')));filter()}
function switchTab(tab){document.querySelectorAll('.pos-tab').forEach(t=>t.classList.toggle('active',t.textContent.toLowerCase().includes(tab)))}
function add(id){const p=products.find(x=>x.id===id);if(!p||!p.sizes)return;const avail=p.sizes.filter(s=>s.stock>0);if(!avail.length){alert('Нет в наличии');return}let s;if(avail.length===1)s=avail[0];else{const size=prompt('Размеры: '+avail.map(z=>z.size+'('+z.stock+')').join(', ')+'\nВведите размер:');if(!size)return;s=avail.find(x=>x.size==size);if(!s){alert('Размер не найден');return}}const e=cart.find(i=>i.product_id===p.id&&i.size_id===s.id);if(e){if(e.quantity<s.stock)e.quantity++;else{alert('Лимит');return}}else cart.push({product_id:p.id,size_id:s.id,name:p.name,size:s.size,price:s.price||p.price,image:p.image,quantity:1,max_stock:s.stock});upd()}
function upd(){const cont=document.getElementById('cart'),countEl=document.getElementById('count'),subEl=document.getElementById('subtotal'),totEl=document.getElementById('total'),btn=document.getElementById('checkout-btn');if(!cart.length){cont.innerHTML='<div class="pos-cart-empty"><i class="bi bi-cart" style="font-size:2rem;display:block;margin-bottom:0.5rem"></i>Корзина пуста</div>';countEl.textContent='0';subEl.textContent='0 BYN';totEl.textContent='0 BYN';btn.disabled=true;discount={type:'percent',value:0,amount:0};document.getElementById('discount-row').style.display='none';return}let sub=0,c=0;cont.innerHTML=cart.map((it,i)=>{const itt=it.price*it.quantity;sub+=itt;c+=it.quantity;return`<div class="pos-cart-item"><img src="${it.image}"><div class="pos-cart-item-info"><div class="pos-cart-item-name">${it.name}</div><div class="pos-cart-item-meta">${it.size}</div></div><div class="pos-cart-item-qty"><button class="pos-qty-btn" onclick="chg(${i},-1)">-</button><span>${it.quantity}</span><button class="pos-qty-btn" onclick="chg(${i},1)" ${it.quantity>=it.max_stock?'disabled':''}>+</button></div><div class="pos-cart-item-total">${Math.round(itt)}</div><button class="pos-qty-btn" onclick="rem(${i})" style="color:var(--admin-danger)"><i class="bi bi-trash"></i></button></div>`}).join('');const tot=sub-discount.amount;countEl.textContent=c;subEl.textContent=sub.toFixed(2)+' BYN';totEl.textContent=tot.toFixed(2)+' BYN';if(discount.amount>0){document.getElementById('discount-row').style.display='flex';document.getElementById('discount-amt').textContent='-'+discount.amount.toFixed(2)+' BYN'}else document.getElementById('discount-row').style.display='none';btn.disabled=false}
function chg(i,d){const it=cart[i],n=it.quantity+d;if(n<=0)rem(i);else if(n<=it.max_stock){it.quantity=n;upd()}}
function rem(i){cart.splice(i,1);upd()}
function applyDiscount(){const val=parseFloat(document.getElementById('discount-val').value)||0,type=document.getElementById('discount-type').value,sub=cart.reduce((a,i)=>a+i.price*i.quantity,0);if(val<=0)return;fetch('<?=Url::to(["/admin/pos/apply-discount"])?>',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({type,value:val,subtotal:sub})}).then(r=>r.json()).then(d=>{if(d.success){discount={type,value:val,amount:d.discount_amount};upd()}else alert(d.message)})}
function selectPayment(m){payment=m;document.querySelectorAll('.pos-payment-btn').forEach(b=>b.classList.toggle('active',b.dataset.method===m));document.getElementById('split-payment').classList.toggle('active',m==='split')}
function openCustomer(){fetch('<?=Url::to(["/admin/pos/get-customers"])?>').then(r=>r.json()).then(d=>{customers=d.customers||[];renderCustomers();document.getElementById('customer-modal').classList.add('active')})}
function closeCustomer(){document.getElementById('customer-modal').classList.remove('active')}
function renderCustomers(list){const l=list||customers,cont=document.getElementById('cust-list');if(!l.length){cont.innerHTML='<div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет клиентов</div>';return}cont.innerHTML=l.map(c=>`<div class="pos-customer-row" onclick="selectCustomer(${c.id})"><div><div style="font-weight:600">${c.name}</div><div style="font-size:0.8rem;color:var(--admin-text-secondary)">${c.phone||''}</div></div><button class="admin-btn admin-btn-primary admin-btn-sm">Выбрать</button></div>`).join('')}
function searchCustomers(){const q=document.getElementById('cust-search').value.toLowerCase();renderCustomers(customers.filter(c=>c.name.toLowerCase().includes(q)||(c.phone&&c.phone.includes(q))))}
function selectCustomer(id){customer=customers.find(c=>c.id===id);if(customer){document.getElementById('cust-info').innerHTML=`<div class="pos-customer-name">${customer.name}</div><div class="pos-customer-phone">${customer.phone||''}</div>`}closeCustomer()}
function createCustomer(){const name=prompt('Имя клиента:');if(!name)return;const phone=prompt('Телефон:');fetch('<?=Url::to(["/admin/pos/create-customer"])?>',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({name,phone})}).then(r=>r.json()).then(d=>{if(d.success){customer=d.customer;selectCustomer(customer.id);customers.push(customer)}else alert(d.message)})}
function openOrders(){if(!customer){alert('Выберите клиента');return}fetch('<?=Url::to(["/admin/pos/get-customer-orders"])?>?customer_id='+customer.id).then(r=>r.json()).then(d=>{if(d.success){renderOrders(d.orders);document.getElementById('orders-modal').classList.add('active')}else alert(d.message||'Ошибка загрузки заказов')})}
function closeOrders(){document.getElementById('orders-modal').classList.remove('active')}
function renderOrders(orders){const cont=document.getElementById('orders-list');if(!orders.length){cont.innerHTML='<div style="text-align:center;padding:2rem;color:var(--admin-text-secondary)">Нет готовых заказов</div>';return}cont.innerHTML=orders.map(o=>`<div class="pos-order-row"><div class="pos-order-header"><div class="pos-order-number">#${o.order_number}</div><span class="pos-order-status ${o.status}">${o.status}</span></div><div class="pos-order-items">${o.items_count} товаров</div><div class="pos-order-total">${o.total_amount} BYN</div><div class="pos-actions"><button class="admin-btn admin-btn-success admin-btn-sm" onclick="completeOrder(${o.id})"><i class="bi bi-check-circle"></i> Выдать</button></div></div>`).join('')}
function completeOrder(id){if(!confirm('Выдать заказ клиенту?'))return;fetch('<?=Url::to(["/admin/pos/complete-order"])?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:'order_id='+id}).then(r=>r.json()).then(d=>{if(d.success){alert('Заказ #'+d.order_number+' выдан');closeOrders()}else alert(d.message)})}
function openScan(){document.getElementById('scan-modal').classList.add('active');setTimeout(()=>document.getElementById('barcode').focus(),100)}
function closeScan(){document.getElementById('scan-modal').classList.remove('active')}
function scan(){const code=document.getElementById('barcode').value.trim();if(!code)return;const p=products.find(x=>x.article===code||x.sku===code);if(p){add(p.id);closeScan();document.getElementById('barcode').value=''}else alert('Товар не найден')}
function checkout(){if(!cart.length)return;let paymentData={method:payment};if(payment==='split'){const cash=parseFloat(document.getElementById('cash-amt').value)||0,card=parseFloat(document.getElementById('card-amt').value)||0,total=cart.reduce((a,i)=>a+i.price*i.quantity,0)-discount.amount;if(cash+card<total){alert('Сумма оплаты меньше итога');return}paymentData={method:'split',cash_amount:cash,card_amount:card}}const data={items:cart,customer_id:customer?customer.id:null,customer_name:customer?customer.name:'Гость',customer_phone:customer?customer.phone:'',payment_method:payment,payment_data:paymentData,discount:discount.amount>0?discount:null};fetch('<?=Url::to(["/admin/pos/quick-order"])?>',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(data)}).then(r=>r.json()).then(d=>{if(d.success){alert('Заказ #'+d.order_number+' создан!\nСумма: '+d.total+' BYN');cart=[];customer=null;discount={type:'percent',value:0,amount:0};document.getElementById('cust-info').innerHTML='<div class="pos-customer-name">Гость</div><div class="pos-customer-phone"></div>';document.getElementById('discount-val').value='';upd()}else alert(d.message)})}
load();
document.addEventListener('keydown',e=>{if(e.ctrlKey&&e.key==='f'){e.preventDefault();document.getElementById('search').focus()}if(e.key==='Escape')document.querySelectorAll('.pos-modal').forEach(m=>m.classList.remove('active'))});
</script>
