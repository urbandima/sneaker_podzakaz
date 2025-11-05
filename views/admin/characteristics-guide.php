<?php

use yii\helpers\Html;

$this->title = 'Справочник характеристик товаров';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['products']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="characteristics-guide">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Этот справочник поможет вам правильно заполнить характеристики товаров для корректной фильтрации в каталоге.
    </div>

    <!-- Материалы -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-tags"></i> Материалы (Material)</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="200">Значение</th>
                        <th>Описание</th>
                        <th>Примеры</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>leather</code></td>
                        <td>Натуральная кожа</td>
                        <td>Классические туфли, ботинки премиум-сегмента</td>
                    </tr>
                    <tr>
                        <td><code>textile</code></td>
                        <td>Текстиль</td>
                        <td>Кроссовки, легкая обувь, летние модели</td>
                    </tr>
                    <tr>
                        <td><code>synthetic</code></td>
                        <td>Синтетические материалы</td>
                        <td>Спортивная обувь, экокожа</td>
                    </tr>
                    <tr>
                        <td><code>suede</code></td>
                        <td>Замша</td>
                        <td>Стильные кроссовки, ботинки</td>
                    </tr>
                    <tr>
                        <td><code>mesh</code></td>
                        <td>Сетка</td>
                        <td>Беговые кроссовки, дышащие модели</td>
                    </tr>
                    <tr>
                        <td><code>canvas</code></td>
                        <td>Холст</td>
                        <td>Кеды, повседневная обувь</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Сезонность -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-calendar3"></i> Сезонность (Season)</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="200">Значение</th>
                        <th>Описание</th>
                        <th>Характеристики</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>summer</code></td>
                        <td>Лето</td>
                        <td>Легкие, дышащие материалы, открытые модели</td>
                    </tr>
                    <tr>
                        <td><code>winter</code></td>
                        <td>Зима</td>
                        <td>Утепленные, с мехом, водонепроницаемые</td>
                    </tr>
                    <tr>
                        <td><code>demi</code></td>
                        <td>Демисезон (весна/осень)</td>
                        <td>Универсальные, средняя защита от влаги</td>
                    </tr>
                    <tr>
                        <td><code>all</code></td>
                        <td>Всесезонные</td>
                        <td>Подходят для круглогодичного ношения</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Пол -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><i class="bi bi-gender-ambiguous"></i> Пол (Gender)</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="200">Значение</th>
                        <th>Описание</th>
                        <th>Примечания</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>male</code></td>
                        <td>Мужские</td>
                        <td>Размеры и дизайн для мужчин</td>
                    </tr>
                    <tr>
                        <td><code>female</code></td>
                        <td>Женские</td>
                        <td>Размеры и дизайн для женщин</td>
                    </tr>
                    <tr>
                        <td><code>unisex</code></td>
                        <td>Унисекс</td>
                        <td>Подходят и мужчинам, и женщинам</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Дополнительные характеристики -->
    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h4 class="mb-0"><i class="bi bi-list-check"></i> Дополнительные характеристики</h4>
        </div>
        <div class="card-body">
            <h5>Материал верха (Upper Material)</h5>
            <p class="text-muted">Детальное описание материала верхней части обуви. Примеры:</p>
            <ul>
                <li><strong>Кожа</strong> - натуральная кожа премиум качества</li>
                <li><strong>Замша</strong> - мягкая замша высокого качества</li>
                <li><strong>Текстиль</strong> - дышащий текстильный материал</li>
                <li><strong>Сетка + синтетика</strong> - комбинированные материалы</li>
            </ul>

            <h5 class="mt-4">Материал подошвы (Sole Material)</h5>
            <p class="text-muted">Тип подошвы. Примеры:</p>
            <ul>
                <li><strong>Резина</strong> - классическая резиновая подошва</li>
                <li><strong>EVA</strong> - легкая и амортизирующая</li>
                <li><strong>Полиуретан</strong> - прочная и износостойкая</li>
                <li><strong>Гума</strong> - натуральная резина</li>
            </ul>

            <h5 class="mt-4">Страна производства (Country of Origin)</h5>
            <p class="text-muted">Указывайте страну-производителя:</p>
            <ul>
                <li>Вьетнам</li>
                <li>Китай</li>
                <li>Индонезия</li>
                <li>Италия</li>
                <li>США</li>
            </ul>
        </div>
    </div>

    <!-- Рекомендации -->
    <div class="card border-warning">
        <div class="card-header bg-warning">
            <h4 class="mb-0"><i class="bi bi-lightbulb"></i> Рекомендации по заполнению</h4>
        </div>
        <div class="card-body">
            <ol>
                <li><strong>Используйте точные значения</strong> - выбирайте из предложенных вариантов для корректной фильтрации</li>
                <li><strong>Заполняйте все доступные поля</strong> - чем больше информации, тем лучше для клиентов</li>
                <li><strong>Проверяйте соответствие</strong> - убедитесь, что характеристики соответствуют реальному товару</li>
                <li><strong>Консистентность</strong> - используйте одинаковые обозначения для похожих товаров</li>
                <li><strong>SEO важно</strong> - заполненные характеристики улучшают поисковую оптимизацию</li>
            </ol>
        </div>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="bi bi-arrow-left"></i> Вернуться к товарам', ['products'], ['class' => 'btn btn-secondary']) ?>
        <?= Html::a('<i class="bi bi-rulers"></i> Справочник размерных сеток', ['size-guide'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('<i class="bi bi-gear"></i> Управление размерными сетками', ['size-grids'], ['class' => 'btn btn-primary']) ?>
    </div>
    
    <div class="alert alert-success mt-4">
        <h5><i class="bi bi-check-circle"></i> Как использовать справочник</h5>
        <p class="mb-0">
            Используйте эти значения при заполнении характеристик товаров. Для редактирования самих товаров 
            перейдите в <strong>Товары → Список товаров</strong> и выберите нужный товар.
        </p>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .table code {
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        color: #e83e8c;
        font-weight: 600;
    }
</style>
