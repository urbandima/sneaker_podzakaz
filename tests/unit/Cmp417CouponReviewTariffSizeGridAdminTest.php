<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use app\backend\modules\catalog\models\ProductReview;
use app\backend\modules\catalog\models\SizeGrid;
use app\backend\modules\admin\controllers\TariffController;
use Yii;

/**
 * Регрессионные тесты CMP-417: живой HTTP-прогон create/update/delete для купонов,
 * publish/respond/delete для отзывов, create/update/delete/toggle для тарифов и
 * size-grid обнаружил баги, не пойманные предыдущим сканером маршрутов (только GET
 * на admin/*\/create, без реальных POST с данными).
 *
 * Каждый тест воспроизводит один подтверждённый через живой HTTP-запрос баг:
 * - ReviewController::actionPublish/actionUnpublish/actionRespond вызывали
 *   несуществующие методы модели ProductReview::publish()/unpublish()/
 *   addAdminResponse() — 500 yii\base\UnknownMethodException на КАЖДОМ вызове.
 * - ProductReview::behaviors() использовал дефолтный TimestampBehavior, который
 *   пытается писать updated_at — колонки нет в схеме product_review (только
 *   created_at, int) — 500 UnknownPropertyException на КАЖДОМ save() модели,
 *   включая создание отзыва с фронта.
 * - SizeGrid::beforeSave() писал $this->slug — колонки slug нет в схеме
 *   size_grid (см. миграцию m251104_230000_create_size_grid_tables) — 500
 *   UnknownPropertyException на КАЖДОМ actionCreate/actionUpdate.
 * - TariffController::actionToggle не был ограничен VerbFilter только POST (в
 *   отличие от CouponController::actionToggle) — простой GET без CSRF-токена
 *   (GET безопасен для csrfTokenSafeMethods и не проверяется) переключал
 *   is_active тарифа, подтверждено живым запросом.
 */
class Cmp417CouponReviewTariffSizeGridAdminTest extends TestCase
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

    private function makeReview(): ProductReview
    {
        $review = new ProductReview();
        $review->product_id = 1;
        $review->user_id = 1;
        $review->name = 'Тестовый Покупатель CMP-417';
        $review->email = 'cmp417@example.com';
        $review->rating = 5;
        $review->comment = 'Отличный товар, кириллический тестовый отзыв CMP-417';
        $this->assertTrue(
            $review->save(),
            'ProductReview::save() (insert) упал: ' . json_encode($review->errors, JSON_UNESCAPED_UNICODE)
        );
        $this->cleanup[] = $review;
        return $review;
    }

    /**
     * До фикса: ReviewController::actionPublish() вызывал $model->publish(),
     * которого не существовало в ProductReview — yii\base\UnknownMethodException,
     * подтверждено живым POST /admin/review/publish/{id} (500).
     */
    public function testProductReviewPublishTogglesIsPublishedAndPersists()
    {
        $review = $this->makeReview();
        $this->assertSame(0, (int)$review->is_published, 'Свежий отзыв должен быть неопубликован');

        $this->assertTrue($review->publish(), 'ProductReview::publish() упал');

        $fresh = ProductReview::findOne($review->id);
        $this->assertSame(1, (int)$fresh->is_published, 'publish() не сохранил is_published=1 в БД');
    }

    /**
     * До фикса: ReviewController::actionUnpublish() вызывал $model->unpublish(),
     * которого не существовало — тот же класс бага, что и у publish().
     */
    public function testProductReviewUnpublishTogglesIsPublishedAndPersists()
    {
        $review = $this->makeReview();
        $review->is_published = true;
        $review->save(false, ['is_published']);

        $this->assertTrue($review->unpublish(), 'ProductReview::unpublish() упал');

        $fresh = ProductReview::findOne($review->id);
        $this->assertSame(0, (int)$fresh->is_published, 'unpublish() не сохранил is_published=0 в БД');
    }

    /**
     * До фикса: ReviewController::actionRespond() (HTML fallback) вызывал
     * $model->addAdminResponse($resp), которого не существовало. Дополнительно
     * проверяем, что кириллический ответ администрации не обрубается/не бьётся
     * под strict SQL mode после фикса TimestampBehavior.
     */
    public function testProductReviewAddAdminResponsePersistsCyrillicText()
    {
        $review = $this->makeReview();
        $responseText = 'Спасибо за ваш отзыв! Мы рады, что вам понравилось «CMP-417».';

        $this->assertTrue(
            $review->addAdminResponse($responseText),
            'ProductReview::addAdminResponse() упал'
        );

        $fresh = ProductReview::findOne($review->id);
        $this->assertSame($responseText, $fresh->admin_response);
        $this->assertNotNull($fresh->admin_response_at);
    }

    /**
     * До фикса: ProductReview::behaviors() использовал TimestampBehavior без
     * updatedAtAttribute => false. Таблица product_review не имеет колонки
     * updated_at (только created_at, int) — ЛЮБОЙ save() (и insert, и update)
     * падал с "Setting unknown property ...ProductReview::updated_at".
     * makeReview() уже покрывает insert; здесь отдельно фиксируем update.
     */
    public function testProductReviewPlainUpdateDoesNotCrashOnMissingUpdatedAtColumn()
    {
        $review = $this->makeReview();
        $review->rating = 4;

        $this->assertTrue(
            $review->save(),
            'ProductReview::save() (update) упал: ' . json_encode($review->errors, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * До фикса: SizeGrid::beforeSave() писал $this->slug — колонки slug нет в
     * схеме size_grid (см. infrastructure/migrations/m251104_230000_create_size_grid_tables.php).
     * yii\base\UnknownPropertyException валил actionCreate/actionUpdate на КАЖДОМ
     * вызове, подтверждено живым POST /admin/size-grid/create (500).
     */
    public function testSizeGridCreateAndUpdateDoNotCrashOnMissingSlugColumn()
    {
        $grid = new SizeGrid();
        $grid->name = 'Тестовая сетка размеров CMP-417';
        $grid->gender = SizeGrid::GENDER_UNISEX;
        $grid->description = 'Тестовое описание сетки';

        $this->assertTrue(
            $grid->save(),
            'SizeGrid::save() (insert/beforeSave) упал: ' . json_encode($grid->errors, JSON_UNESCAPED_UNICODE)
        );
        $this->cleanup[] = $grid;

        $grid->name = 'Тестовая сетка размеров CMP-417 обновлена';
        $this->assertTrue(
            $grid->save(),
            'SizeGrid::save() (update/beforeSave) упал: ' . json_encode($grid->errors, JSON_UNESCAPED_UNICODE)
        );

        $fresh = SizeGrid::findOne($grid->id);
        $this->assertSame('Тестовая сетка размеров CMP-417 обновлена', $fresh->name);
    }

    /**
     * До фикса: TariffController::behaviors()['verbs']['actions'] ограничивал
     * только 'delete' методом POST (в отличие от CouponController, где 'toggle'
     * тоже был защищён). Живой GET /admin/tariff/toggle/{id} без CSRF-токена
     * переключал is_active — подтверждено (302, is_active сменился 1 -> 0).
     */
    public function testTariffControllerToggleActionIsRestrictedToPost()
    {
        $controller = new TariffController('tariff', Yii::$app);
        $behaviors = $controller->behaviors();

        $this->assertArrayHasKey('verbs', $behaviors);
        $this->assertSame(
            ['POST'],
            $behaviors['verbs']['actions']['toggle'] ?? null,
            'actionToggle должен быть ограничен методом POST, иначе GET без CSRF меняет is_active'
        );
    }
}
