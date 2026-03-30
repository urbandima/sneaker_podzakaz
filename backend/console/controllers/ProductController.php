<?php
/**
 * Контроллер для создания тестовых товаров
 */

namespace app\backend\console\controllers;

use Yii;
use yii\console\Controller;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\ProductImage;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\ProductCharacteristicValue;

class ProductController extends Controller
{
    /**
     * Создать тестовые товары
     * @param int $count Количество товаров
     */
    public function actionCreateTestProducts($count = 10)
    {
        $this->stdout("Создание {$count} тестовых товаров...\n");

        $brands = ['Nike', 'Adidas', 'Jordan', 'New Balance', 'Puma', 'Reebok'];
        $models = [
            'Air Force 1', 'Dunk Low', 'Air Max 90', 'Jordan 1', 'Jordan 4',
            'Ultra Boost', 'Samba', 'Gazelle', 'Campus', '550', '574'
        ];
        $colors = ['Белый', 'Черный', 'Серый', 'Красный', 'Синий', 'Зеленый', 'Бежевый'];
        $materials = ['Кожа', 'Замша', 'Сетка', 'Канвас', 'Нубук'];
        $seasons = ['Лето', 'Зима', 'Демисезон', 'Всесезон'];
        $genders = ['Мужской', 'Женский', 'Унисекс'];

        // Получаем или создаем категорию
        $category = Category::find()->one();
        if (!$category) {
            $category = new Category([
                'name' => 'Кроссовки',
                'slug' => 'krossovki',
                'description' => 'Категория кроссовок'
            ]);
            $category->save();
        }

        for ($i = 1; $i <= $count; $i++) {
            $brandName = $brands[array_rand($brands)];
            $modelName = $models[array_rand($models)];
            $color = $colors[array_rand($colors)];
            
            $product = new Product([
                'name' => "{$brandName} {$modelName} {$color} - Тест {$i}",
                'slug' => "test-{$brandName}-" . strtolower($modelName) . "-{$i}-" . time(),
                'description' => $this->generateDescription($brandName, $modelName, $color),
                'price' => rand(150, 800),
                'old_price' => rand(0, 3) === 0 ? rand(200, 900) : null,
                'category_id' => $category->id,
                'brand_id' => $this->getOrCreateBrand($brandName),
                'model_name' => $modelName,
                'is_active' => 1,
                'is_featured' => rand(0, 3) === 0 ? 1 : 0,
                'stock_status' => ['in_stock', 'out_of_stock', 'preorder'][array_rand(['in_stock', 'out_of_stock', 'preorder'])],
                'material' => $materials[array_rand($materials)],
                'season' => $seasons[array_rand($seasons)],
                'gender' => $genders[array_rand($genders)],
                'views_count' => rand(0, 500),
                'rating' => rand(30, 50) / 10,
                'reviews_count' => rand(0, 20),
                'created_at' => time() - rand(0, 2592000),
                'updated_at' => time(),
            ]);

            if ($product->save()) {
                // Добавляем изображения
                $this->addProductImages($product);
                // Добавляем размеры
                $this->addProductSizes($product);
                // Добавляем характеристики
                $this->addProductCharacteristics($product);
                
                $this->stdout("✓ Создан товар #{$product->id}: {$product->name}\n");
            } else {
                $this->stdout("✗ Ошибка создания товара: " . print_r($product->errors, true) . "\n");
            }
        }

        $this->stdout("\nГотово! Создано {$count} тестовых товаров.\n");
    }

    private function getOrCreateBrand($name)
    {
        $brand = Brand::find()->where(['name' => $name])->one();
        if (!$brand) {
            $brand = new Brand([
                'name' => $name,
                'slug' => strtolower($name),
                'description' => "Бренд {$name}"
            ]);
            $brand->save();
        }
        return $brand->id;
    }

    private function generateDescription($brand, $model, $color)
    {
        return "<p>Оригинальные кроссовки <strong>{$brand} {$model}</strong> в цвете {$color}.</p>
<p><strong>Особенности:</strong></p>
<ul>
<li>Премиальные материалы</li>
<li>Оригинальная фурнитура</li>
<li>Удобная посадка</li>
<li>Стильный дизайн</li>
</ul>
<p>Идеально подходят для повседневной носки и спортивных занятий.</p>";
    }

    private function addProductImages($product)
    {
        // Используем placeholder изображения
        $placeholders = [
            "https://placehold.co/600x400/3b82f6/ffffff?text={$product->brand->name}+{$product->model_name}",
            "https://placehold.co/600x400/8b5cf6/ffffff?text=Side+View",
            "https://placehold.co/600x400/ec4899/ffffff?text=Back+View",
        ];

        $positions = [0, 1, 2];
        shuffle($positions);

        foreach ($placeholders as $i => $url) {
            $image = new ProductImage([
                'product_id' => $product->id,
                'url' => $url,
                'alt' => "{$product->name} - фото " . ($i + 1),
                'position' => $positions[$i],
                'is_main' => $i === 0 ? 1 : 0,
            ]);
            $image->save();
        }

        // Устанавливаем главное изображение
        $product->main_image = $placeholders[0];
        $product->save(false);
    }

    private function addProductSizes($product)
    {
        $sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45];
        $usSizes = [4, 5, 5.5, 6, 6.5, 7, 8, 9, 10, 11];
        $ukSizes = [3.5, 4, 4.5, 5, 5.5, 6, 7, 8, 9, 10];

        foreach ($sizes as $i => $euSize) {
            $stock = rand(0, 10);
            $size = new ProductSize([
                'product_id' => $product->id,
                'size_eu' => $euSize,
                'size_us' => $usSizes[$i] ?? null,
                'size_uk' => $ukSizes[$i] ?? null,
                'stock_quantity' => $stock,
                'is_available' => $stock > 0 ? 1 : 0,
                'sku' => "{$product->id}-{$euSize}",
            ]);
            $size->save();
        }
    }

    private function addProductCharacteristics($product)
    {
        $characteristics = [
            ['name' => 'Верх', 'value' => ['Натуральная кожа', 'Замша', 'Сетка'][array_rand(['Натуральная кожа', 'Замша', 'Сетка'])]],
            ['name' => 'Подкладка', 'value' => ['Текстиль', 'Синтетика', 'Натуральная кожа'][array_rand(['Текстиль', 'Синтетика', 'Натуральная кожа'])]],
            ['name' => 'Подошва', 'value' => ['Резина', 'Полиуретан', 'Пеноматериал'][array_rand(['Резина', 'Полиуретан', 'Пеноматериал'])]],
            ['name' => 'Страна производства', 'value' => ['Вьетнам', 'Индонезия', 'Китай'][array_rand(['Вьетнам', 'Индонезия', 'Китай'])]],
            ['name' => 'Вес', 'value' => rand(300, 500) . ' г'],
        ];

        foreach ($characteristics as $char) {
            $charValue = new ProductCharacteristicValue([
                'product_id' => $product->id,
                'characteristic_id' => null,
                'value' => $char['value'],
            ]);
            $charValue->save();
        }
    }
}
