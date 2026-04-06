<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Управление отзывами';
$pendingCount = $stats['pending'] ?? 0;
?>

<div id="review-page-container" data-reply-url="<?= Url::to(['/admin/review/respond']) ?>"></div>

<div class="review-page">
    <div class="page-header">
        <h1 class="page-title"><?= Html::encode($this->title) ?></h1>
    </div>

    <!-- Статистика -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Всего</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['published'] ?></div>
            <div class="stat-label">Опубликовано</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['pending'] ?></div>
            <div class="stat-label">На модерации</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['featured'] ?></div>
            <div class="stat-label">Избранные</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['avg_rating'], 1) ?> ⭐</div>
            <div class="stat-label">Средний рейтинг</div>
        </div>
    </div>

    <!-- Фильтры -->
    <div class="filters">
        <a href="<?= Url::to(['index']) ?>" class="filter-btn <?= !Yii::$app->request->get('status') ? 'active' : '' ?>">
            <i class="bi bi-list-ul"></i> Все
        </a>
        <a href="<?= Url::to(['index', 'status' => 'pending']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'pending' ? 'active' : '' ?>">
            <i class="bi bi-hourglass-split"></i> На модерации
            <?php if ($pendingCount > 0): ?>
                <span class="filter-badge"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= Url::to(['index', 'status' => 'published']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'published' ? 'active' : '' ?>">
            <i class="bi bi-check-circle"></i> Опубликованные
        </a>
        <a href="<?= Url::to(['index', 'status' => 'rejected']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'rejected' ? 'active' : '' ?>">
            <i class="bi bi-x-circle"></i> Отклоненные
        </a>
        <a href="<?= Url::to(['index', 'status' => 'featured']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'featured' ? 'active' : '' ?>">
            <i class="bi bi-star-fill"></i> Избранные
        </a>
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <a href="<?= Url::to(['index', 'rating' => $i]) ?>" class="filter-btn <?= Yii::$app->request->get('rating') == $i ? 'active' : '' ?>">
                <?= $i ?> ★
            </a>
        <?php endfor; ?>
    </div>

    <!-- Список отзывов -->
    <div class="review-list">
        <?php foreach ($dataProvider->models as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-author">
                        <div class="author-avatar">
                            <?= strtoupper(substr($review->getDisplayName(), 0, 2)) ?>
                        </div>
                        <div class="author-info">
                            <span class="author-name"><?= Html::encode($review->getDisplayName()) ?></span>
                            <span class="review-date"><?= Yii::$app->formatter->asDatetime($review->created_at) ?></span>
                        </div>
                    </div>
                    <div class="review-badges">
                        <?php if ($review->is_published): ?>
                            <span class="badge badge-published">Опубликован</span>
                        <?php else: ?>
                            <span class="badge badge-pending">На модерации</span>
                        <?php endif; ?>
                        <?php if ($review->is_featured): ?>
                            <span class="badge badge-featured">Избранный</span>
                        <?php endif; ?>
                        <?php if ($review->is_verified): ?>
                            <span class="badge badge-verified">✓ Подтвержден</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?= $i <= $review->rating ? '' : 'empty' ?>">★</span>
                    <?php endfor; ?>
                </div>

                <?php if ($review->product): ?>
                    <div class="review-product">
                        <a href="<?= Url::to(['/admin/product/view', 'id' => $review->product_id]) ?>">
                            <?= Html::encode($review->product->name) ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ($review->title): ?>
                    <div class="review-title"><?= Html::encode($review->title) ?></div>
                <?php endif; ?>

                <div class="review-content"><?= Html::encode($review->content) ?></div>

                <?php if ($review->pros || $review->cons): ?>
                    <div class="review-pros-cons">
                        <?php if ($review->pros): ?>
                            <div class="pros">
                                <strong>✓ Достоинства:</strong><br>
                                <?= Html::encode($review->pros) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($review->cons): ?>
                            <div class="cons">
                                <strong>✗ Недостатки:</strong><br>
                                <?= Html::encode($review->cons) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($review->admin_response): ?>
                    <div class="admin-response">
                        <div class="admin-response-label">Ответ администратора (<?= Yii::$app->formatter->asDatetime($review->admin_response_at) ?>)</div>
                        <?= Html::encode($review->admin_response) ?>
                    </div>
                <?php endif; ?>

                <div class="review-actions">
                    <?php if (!$review->is_published): ?>
                        <?= Html::a('<i class="bi bi-check-lg"></i> Опубликовать', ['publish', 'id' => $review->id], [
                            'class' => 'btn-action success',
                            'data' => ['method' => 'post'],
                        ]) ?>
                        <?= Html::a('<i class="bi bi-x-lg"></i> Отклонить', ['reject', 'id' => $review->id], [
                            'class' => 'btn-action danger',
                            'data' => ['method' => 'post', 'confirm' => 'Отклонить отзыв?'],
                        ]) ?>
                    <?php else: ?>
                        <?= Html::a('<i class="bi bi-eye-slash"></i> Снять', ['unpublish', 'id' => $review->id], ['class' => 'btn-action', 'data' => ['method' => 'post']]) ?>
                    <?php endif; ?>

                    <a href="<?= Url::to(['toggle-featured', 'id' => $review->id]) ?>" class="btn-action">
                        <?= $review->is_featured ? '<i class="bi bi-star-fill"></i> Из избранных' : '<i class="bi bi-star"></i> В избранное' ?>
                    </a>

                    <button type="button" class="btn-action" onclick="toggleReplyForm(<?= $review->id ?>)">
                        <i class="bi bi-reply"></i> Ответить
                    </button>

                    <?= Html::a('<i class="bi bi-trash"></i> Удалить', ['delete', 'id' => $review->id], [
                        'class' => 'btn-action danger',
                        'data' => ['method' => 'post', 'confirm' => 'Удалить отзыв?'],
                    ]) ?>
                </div>

                <!-- Inline Reply Form -->
                <div id="reply-form-<?= $review->id ?>" class="reply-form" style="display:none;">
                    <div class="reply-form-inner">
                        <label class="reply-label"><i class="bi bi-chat-left-text"></i> Ответ администратора</label>
                        <textarea
                            id="reply-text-<?= $review->id ?>"
                            class="reply-textarea"
                            rows="3"
                            placeholder="Введите ответ на отзыв..."
                        ><?= $review->admin_response ? Html::encode($review->admin_response) : '' ?></textarea>
                        <div class="reply-actions">
                            <button type="button" class="btn-action success" onclick="submitReply(<?= $review->id ?>)">
                                <i class="bi bi-send"></i> Отправить ответ
                            </button>
                            <button type="button" class="btn-action" onclick="toggleReplyForm(<?= $review->id ?>)">
                                Отмена
                            </button>
                        </div>
                        <div id="reply-result-<?= $review->id ?>" class="reply-result" style="display:none;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Пагинация -->
    <?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
</div>

