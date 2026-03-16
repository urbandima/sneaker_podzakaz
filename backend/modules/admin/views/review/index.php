<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = '⭐ Управление отзывами';
?>

<style>
.review-page {
    padding: 1.5rem;
    background: #f8fafc;
    min-height: 100vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: #1f2937;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1f2937;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
}

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #374151;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.filter-btn:hover,
.filter-btn.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.review-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.review-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.author-avatar {
    width: 40px;
    height: 40px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #3b82f6;
}

.author-info {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: 600;
    color: #1f2937;
}

.review-date {
    font-size: 0.75rem;
    color: #6b7280;
}

.review-badges {
    display: flex;
    gap: 0.5rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-published {
    background: #d1fae5;
    color: #065f46;
}

.badge-pending {
    background: #fef3c7;
    color: #92400e;
}

.badge-featured {
    background: #dbeafe;
    color: #1e40af;
}

.badge-verified {
    background: #f3f4f6;
    color: #374151;
}

.review-rating {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.star {
    color: #fbbf24;
}

.star.empty {
    color: #e5e7eb;
}

.review-product {
    font-size: 0.875rem;
    color: #3b82f6;
    margin-bottom: 0.75rem;
}

.review-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.review-content {
    color: #374151;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.review-pros-cons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.pros, .cons {
    padding: 0.75rem;
    border-radius: 8px;
    font-size: 0.875rem;
}

.pros {
    background: #f0fdf4;
    color: #166534;
}

.cons {
    background: #fef2f2;
    color: #991b1b;
}

.review-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #f3f4f6;
}

.btn-action {
    padding: 0.375rem 0.75rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #374151;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.75rem;
    transition: all 0.2s;
}

.btn-action:hover {
    background: #f3f4f6;
}

.btn-action.success {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.btn-action.danger {
    color: #ef4444;
}

.admin-response {
    background: #f8fafc;
    border-left: 3px solid #3b82f6;
    padding: 1rem;
    margin-top: 1rem;
    border-radius: 0 8px 8px 0;
}

.admin-response-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}
</style>

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
            Все
        </a>
        <a href="<?= Url::to(['index', 'status' => 'pending']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'pending' ? 'active' : '' ?>">
            На модерации
        </a>
        <a href="<?= Url::to(['index', 'status' => 'published']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'published' ? 'active' : '' ?>">
            Опубликованные
        </a>
        <a href="<?= Url::to(['index', 'status' => 'featured']) ?>" class="filter-btn <?= Yii::$app->request->get('status') === 'featured' ? 'active' : '' ?>">
            Избранные
        </a>
        <?php for ($i = 5; $i >= 1; $i--): ?>
            <a href="<?= Url::to(['index', 'rating' => $i]) ?>" class="filter-btn <?= Yii::$app->request->get('rating') == $i ? 'active' : '' ?>">
                <?= $i ?> ⭐
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
                        <?= Html::a('✓ Опубликовать', ['publish', 'id' => $review->id], ['class' => 'btn-action success', 'data' => ['method' => 'post']]) ?>
                    <?php else: ?>
                        <?= Html::a('✗ Снять', ['unpublish', 'id' => $review->id], ['class' => 'btn-action', 'data' => ['method' => 'post']]) ?>
                    <?php endif; ?>
                    
                    <a href="<?= Url::to(['toggle-featured', 'id' => $review->id]) ?>" class="btn-action">
                        <?= $review->is_featured ? '☆ Убрать из избранных' : '★ В избранное' ?>
                    </a>
                    
                    <a href="<?= Url::to(['respond', 'id' => $review->id]) ?>" class="btn-action">
                        💬 Ответить
                    </a>
                    
                    <?= Html::a('🗑️ Удалить', ['delete', 'id' => $review->id], [
                        'class' => 'btn-action danger',
                        'data' => ['method' => 'post', 'confirm' => 'Удалить отзыв?'],
                    ]) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Пагинация -->
    <?= LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
</div>
