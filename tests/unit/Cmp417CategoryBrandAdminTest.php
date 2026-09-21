<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\backend\modules\admin\AdminModule;
use app\backend\modules\admin\controllers\BaseAdminController;
use app\backend\modules\admin\controllers\CategoryController;
use app\backend\modules\admin\controllers\BrandController;
use app\backend\modules\catalog\models\Category;
use app\backend\modules\catalog\models\Brand;

/**
 * Регрессионные тесты CMP-417: живой HTTP POST на CategoryController/
 * BrandController обнажил два бага, которые предыдущий GET-только сканер
 * (CMP-413) не мог найти:
 *
 * 1. Category/Brand::behaviors() отдавали slug SluggableBehavior'у прямо из
 *    'name'. yii\helpers\Inflector::transliterate() без php-intl (расширение
 *    не гарантировано ни локально, ни на проде) молча падает на fallback-карту
 *    $transliteration, которая не покрывает кириллицу — Inflector::slug()
 *    затем вырезает ВСЕ кириллические буквы регэкспом [^a-zA-Z0-9...].
 *    Категория/бренд с чисто кириллическим названием получали slug = ''
 *    (следующая такая же запись — slug = '-2' и т.п.), что делает
 *    /catalog/category/{slug} и /catalog/brand/{slug} недостижимыми.
 *
 * 2. CategoryController::actionDelete не проверял дочерние категории —
 *    в схеме category.parent_id нет FK/ON DELETE. Удаление родителя оставляло
 *    дочерние категории с parent_id, указывающим на несуществующую запись.
 */
class Cmp417CategoryBrandAdminTest extends TestCase
{
    private static ?AdminModule $adminModule = null;
    private array $cleanup = [];

    private static function adminModule(): AdminModule
    {
        return self::$adminModule ??= new AdminModule('admin');
    }

    /**
     * actionDelete() вызывает $this->redirect(['index']), которое резолвит
     * маршрут через Url::to() относительно Yii::$app->controller — вне
     * полного HTTP-цикла (прямой вызов action в PHPUnit) его нужно
     * выставить вручную, как и в Cmp413RouteSweepTest.
     */
    private function callActionDelete(BaseAdminController $controller, int $id): void
    {
        $previous = Yii::$app->controller;
        Yii::$app->controller = $controller;
        try {
            $controller->actionDelete($id);
        } finally {
            Yii::$app->controller = $previous;
        }
    }

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

    /**
     * Category::save() с чисто кириллическим названием должно генерировать
     * непустой, транслитерированный в латиницу slug (а не '').
     */
    public function testCategorySaveWithCyrillicNameProducesNonEmptyLatinSlug(): void
    {
        $category = new Category();
        $category->name = 'Женская обувь Осень Зима ' . uniqid();

        $this->assertTrue($category->save(), json_encode($category->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $category;

        $this->assertNotSame('', $category->slug, 'slug пустой — категория недостижима на /catalog/category/{slug}');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $category->slug);
        $this->assertStringContainsString('zhenskaya', $category->slug);
    }

    /** Тот же класс бага, что и Category, но для Brand::behaviors(). */
    public function testBrandSaveWithCyrillicNameProducesNonEmptyLatinSlug(): void
    {
        $brand = new Brand();
        $brand->name = 'Кроссовкин ' . uniqid();

        $this->assertTrue($brand->save(), json_encode($brand->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $brand;

        $this->assertNotSame('', $brand->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $brand->slug);
        $this->assertStringContainsString('krossovkin', $brand->slug);
    }

    /**
     * Названия без кириллицы (Nike, Adidas...) должны продолжать
     * транслитерироваться так же, как раньше (no regression).
     */
    public function testBrandSaveWithLatinNameKeepsPlainSlug(): void
    {
        $brand = new Brand();
        $brand->name = 'CMP417 Latin Test ' . uniqid();

        $this->assertTrue($brand->save(), json_encode($brand->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $brand;

        $this->assertStringStartsWith('cmp417-latin-test-', $brand->slug);
    }

    /**
     * Кириллические спецсимволы в name/description/SEO meta-полях должны
     * сохраняться в БД без обрезки/искажения (round-trip через реальную БД).
     */
    public function testCategorySavesCyrillicSpecialCharsIntactInDescriptionAndSeoFields(): void
    {
        $category = new Category();
        $category->name = 'Женская обувь «Осень-Зима» ' . uniqid();
        $category->description = 'Описание с «кавычками», тире — и спецсимволами: №, §, ±';
        $category->meta_title = 'SEO заголовок «тест»';
        $category->meta_description = 'SEO описание с ё, э, ъ';

        $this->assertTrue($category->save(), json_encode($category->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $category;

        $reloaded = Category::findOne($category->id);
        $this->assertSame($category->name, $reloaded->name);
        $this->assertSame($category->description, $reloaded->description);
        $this->assertSame($category->meta_title, $reloaded->meta_title);
        $this->assertSame($category->meta_description, $reloaded->meta_description);
    }

    /**
     * CategoryController::actionDelete должен отказывать в удалении категории,
     * у которой есть дочерние категории (нет FK/ON DELETE в схеме —
     * без явной проверки в контроллере родитель удалялся, а дочерняя
     * категория оставалась с parent_id на несуществующую запись).
     */
    public function testActionDeleteBlocksParentWithChildCategories(): void
    {
        $parent = new Category();
        $parent->name = 'CMP-417 родитель ' . uniqid();
        $this->assertTrue($parent->save());
        $this->cleanup[] = $parent;

        $child = new Category();
        $child->name = 'CMP-417 ребёнок ' . uniqid();
        $child->parent_id = $parent->id;
        $this->assertTrue($child->save());
        $this->cleanup[] = $child;

        $controller = new CategoryController('category', self::adminModule());
        $this->callActionDelete($controller, $parent->id);

        $this->assertNotNull(
            Category::findOne($parent->id),
            'Родительская категория была удалена, хотя у неё есть дочерние'
        );

        // После удаления дочерней категории удаление родителя должно пройти.
        $child->delete();
        $this->cleanup = array_filter($this->cleanup, fn ($m) => $m !== $child);

        $this->callActionDelete($controller, $parent->id);
        $this->assertNull(Category::findOne($parent->id), 'Родительская категория без детей должна удаляться');
        $this->cleanup = array_filter($this->cleanup, fn ($m) => $m !== $parent);
    }

    /**
     * Sanity: BrandController::actionDelete продолжает работать как раньше
     * (регрессия не затронула его — только Category получил новую проверку).
     */
    public function testBrandActionDeleteRemovesBrandWithoutProducts(): void
    {
        $brand = new Brand();
        $brand->name = 'CMP-417 удаляемый бренд ' . uniqid();
        $this->assertTrue($brand->save());

        $controller = new BrandController('brand', self::adminModule());
        $this->callActionDelete($controller, $brand->id);

        $this->assertNull(Brand::findOne($brand->id));
    }
}
