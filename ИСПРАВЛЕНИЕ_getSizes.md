# ✅ Исправлена ошибка getSizes()

**Дата**: 05.11.2024, 06:31

---

## 🐛 Ошибка

```
Unknown Method – yii\base\UnknownMethodException
Calling unknown method: app\models\Product::getSizes()
в /Users/user/CascadeProjects/splitwise/vendor/yiisoft/yii2/base/Component.php:312
```

**Причина**: В модели `Product` отсутствовал метод `getSizes()`, который используется в админке.

---

## ✅ Решение

Добавлен метод в `models/Product.php`:

```php
/**
 * Все размеры товара
 */
public function getSizes()
{
    return $this->hasMany(ProductSize::class, ['product_id' => 'id'])
        ->orderBy(['sort_order' => SORT_ASC, 'size' => SORT_ASC]);
}
```

---

## 📝 Где используется

Метод `getSizes()` используется в:

1. **edit-product.php** (строки 413, 647, 648):
   ```php
   $sizes = $product->getSizes()->orderBy(['us_size' => SORT_ASC])->all();
   $sizesCount = $product->getSizes()->count();
   $availableSizes = $product->getSizes()->where(['is_available' => 1])->count();
   ```

2. **view-product.php** (строка 423):
   ```php
   $sizes = $product->getSizes()->orderBy(['us_size' => SORT_ASC])->all();
   ```

---

## 🔄 Разница методов

### `getSizes()` - ВСЕ размеры
```php
$product->sizes  // Все размеры (включая недоступные)
```

### `getAvailableSizes()` - ДОСТУПНЫЕ размеры
```php
$product->availableSizes  // Только is_available = 1
```

---

## ✅ Готово!

Теперь можно открывать карточки товаров Poizon в админке без ошибок.

**Проверьте**:
```
http://localhost:8080/admin/view-product?id=121
http://localhost:8080/admin/edit-product?id=121
```
