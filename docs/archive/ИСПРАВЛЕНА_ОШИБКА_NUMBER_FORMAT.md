# ✅ Исправлена ошибка number_format()

**Дата**: 05.11.2024, 05:49  
**Проблема**: PHP Warning при просмотре товаров Poizon  
**Статус**: Полностью исправлено

---

## ❌ Ошибка

```
PHP Deprecated Warning – yii\base\ErrorException
number_format(): Passing null to parameter #1 ($num) of type float is deprecated

in /Users/user/CascadeProjects/splitwise/views/admin/view-product.php at line 129
```

---

## 🔍 Причина

Функция `number_format()` вызывалась с `null` значениями для полей:
- `poizon_price_cny` - может быть null
- `price` - может быть null
- `purchase_price` - может быть null

В PHP 8.0+ передача `null` в `number_format()` вызывает deprecated warning.

---

## ✅ Исправление

### 1. **view-product.php** (3 места)

**Было**:
```php
<td><strong>¥<?= number_format($product->poizon_price_cny, 2) ?></strong></td>
```

**Стало**:
```php
<td><strong><?= $product->poizon_price_cny ? '¥' . number_format($product->poizon_price_cny, 2) : '-' ?></strong></td>
```

---

**Было**:
```php
'value' => '<strong>' . number_format($product->price, 2) . ' BYN</strong>',
```

**Стало**:
```php
'value' => '<strong>' . ($product->price ? number_format($product->price, 2) : '0.00') . ' BYN</strong>',
```

---

**Было**:
```php
'value' => number_format($product->purchase_price, 2) . ' BYN',
```

**Стало**:
```php
'value' => ($product->purchase_price ? number_format($product->purchase_price, 2) : '0.00') . ' BYN',
```

---

### 2. **products.php** (2 места)

**Было**:
```php
$html .= '<strong class="text-success">' . number_format($model->price, 2) . ' BYN</strong>';
```

**Стало**:
```php
$html .= '<strong class="text-success">' . ($model->price ? number_format($model->price, 2) : '0.00') . ' BYN</strong>';
```

---

**Было**:
```php
$html .= '<br><small class="text-muted">💰 ' . number_format($model->purchase_price, 2) . ' BYN</small>';
```

**Стало**:
```php
$html .= '<br><small class="text-muted">💰 ' . ($model->purchase_price ? number_format($model->purchase_price, 2) : '0.00') . ' BYN</small>';
```

---

### 3. **edit-product.php** (1 место)

**Было**:
```php
<strong class="text-danger"><?= number_format($size->price, 2) ?> BYN</strong>
```

**Стало**:
```php
<strong class="text-danger"><?= $size->price ? number_format($size->price, 2) : '0.00' ?> BYN</strong>
```

---

## 📋 Исправленные файлы

1. ✅ `views/admin/view-product.php` - 3 места
2. ✅ `views/admin/products.php` - 2 места  
3. ✅ `views/admin/edit-product.php` - 1 место

**Итого**: 6 исправлений

---

## 🎯 Результат

**Теперь**:
- ✅ Нет warnings при просмотре товаров
- ✅ Корректное отображение цен (0.00 вместо пустого места)
- ✅ Безопасная работа с null значениями
- ✅ Совместимость с PHP 8.0+

---

## 🧪 Проверка

1. **Откройте любой товар Poizon**:
   ```
   http://localhost:8080/admin/view-product?id=ID
   ```

2. **Проверьте**:
   - Нет PHP warnings
   - Цены отображаются корректно
   - Если цена null, показывается "-" или "0.00"

---

## 💡 Для будущего

### Всегда проверяйте null перед number_format()

**Правильно**:
```php
<?= $value ? number_format($value, 2) : '0.00' ?>
```

**Или**:
```php
<?= $value ? '¥' . number_format($value, 2) : '-' ?>
```

**Неправильно**:
```php
<?= number_format($value, 2) ?> <!-- Warning если $value = null -->
```

---

## ✅ Готово!

Ошибка полностью исправлена во всех файлах админки.

**Теперь можно безопасно просматривать товары Poizon без warnings!** 🚀
