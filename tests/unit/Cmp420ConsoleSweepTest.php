<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\console\controllers\ElasticsearchController;
use app\console\controllers\ProductController as ConsoleProductController;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductImage;
use app\backend\modules\catalog\models\ProductSize;
use app\backend\modules\catalog\models\ProductCharacteristicValue;

/**
 * Регрессионные тесты CMP-420: живой прогон всех console-команд (cron).
 *
 * Каждый тест воспроизводит один подтверждённый баг из живого прогона
 * `php yii <route>` (docs/route-audit/CMP-420-console-sweep.md) и проверяет,
 * что он больше не повторяется.
 */
class Cmp420ConsoleSweepTest extends TestCase
{
    /**
     * infrastructure/config/console.php долго расходился с web.php: 'redis'
     * (+ 'timeout' — несуществующее в yii2-redis 2.1.2 свойство вместо
     * 'connectionTimeout'), 'amocrm', 'moysklad', 'moyskladClient' были
     * зарегистрированы только в web.php. Из-за этого `php yii help` и любая
     * консольная команда, трогающая эти компоненты (production/*,
     * amocrm/*, moy-sklad-sync/*), падала с UnknownPropertyException ещё до
     * первого обращения к внешнему сервису.
     */
    public function testConsoleConfigRegistersComponentsPresentInWebConfig(): void
    {
        $config = require dirname(__DIR__, 2) . '/infrastructure/config/console.php';
        $components = $config['components'];

        $this->assertSame('yii\redis\Connection', $components['redis']['class']);
        $this->assertArrayNotHasKey(
            'timeout',
            $components['redis'],
            "'timeout' не существует в yii\\redis\\Connection 2.1.2 (есть connectionTimeout/dataTimeout) — Setting unknown property"
        );
        $this->assertArrayHasKey('connectionTimeout', $components['redis']);

        $this->assertSame('app\backend\shared\components\AmocrmClient', $components['amocrm']['class']);
        $this->assertSame('app\backend\shared\services\MoySkladService', $components['moysklad']['class']);
        $this->assertSame('app\backend\shared\components\MoyskladClient', $components['moyskladClient']['class']);
        $this->assertSame('yii\mutex\FileMutex', $components['mutex']['class']);

        // CMP-417/CMP-420: viewPath стал '@app/backend/shared/mail' в web.php,
        // console.php остался со старым '@app/mail' — письма из консольных
        // команд не находили шаблоны.
        $this->assertSame('@app/backend/shared/mail', $components['mailer']['viewPath']);
    }

    /**
     * `php yii elasticsearch/index-all` всегда возвращал ExitCode::OK, даже
     * если ни один товар не проиндексировался (Elasticsearch недоступен) —
     * cron никогда не сигнализировал о полностью упавшем поиске.
     */
    public function testElasticsearchIndexAllReturnsErrorWhenEverythingFails(): void
    {
        $controller = new ElasticsearchController('elasticsearch', Yii::$app);

        $exitCode = $controller->actionIndexAll();

        $this->assertNotSame(
            \yii\console\ExitCode::OK,
            $exitCode,
            'index-all должен сигнализировать ошибку, если ни один товар не проиндексирован'
        );
    }

    /**
     * `php yii product/create-test-products` был физически недостижим
     * (namespace app\backend\console\controllers не совпадал с
     * controllerNamespace консоли), поэтому расхождение со схемой БД никогда
     * не проявлялось: material/season/gender хранили русские слова вместо
     * slug'ов из Product::rules() 'in'-валидатора, ProductImage::url —
     * read-only геттер (реальная колонка — image), ProductSize не имел
     * колонок size_eu/size_us/stock_quantity/sku, а
     * ProductCharacteristicValue.characteristic_id обязателен (NOT NULL).
     */
    public function testCreateTestProductsPersistsProductWithValidRelatedRows(): void
    {
        $controller = new ConsoleProductController('product', Yii::$app);
        $maxIdBefore = (int) Product::find()->max('id');

        $controller->actionCreateTestProducts(1);

        $product = Product::find()->where(['>', 'id', $maxIdBefore])->orderBy(['id' => SORT_DESC])->one();
        $this->assertNotNull($product);
        $this->assertContains($product->material, ['leather', 'textile', 'synthetic', 'suede', 'mesh', 'canvas']);
        $this->assertContains($product->season, ['summer', 'winter', 'demi', 'all']);
        $this->assertContains($product->gender, ['male', 'female', 'unisex']);

        $this->assertSame(3, ProductImage::find()->where(['product_id' => $product->id])->count());
        $this->assertSame(10, ProductSize::find()->where(['product_id' => $product->id])->count());
        $this->assertSame(5, ProductCharacteristicValue::find()->where(['product_id' => $product->id])->count());
    }
}
