<?php

/** @var yii\web\View $this */
/** @var app\backend\modules\catalog\models\Product $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Новый товар';
?>

<?php
$this->params['headerActions'] = [
    Html::a('<i class="bi bi-arrow-left"></i> Назад', ['/admin/product'], ['class' => 'admin-btn admin-btn-secondary admin-btn-sm'])
];
?>

<!-- Быстрая навигация по секциям -->
<div class="section-nav">
    <a href="#section-basic" class="section-nav-link active"><i class="bi bi-info-circle"></i> Основное</a>
    <a href="#section-characteristics" class="section-nav-link"><i class="bi bi-list-check"></i> Характеристики</a>
    <a href="#section-seo" class="section-nav-link"><i class="bi bi-search"></i> SEO</a>
    <a href="#section-images" class="section-nav-link"><i class="bi bi-images"></i> Фото</a>
</div>

<div class="admin-card">
    <?php $form = ActiveForm::begin(); ?>

    <!-- Секция: Основное -->
    <div class="card-section" id="section-basic">
        <div class="card-section-header">
            <h3 class="card-section-title"><i class="bi bi-info-circle"></i> Основная информация</h3>
        </div>
        <div class="card-section-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div class="form-group">
                    <label class="admin-form-label">Название товара *</label>
                    <?= Html::activeTextInput($model, 'name', ['class' => 'admin-form-input', 'required' => true]) ?>
                </div>

                <div class="form-group">
                    <label class="admin-form-label">Бренд *</label>
                    <?= Html::activeDropDownList($model, 'brand_id',
                        \yii\helpers\ArrayHelper::map($brands, 'id', 'name'),
                        ['class' => 'admin-form-select', 'prompt' => 'Выберите бренд', 'required' => true]) ?>
                </div>

                <div class="form-group">
                    <label class="admin-form-label">Категория *</label>
                    <?= Html::activeDropDownList($model, 'category_id',
                        \yii\helpers\ArrayHelper::map($categories, 'id', 'name'),
                        ['class' => 'admin-form-select', 'prompt' => 'Выберите категорию', 'required' => true]) ?>
                </div>

                <div class="form-group">
                    <label class="admin-form-label">Артикул (SKU)</label>
                    <?= Html::activeTextInput($model, 'sku', ['class' => 'admin-form-input']) ?>
                </div>

                <div class="form-group">
                    <label class="admin-form-label">Цена, BYN *</label>
                    <?= Html::activeTextInput($model, 'price', ['type' => 'number', 'step' => '0.01', 'class' => 'admin-form-input', 'required' => true]) ?>
                </div>

                <div class="form-group">
                    <label class="admin-form-label">Цена в юанях</label>
                    <?= Html::activeTextInput($model, 'price_cny', ['type' => 'number', 'step' => '0.01', 'class' => 'admin-form-input']) ?>
                </div>

                <div class="form-group grid-full">
                    <label class="admin-form-label">Описание</label>
                    <?= Html::activeTextarea($model, 'description', ['class' => 'admin-form-input', 'rows' => 5]) ?>
                </div>

                <div class="form-group">
                    <label class="admin-form-label">Статус активности</label>
                    <div class="d-flex align-center gap-1">
                        <?= Html::activeCheckbox($model, 'is_active', ['label' => false]) ?>
                        <span style="font-size: 14px; color: var(--color-text-secondary);">Активен</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Секция: Характеристики -->
    <div class="card-section" id="section-characteristics">
        <div class="card-section-header">
            <h3 class="card-section-title"><i class="bi bi-list-check"></i> Характеристики товара</h3>
            <p style="color: var(--color-text-secondary); font-size: 14px;">Характеристики будут доступны после сохранения товара</p>
        </div>
        <div class="card-section-body">
            <div class="alert" style="background: var(--color-gray-100); color: var(--color-text-secondary); padding: 1rem; border-radius: var(--radius-md);">
                <i class="bi bi-info-circle"></i> Сохраните товар, чтобы добавить характеристики
            </div>
        </div>
    </div>

    <!-- Секция: SEO -->
    <div class="card-section" id="section-seo">
        <div class="card-section-header">
            <h3 class="card-section-title"><i class="bi bi-search"></i> SEO настройки</h3>
        </div>
        <div class="card-section-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div class="form-group grid-full">
                    <label class="admin-form-label">Meta Title</label>
                    <?= Html::activeTextInput($model, 'meta_title', ['class' => 'admin-form-input']) ?>
                    <small style="color: var(--color-text-secondary); font-size: 12px;">Рекомендуется: 50-60 символов</small>
                </div>

                <div class="form-group grid-full">
                    <label class="admin-form-label">Meta Description</label>
                    <?= Html::activeTextarea($model, 'meta_description', ['class' => 'admin-form-input', 'rows' => 3]) ?>
                    <small style="color: var(--color-text-secondary); font-size: 12px;">Рекомендуется: 150-160 символов</small>
                </div>

                <div class="form-group grid-full">
                    <label class="admin-form-label">Meta Keywords</label>
                    <?= Html::activeTextInput($model, 'meta_keywords', ['class' => 'admin-form-input']) ?>
                    <small style="color: var(--color-text-secondary); font-size: 12px;">Через запятую</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Секция: Фото -->
    <div class="card-section" id="section-images">
        <div class="card-section-header">
            <h3 class="card-section-title"><i class="bi bi-images"></i> Изображения товара</h3>
            <p style="color: var(--color-text-secondary); font-size: 14px;">Изображения будут доступны после сохранения товара</p>
        </div>
        <div class="card-section-body">
            <div class="alert" style="background: var(--color-gray-100); color: var(--color-text-secondary); padding: 1rem; border-radius: var(--radius-md);">
                <i class="bi bi-info-circle"></i> Сохраните товар, чтобы загрузить изображения
            </div>
        </div>
    </div>

    <!-- Кнопки действий -->
    <div class="card-footer">
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <?= Html::a('Отмена', ['/admin/product'], ['class' => 'admin-btn admin-btn-secondary']) ?>
            <?= Html::submitButton('<i class="bi bi-check-circle"></i> Сохранить товар', ['class' => 'admin-btn admin-btn-primary']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<style>
.section-nav {
    display: flex;
    gap: var(--space-2);
    margin-bottom: var(--space-6);
    padding: var(--space-4);
    background: var(--color-gray-100);
    border-radius: var(--radius-lg);
    overflow-x: auto;
}

.section-nav-link {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--color-text-secondary);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    transition: all var(--transition-fast);
    white-space: nowrap;
}

.section-nav-link:hover {
    background: var(--color-gray-200);
    color: var(--color-text);
}

.section-nav-link.active {
    background: var(--color-primary-500);
    color: white;
}

.card-section {
    border-bottom: 1px solid var(--color-border);
}

.card-section:last-child {
    border-bottom: none;
}

.card-section-header {
    padding: var(--space-6);
    background: var(--color-gray-50);
}

.card-section-title {
    margin: 0;
    font-size: var(--text-lg);
    font-weight: var(--font-semibold);
    color: var(--color-text);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.card-section-body {
    padding: var(--space-6);
}

.card-footer {
    padding: var(--space-6);
    border-top: 1px solid var(--color-border);
    background: var(--color-gray-50);
}
</style>

<script>
// Обработка навигации по секциям
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.section-nav-link');
    const sections = document.querySelectorAll('.card-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Обновить активный класс
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Обновить активный класс при скролле
    window.addEventListener('scroll', function() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (pageYOffset >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').substring(1) === current) {
                link.classList.add('active');
            }
        });
    });
});
</script>
