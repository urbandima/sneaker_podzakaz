<?php

use yii\helpers\Html;

$this->title = 'Справочник размерных сеток';
$this->params['breadcrumbs'][] = ['label' => 'Товары', 'url' => ['/admin/product/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="size-guide">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Справочник по международным размерным сеткам для обуви. Используйте эти данные для правильного заполнения размеров товаров.
    </div>

    <!-- Мужские размеры -->
    <div class="card mb-4">
        <div class="card-header" style="background: #0d6efd; color: white;">
            <h4 class="mb-0"><i class="bi bi-person"></i> Мужские размеры обуви</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead class="table-light">
                        <tr>
                            <th>US (США)</th>
                            <th>EU (Европа)</th>
                            <th>UK (Англия)</th>
                            <th>CM (Длина стопы)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>6</td><td>38.5</td><td>5.5</td><td>24.0</td></tr>
                        <tr><td>6.5</td><td>39</td><td>6</td><td>24.5</td></tr>
                        <tr><td>7</td><td>40</td><td>6</td><td>25.0</td></tr>
                        <tr><td>7.5</td><td>40.5</td><td>6.5</td><td>25.5</td></tr>
                        <tr><td>8</td><td>41</td><td>7</td><td>26.0</td></tr>
                        <tr><td>8.5</td><td>42</td><td>7.5</td><td>26.5</td></tr>
                        <tr><td>9</td><td>42.5</td><td>8</td><td>27.0</td></tr>
                        <tr><td>9.5</td><td>43</td><td>8.5</td><td>27.5</td></tr>
                        <tr><td>10</td><td>44</td><td>9</td><td>28.0</td></tr>
                        <tr><td>10.5</td><td>44.5</td><td>9.5</td><td>28.5</td></tr>
                        <tr><td>11</td><td>45</td><td>10</td><td>29.0</td></tr>
                        <tr><td>11.5</td><td>45.5</td><td>10.5</td><td>29.5</td></tr>
                        <tr><td>12</td><td>46</td><td>11</td><td>30.0</td></tr>
                        <tr><td>13</td><td>47.5</td><td>12</td><td>31.0</td></tr>
                        <tr><td>14</td><td>48.5</td><td>13</td><td>32.0</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Женские размеры -->
    <div class="card mb-4">
        <div class="card-header" style="background: #d63384; color: white;">
            <h4 class="mb-0"><i class="bi bi-person-dress"></i> Женские размеры обуви</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead class="table-light">
                        <tr>
                            <th>US (США)</th>
                            <th>EU (Европа)</th>
                            <th>UK (Англия)</th>
                            <th>CM (Длина стопы)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>5</td><td>35.5</td><td>2.5</td><td>22.0</td></tr>
                        <tr><td>5.5</td><td>36</td><td>3</td><td>22.5</td></tr>
                        <tr><td>6</td><td>36.5</td><td>3.5</td><td>23.0</td></tr>
                        <tr><td>6.5</td><td>37</td><td>4</td><td>23.5</td></tr>
                        <tr><td>7</td><td>37.5</td><td>4.5</td><td>24.0</td></tr>
                        <tr><td>7.5</td><td>38</td><td>5</td><td>24.5</td></tr>
                        <tr><td>8</td><td>38.5</td><td>5.5</td><td>25.0</td></tr>
                        <tr><td>8.5</td><td>39</td><td>6</td><td>25.5</td></tr>
                        <tr><td>9</td><td>40</td><td>6.5</td><td>26.0</td></tr>
                        <tr><td>9.5</td><td>40.5</td><td>7</td><td>26.5</td></tr>
                        <tr><td>10</td><td>41</td><td>7.5</td><td>27.0</td></tr>
                        <tr><td>11</td><td>42.5</td><td>8.5</td><td>28.0</td></tr>
                        <tr><td>12</td><td>44</td><td>9.5</td><td>29.0</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Как измерить стопу -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="bi bi-rulers"></i> Как правильно измерить длину стопы</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Инструкция для клиентов:</h5>
                    <ol>
                        <li>Встаньте на лист бумаги босиком</li>
                        <li>Обведите контур стопы карандашом, держа его вертикально</li>
                        <li>Измерьте расстояние от пятки до самого длинного пальца</li>
                        <li>Повторите для второй ноги (они могут отличаться!)</li>
                        <li>Используйте большее значение</li>
                        <li>Добавьте 0.5-1 см для комфорта</li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning">
                        <h5><i class="bi bi-exclamation-triangle"></i> Важно!</h5>
                        <ul class="mb-0">
                            <li>Измеряйте стопу вечером - она немного увеличивается к концу дня</li>
                            <li>Измеряйте в носках, если планируете носить обувь с носками</li>
                            <li>Для спортивной обуви часто берут на 0.5-1 размер больше</li>
                            <li>У разных брендов размеры могут немного отличаться</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Особенности размеров брендов -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <h4 class="mb-0"><i class="bi bi-bag"></i> Особенности размеров популярных брендов</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Nike</h5>
                    <p>Обычно маломерят на 0.5 размера. Рекомендуется брать на размер больше для кроссовок.</p>
                    
                    <h5>Adidas</h5>
                    <p>Размер в размер. Редко маломерят.</p>
                    
                    <h5>New Balance</h5>
                    <p>Часто маломерят. Берите на 0.5 размера больше.</p>
                </div>
                <div class="col-md-6">
                    <h5>Puma</h5>
                    <p>Размер в размер, иногда чуть узковаты.</p>
                    
                    <h5>Reebok</h5>
                    <p>Обычно размер в размер.</p>
                    
                    <h5>Converse</h5>
                    <p>Большемерят на 0.5-1 размер. Берите меньше обычного.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Детские размеры -->
    <div class="card mb-4">
        <div class="card-header" style="background: #20c997; color: white;">
            <h4 class="mb-0"><i class="bi bi-emoji-smile"></i> Детские размеры обуви</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead class="table-light">
                        <tr>
                            <th>US</th>
                            <th>EU</th>
                            <th>UK</th>
                            <th>CM</th>
                            <th>Возраст</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>10.5C</td><td>27</td><td>10</td><td>17.0</td><td>2-3 года</td></tr>
                        <tr><td>11C</td><td>28</td><td>10.5</td><td>17.5</td><td>3 года</td></tr>
                        <tr><td>11.5C</td><td>29</td><td>11</td><td>18.0</td><td>3-4 года</td></tr>
                        <tr><td>12C</td><td>30</td><td>11.5</td><td>18.5</td><td>4 года</td></tr>
                        <tr><td>12.5C</td><td>30.5</td><td>12</td><td>19.0</td><td>4-5 лет</td></tr>
                        <tr><td>13C</td><td>31</td><td>12.5</td><td>19.5</td><td>5 лет</td></tr>
                        <tr><td>13.5C</td><td>32</td><td>13</td><td>20.0</td><td>5-6 лет</td></tr>
                        <tr><td>1Y</td><td>32.5</td><td>13.5</td><td>20.5</td><td>6 лет</td></tr>
                        <tr><td>1.5Y</td><td>33</td><td>1</td><td>21.0</td><td>6-7 лет</td></tr>
                        <tr><td>2Y</td><td>33.5</td><td>1.5</td><td>21.5</td><td>7 лет</td></tr>
                        <tr><td>2.5Y</td><td>34</td><td>2</td><td>22.0</td><td>7-8 лет</td></tr>
                        <tr><td>3Y</td><td>35</td><td>2.5</td><td>22.5</td><td>8 лет</td></tr>
                    </tbody>
                </table>
            </div>
            <p class="text-muted"><small>* C - Child (детские), Y - Youth (подростковые)</small></p>
        </div>
    </div>

    <!-- Рекомендации -->
    <div class="card border-info">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><i class="bi bi-clipboard-check"></i> Рекомендации при заполнении размеров</h4>
        </div>
        <div class="card-body">
            <h5>При добавлении размеров товара:</h5>
            <ol>
                <li><strong>Заполняйте все системы</strong> - US, EU, UK, CM для удобства клиентов</li>
                <li><strong>Используйте таблицу соответствия</strong> - проверяйте правильность конвертации</li>
                <li><strong>Указывайте точные значения CM</strong> - это помогает клиентам выбрать размер</li>
                <li><strong>Отмечайте доступность</strong> - обновляйте статус при изменении остатков</li>
                <li><strong>Добавляйте примечания для бренда</strong> - если модель маломерит или большемерит</li>
            </ol>

            <div class="alert alert-success mt-3">
                <h5><i class="bi bi-check-circle"></i> Полезный совет</h5>
                <p class="mb-0">Для товаров из Poizon размеры обычно импортируются автоматически. Для ручных товаров используйте таблицы выше для корректного заполнения всех полей.</p>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="bi bi-arrow-left"></i> Вернуться к товарам', ['/admin/product/index'], ['class' => 'btn btn-secondary']) ?>
        <?= Html::a('<i class="bi bi-tags"></i> Справочник характеристик', ['/admin/characteristic/guide'], ['class' => 'btn btn-info']) ?>
        <?= Html::a('<i class="bi bi-gear"></i> Управление размерными сетками', ['/admin/size-grid/index'], ['class' => 'btn btn-primary']) ?>
    </div>
    
    <div class="alert alert-success mt-4">
        <h5><i class="bi bi-lightbulb"></i> Практическое применение</h5>
        <p class="mb-2"><strong>Для просмотра готовых сеток и управления ими:</strong></p>
        <p class="mb-0">
            Перейдите в раздел <strong>"Управление размерными сетками"</strong>, где вы можете создавать заготовленные 
            сетки для каждого бренда, а затем быстро добавлять все размеры к товару одним кликом.
        </p>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .table th {
        background-color: #e9ecef;
        font-weight: 600;
    }
</style>
