<?php

use yii\helpers\Html;

$this->title = 'ALT тексты изображений';
?>

<div class="seo-alt-texts">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="alert alert-success mb-3">
        <h5>🎯 Быстрая победа — заполнение ALT текстов</h5>
        <p class="mb-2">ALT тексты помогают поисковикам понимать содержимое изображений и улучшают доступность для слабовидящих.</p>
        <p class="mb-0"><strong>Статистика:</strong><br>
        📷 Всего изображений: <strong><?= $stats['total_images'] ?></strong><br>
        ✅ С ALT текстом: <strong><?= $stats['with_alt'] ?></strong><br>
        ❌ Без ALT текста: <strong><?= $stats['without_alt'] ?></strong></p>
    </div>
    
    <div class="alert alert-info mb-3">
        <strong>💡 Советы по написанию ALT текстов:</strong>
        <ul class="mb-0 mt-2">
            <li>Опишите что изображено на фото: <code>Nike Air Max 90 черные с белой подошвой</code></li>
            <li>Используйте ключевые слова естественно: <code>Кроссовки Adidas Yeezy Boost оригинал</code></li>
            <li>Длиной 3-8 слов оптимально</li>
            <li>Не начинайте с "Фото" или "Изображение"</li>
        </ul>
    </div>
    
    <p>
        <?= Html::a('← SEO панель', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </p>
    
    <div class="row">
        <?php foreach ($products as $product): ?>
            <?php if (empty($product->images)) continue; ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><?= Html::encode($product->name) ?></span>
                        <small class="text-muted">ID: <?= $product->id ?></small>
                    </div>
                    <div class="card-body">
                        <?php foreach ($product->images as $image): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="<?= $image->getUrl() ?>" alt="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;" class="me-3">
                                <div class="flex-grow-1">
                                    <label class="form-label small text-muted mb-1">ALT текст</label>
                                    <input type="text" 
                                           class="form-control form-control-sm <?= empty($image->alt_text) ? 'is-invalid' : 'is-valid' ?>" 
                                           value="<?= Html::encode($image->alt_text) ?>"
                                           placeholder="Опишите изображение..."
                                           onchange="updateImageAlt(<?= $image->id ?>, this.value)">
                                    <?php if (empty($image->alt_text)): ?>
                                        <div class="invalid-feedback small">ALT текст не заполнен</div>
                                    <?php else: ?>
                                        <div class="valid-feedback small">✓ Заполнено</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function updateImageAlt(id, value) {
    fetch('/admin/seo/update-image-alt', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-Token': yii.getCsrfToken(),
        },
        body: 'id=' + id + '&alt_text=' + encodeURIComponent(value)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('ALT текст сохранён!', 'success');
        } else {
            showNotification('Ошибка сохранения', 'error');
        }
    });
}

function showNotification(message, type) {
    const div = document.createElement('div');
    div.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
    div.textContent = message;
    div.style.position = 'fixed';
    div.style.top = '20px';
    div.style.right = '20px';
    div.style.zIndex = '9999';
    div.style.padding = '10px 20px';
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}
</script>
