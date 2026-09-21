<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\ProductImage;
use app\backend\modules\catalog\models\Brand;
use app\backend\modules\catalog\models\Category;

/**
 * Регрессионные тесты CMP-417: живой HTTP POST на ProductController обнажил,
 * что предыдущий сканер маршрутов (CMP-413) делал только GET и никогда не
 * прогонял save()/validate() реальных AR-моделей товара с кириллическими
 * данными под MySQL 8 strict mode.
 *
 * Каждый тест воспроизводит один подтверждённый живой сценарий (create/edit/
 * toggle/add-size/add-image через реальный HTTP-клиент, см.
 * scripts/cmp417-http-client.php) как save()/validate() напрямую на реальной
 * БД + проверка сохранённых данных.
 */
class Cmp417ProductAdminTest extends TestCase
{
    private array $cleanup = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanup) as $model) {
            try {
                $model->delete();
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
        $this->cleanup = [];
        parent::tearDown();
    }

    private function existingBrandId(): int
    {
        $brand = Brand::find()->one();
        if (!$brand) {
            $brand = new Brand();
            $brand->name = 'CMP-417 тестовый бренд';
            $brand->save();
            $this->cleanup[] = $brand;
        }
        return $brand->id;
    }

    private function existingCategoryId(): int
    {
        $category = Category::find()->one();
        if (!$category) {
            $category = new Category();
            $category->name = 'CMP-417 тестовая категория';
            $category->save();
            $this->cleanup[] = $category;
        }
        return $category->id;
    }

    /**
     * actionCreate — сохранение товара с кириллическим названием, кавычками
     * «ёлочка», номерным знаком № и длинным описанием с амперсандами и
     * апострофами не должно падать под strict mode и не должно обрубать/
     * искажать данные при чтении обратно из БД.
     */
    public function testCreateSavesCyrillicNameAndDescriptionIntact(): void
    {
        $name = 'Кроссовки Nike Air Max 90 «Зимняя коллекция» №2';
        $description = "Оригинальные кроссовки Nike & Co. с амперсандом и апострофом (it's a test).\n"
            . 'Кавычки «ёлочка» и «лапки», спецсимволы: №, §, ±.';

        $product = new Product();
        $product->name = $name;
        $product->description = $description;
        $product->price = 299.99;
        $product->old_price = 349.99;
        $product->brand_id = $this->existingBrandId();
        $product->category_id = $this->existingCategoryId();
        $product->meta_title = 'SEO Заголовок «Nike Air Max 90» №2';
        $product->meta_description = 'SEO описание с амперсандом Nike & Air Max и апострофом it\'s.';
        $product->meta_keywords = 'найк, аир макс, кроссовки, №2, зима';
        $product->is_active = true;

        $this->assertTrue($product->validate(), json_encode($product->errors, JSON_UNESCAPED_UNICODE));
        $this->assertTrue($product->save(false));
        $this->cleanup[] = $product;

        $reloaded = Product::findOne($product->id);
        $this->assertSame($name, $reloaded->name, 'Кириллическое название было обрублено/искажено');
        $this->assertSame($description, $reloaded->description, 'Описание с амперсандом/апострофом было искажено');
    }

    /**
     * actionEdit — повторное сохранение (update) того же товара с новым
     * названием/описанием проходит без ошибок strict mode.
     */
    public function testEditUpdatesCyrillicFieldsInPlace(): void
    {
        $product = new Product();
        $product->name = 'CMP-417 товар для редактирования';
        $product->price = 100;
        $product->brand_id = $this->existingBrandId();
        $product->category_id = $this->existingCategoryId();
        $this->assertTrue($product->save());
        $this->cleanup[] = $product;

        $newName = 'Кроссовки Nike Air Max 90 «Обновлённая версия» №3 — весна';
        $newDescription = "Обновлённое описание с амперсандом Nike & Adidas, апострофом it's the best.";
        $product->name = $newName;
        $product->description = $newDescription;
        $product->price = 319.99;

        $this->assertTrue($product->save(), json_encode($product->errors, JSON_UNESCAPED_UNICODE));

        $reloaded = Product::findOne($product->id);
        $this->assertSame($newName, $reloaded->name);
        $this->assertSame($newDescription, $reloaded->description);
        $this->assertEqualsWithDelta(319.99, (float) $reloaded->price, 0.001);
    }

    /**
     * actionToggle — is_active флип и обратно, без потери значения между
     * двумя save(false) в реальной БД.
     */
    public function testToggleFlipsIsActiveAndPersists(): void
    {
        $product = new Product();
        $product->name = 'CMP-417 товар для toggle';
        $product->price = 100;
        $product->brand_id = $this->existingBrandId();
        $product->category_id = $this->existingCategoryId();
        $product->is_active = true;
        $this->assertTrue($product->save());
        $this->cleanup[] = $product;

        $product->is_active = $product->is_active ? 0 : 1;
        $this->assertTrue($product->save(false));
        $this->assertEquals(0, Product::findOne($product->id)->is_active);

        $product->is_active = $product->is_active ? 0 : 1;
        $this->assertTrue($product->save(false));
        $this->assertEquals(1, Product::findOne($product->id)->is_active);
    }

    /**
     * actionAddSize — ProductSize::save() с реальными данными (размер,
     * остаток, цена) проходит под strict mode.
     */
    public function testAddSizeSavesRealSizeData(): void
    {
        $product = new Product();
        $product->name = 'CMP-417 товар для размера';
        $product->price = 100;
        $product->brand_id = $this->existingBrandId();
        $product->category_id = $this->existingCategoryId();
        $this->assertTrue($product->save());
        $this->cleanup[] = $product;

        $size = new ProductSize();
        $size->product_id = $product->id;
        $size->size = '42';
        $size->us_size = '9';
        $size->eu_size = '42';
        $size->uk_size = '8';
        $size->stock = 15;
        $size->is_available = 1;
        $size->price_byn = 319.99;

        $this->assertTrue($size->save(), json_encode($size->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $size;

        $reloaded = ProductSize::findOne($size->id);
        $this->assertSame('42', $reloaded->size);
        $this->assertEquals(15, $reloaded->stock);
    }

    /**
     * CMP-417 БАГ: `product_image`.`created_at` — `int NOT NULL` без дефолта
     * (infrastructure/migrations/m250101_000000_create_base_tables.php), но
     * ProductImage не заполняла её ни в одном behavior/beforeSave — ЛЮБОЙ
     * ProductImage::save() (actionAddImage) падал вживую с
     * "SQLSTATE[HY000]: 1364 Field 'created_at' doesn't have a default
     * value". Пофикшено добавлением TimestampBehavior (createdAtAttribute
     * только, updatedAtAttribute=false — колонки updated_at в таблице нет).
     */
    public function testAddImageSavesWithoutMissingCreatedAtSqlError(): void
    {
        $product = new Product();
        $product->name = 'CMP-417 товар для изображения';
        $product->price = 100;
        $product->brand_id = $this->existingBrandId();
        $product->category_id = $this->existingCategoryId();
        $this->assertTrue($product->save());
        $this->cleanup[] = $product;

        $image = new ProductImage();
        $image->product_id = $product->id;
        $image->image = 'https://example.com/test-image-cmp417.jpg';
        $image->sort_order = 1;

        $this->assertTrue(
            $image->save(),
            'ProductImage::save() упал: ' . json_encode($image->errors, JSON_UNESCAPED_UNICODE)
        );
        $this->cleanup[] = $image;

        $reloaded = ProductImage::findOne($image->id);
        $this->assertIsInt($reloaded->created_at);
        $this->assertGreaterThan(1_700_000_000, $reloaded->created_at, 'created_at не заполнен TimestampBehavior');
    }

    /**
     * actionDeleteSize / actionDeleteImage / actionDelete — удаление
     * созданных тестовых сущностей проходит без ошибок (в т.ч. FK-каскад
     * product -> product_size/product_image).
     */
    public function testDeleteSizeDeleteImageAndDeleteProductSucceed(): void
    {
        $product = new Product();
        $product->name = 'CMP-417 товар для удаления';
        $product->price = 100;
        $product->brand_id = $this->existingBrandId();
        $product->category_id = $this->existingCategoryId();
        $this->assertTrue($product->save());

        $size = new ProductSize();
        $size->product_id = $product->id;
        $size->size = '43';
        $this->assertTrue($size->save());

        $image = new ProductImage();
        $image->product_id = $product->id;
        $image->image = 'https://example.com/test-image-cmp417-delete.jpg';
        $this->assertTrue($image->save());

        $this->assertNotFalse($size->delete());
        $this->assertNull(ProductSize::findOne($size->id));

        $this->assertNotFalse($image->delete());
        $this->assertNull(ProductImage::findOne($image->id));

        $this->assertNotFalse($product->delete());
        $this->assertNull(Product::findOne($product->id));
    }
}
