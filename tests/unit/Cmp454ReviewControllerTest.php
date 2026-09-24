<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;
use Yii;
use app\backend\modules\account\models\Customer;
use app\backend\modules\catalog\controllers\CatalogController;
use app\backend\modules\catalog\controllers\ReviewController;
use app\backend\modules\catalog\models\Product;
use app\backend\modules\catalog\models\ProductReview;

/**
 * Регрессионные тесты CMP-454: форма отзыва на странице товара возвращала
 * {"success":true} и ничего не сохраняла — фронт слал POST на
 * CatalogController::actionSubmitReview, которая только отправляла письмо
 * администратору (или молча глотала ошибку письма и всё равно отвечала
 * success:true), не создавая ни одной строки в product_review.
 *
 * Решение CEO (см. описание CMP-454): формой владеет
 * ReviewController::actionCreate (система записи product_review + модерация),
 * actionSubmitReview удаляется как мёртвый дублирующий путь, гость не может
 * физически пройти (product_review.user_id — FK на customer).
 */
class Cmp454ReviewControllerTest extends TestCase
{
    private array $cleanup = [];

    protected function tearDown(): void
    {
        Yii::$app->session->remove('customer_id');
        $_SERVER['REQUEST_METHOD'] = 'GET';
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

    private function makeCustomer(): Customer
    {
        $customer = new Customer();
        $customer->email = 'cmp454.' . uniqid() . '@example.com';
        $customer->first_name = 'Ольга';
        $customer->last_name = 'Петрова';
        $customer->setPassword('Passw0rd!');
        $customer->generateAuthKey();
        $customer->status = Customer::STATUS_ACTIVE;
        $this->assertTrue($customer->save(), 'Customer::save() упал: ' . json_encode($customer->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $customer;
        return $customer;
    }

    /**
     * До фикса: форма слала POST на /catalog/submit-review
     * (CatalogController::actionSubmitReview), который только отправлял письмо
     * и отвечал {"success":true} даже когда письмо падало — ни одной строки
     * в product_review не появлялось. Теперь форма зовёт
     * ReviewController::actionCreate — реальный live-прогон под залогиненным
     * покупателем обязан создать строку с user_id = customer.id и кириллицей
     * без искажений.
     */
    public function testLoggedInCustomerReviewIsPersistedToProductReview(): void
    {
        $customer = $this->makeCustomer();
        Yii::$app->session->set('customer_id', $customer->id);

        $controller = new ReviewController('review', Yii::$app);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'product_id' => 1,
            'rating' => 5,
            'comment' => 'Отличные кроссовки, ношу второй месяц — полёт нормальный!',
        ]);

        $result = $controller->runAction('create');

        $this->assertTrue(
            $result['success'] ?? false,
            'ReviewController::actionCreate() отклонил валидный запрос залогиненного покупателя: '
                . json_encode($result, JSON_UNESCAPED_UNICODE)
        );
        // Текст должен честно говорить про модерацию, а не про публикацию —
        // критерий приёмки CMP-454 #4.
        $this->assertStringContainsString('модерац', mb_strtolower($result['message'] ?? ''));

        $saved = ProductReview::find()
            ->where(['product_id' => 1, 'user_id' => $customer->id])
            ->one();
        $this->assertNotNull($saved, 'Отзыв не найден в product_review после успешного ответа контроллера');
        $this->cleanup[] = $saved;

        $this->assertSame('Отличные кроссовки, ношу второй месяц — полёт нормальный!', $saved->comment);
        $this->assertSame('pending', $saved->status);
        // Не должен появиться на витрине до одобрения в админке (CMP-410 путь).
        $this->assertSame(0, (int)$saved->is_published);
    }

    /**
     * product_review.user_id — FK на customer: гость физически не может быть
     * автором отзыва. ReviewController::behaviors() должен отклонить запрос
     * без session['customer_id'] и явно сказать, что нужно авторизоваться —
     * а не принять данные и потерять их (критерий приёмки CMP-454 #2).
     */
    public function testGuestCannotCreateReviewAndIsToldToLogIn(): void
    {
        Yii::$app->session->remove('customer_id');

        $controller = new ReviewController('review', Yii::$app);
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([
            'product_id' => 1,
            'rating' => 5,
            'comment' => 'Гостевой отзыв, которого быть не должно',
        ]);

        $controller->runAction('create');
        $response = Yii::$app->response->data;

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('авторизова', mb_strtolower($response['message'] ?? ''));

        $this->assertNull(
            ProductReview::find()->where(['comment' => 'Гостевой отзыв, которого быть не должно'])->one(),
            'Гостевой запрос не должен был создать строку в product_review'
        );
    }

    /**
     * Отзыв не должен появляться в Product::getReviews() (витрина товара)
     * до is_published=true — путь модерации переиспользован из CMP-410, а не
     * построен заново (критерий приёмки CMP-454 #3).
     */
    public function testReviewHiddenFromProductUntilPublishedThenVisible(): void
    {
        $customer = $this->makeCustomer();

        $review = new ProductReview();
        $review->product_id = 1;
        $review->user_id = $customer->id;
        $review->name = $customer->getFullName();
        $review->rating = 4;
        $review->comment = 'Отзыв CMP-454 на модерации';
        $review->status = 'pending';
        $this->assertTrue($review->save(), 'ProductReview::save() упал: ' . json_encode($review->errors, JSON_UNESCAPED_UNICODE));
        $this->cleanup[] = $review;

        $product = Product::findOne(1);
        $this->assertNotNull($product, 'Ожидался сид-товар с id=1');

        $visibleIds = array_map(fn ($r) => $r->id, $product->getReviews()->all());
        $this->assertNotContains($review->id, $visibleIds, 'Неопубликованный отзыв не должен быть виден на странице товара');

        $review->is_published = true;
        $review->save(false, ['is_published']);

        $product->refresh();
        $visibleIdsAfter = array_map(fn ($r) => $r->id, $product->getReviews()->all());
        $this->assertContains($review->id, $visibleIdsAfter, 'Одобренный отзыв должен появиться на странице товара');
    }

    /**
     * actionSubmitReview — дублирующий мёртвый путь (письмо вместо записи в
     * БД) — удалён вместе с маршрутом catalog/submit-review (критерий
     * приёмки CMP-454 #5): в шаблоне и JS не осталось живых потребителей.
     */
    public function testActionSubmitReviewRemovedFromCatalogController(): void
    {
        $this->assertFalse(
            method_exists(CatalogController::class, 'actionSubmitReview'),
            'CatalogController::actionSubmitReview должен быть удалён — единственная система записи отзыва теперь ReviewController::actionCreate'
        );
    }
}
