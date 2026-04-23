<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Управление тарифами';

$calculationHistory = $calculationHistory ?? [];
?>

<div class="tariff-page">
    <div class="tariff-shell">
        <!-- Hero Section -->
        <div class="tariff-hero">
            <div>
                <h1><i class="bi bi-calculator-fill"></i> <?= Html::encode($this->title) ?></h1>
                <p>Управляйте тарифами и комиссиями для расчета стоимости заказов</p>
            </div>
            <a href="<?= Url::to(['create']) ?>" class="btn-action primary">
                <i class="bi bi-plus-lg"></i> Добавить тариф
            </a>
        </div>

        <!-- Tariff Cards Grid -->
        <div class="tariff-grid">
            <?php foreach ($tariffs as $tariff): ?>
                <div class="tariff-card <?= $tariff->is_active ? '' : 'inactive' ?>">
                    <div class="tariff-header">
                        <div class="tariff-name"><?= Html::encode($tariff->name) ?></div>
                        <span class="tariff-badge <?= $tariff->is_active ? 'active' : 'inactive' ?>">
                            <?= $tariff->is_active ? 'Активен' : 'Неактивен' ?>
                        </span>
                    </div>
                    
                    <?php if ($tariff->description): ?>
                        <div class="tariff-description"><?= Html::encode($tariff->description) ?></div>
                    <?php endif; ?>
                    
                    <div class="tariff-details">
                        <div class="tariff-detail">
                            <span class="detail-label">Комиссия</span>
                            <span class="detail-value"><?= $tariff->commission_percent ?>%</span>
                        </div>
                        <div class="tariff-detail">
                            <span class="detail-label">Доставка/кг</span>
                            <span class="detail-value"><?= $tariff->delivery_cost_per_kg ?> ¥</span>
                        </div>
                        <div class="tariff-detail">
                            <span class="detail-label">Страховка</span>
                            <span class="detail-value"><?= $tariff->insurance_percent ?>%</span>
                        </div>
                        <div class="tariff-detail">
                            <span class="detail-label">Курс</span>
                            <span class="detail-value"><?= $tariff->exchange_rate ?></span>
                        </div>
                    </div>
                    
                    <div class="tariff-actions">
                        <a href="<?= Url::to(['update', 'id' => $tariff->id]) ?>" class="btn-action">
                            <i class="bi bi-pencil"></i> Изменить
                        </a>
                        <a href="<?= Url::to(['toggle', 'id' => $tariff->id]) ?>" class="btn-action">
                            <i class="bi bi-<?= $tariff->is_active ? 'pause' : 'play' ?>"></i>
                            <?= $tariff->is_active ? 'Деактивировать' : 'Активировать' ?>
                        </a>
                        <?= Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $tariff->id], [
                            'class' => 'btn-action danger',
                            'data' => ['method' => 'post', 'confirm' => 'Удалить тариф?'],
                        ]) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Calculator Section -->
        <div class="calculator-section">
            <div class="calculator-header">
                <h3 class="calculator-title"><i class="bi bi-calculator"></i> Калькулятор стоимости заказа</h3>
            </div>
            
            <div class="calculator-form">
                <div class="form-group">
                    <label>Тариф</label>
                    <select id="calcTariff">
                        <?php foreach ($tariffs as $tariff): ?>
                            <?php if ($tariff->is_active): ?>
                                <option value="<?= $tariff->id ?>"><?= Html::encode($tariff->name) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Цена товара (¥)</label>
                    <input type="number" id="calcPrice" value="500" step="0.01">
                </div>
                <div class="form-group">
                    <label>Вес (кг)</label>
                    <input type="number" id="calcWeight" value="0.5" step="0.1">
                </div>
                <div class="form-group">
                    <label>Примечание</label>
                    <input type="text" id="calcNote" placeholder="Опционально">
                </div>
                <div class="form-group" style="align-self: end;">
                    <button type="button" class="btn-action primary" onclick="calculateCost()">
                        <i class="bi bi-calculator"></i> Рассчитать
                    </button>
                </div>
            </div>
            
            <div class="calculation-result" id="calcResult">
                <div id="calcBreakdown"></div>
            </div>
        </div>

        <!-- Calculation History Section -->
        <div class="history-section">
            <div class="history-header">
                <h3 class="history-title"><i class="bi bi-clock-history"></i> История расчетов</h3>
                <span style="font-size: 0.875rem; color: var(--admin-text-muted, #94a3b8);">
                    Последние 30 расчетов
                </span>
            </div>
            
            <?php if (!empty($calculationHistory)): ?>
                <div class="history-table-wrapper">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Тариф</th>
                                <th>Цена (¥)</th>
                                <th>Вес (кг)</th>
                                <th>Комиссия</th>
                                <th>Доставка</th>
                                <th>Итого (¥)</th>
                                <th>Курс</th>
                                <th>Итого (BYN)</th>
                                <th>Примечание</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calculationHistory as $calc): ?>
                                <tr>
                                    <td><?= Yii::$app->formatter->asDatetime($calc->created_at, 'short') ?></td>
                                    <td><strong><?= Html::encode($calc->tariff_name) ?></strong></td>
                                    <td><?= number_format($calc->price_cny, 2) ?></td>
                                    <td><?= number_format($calc->weight_kg, 2) ?></td>
                                    <td><?= number_format($calc->commission_cny, 2) ?> ¥</td>
                                    <td><?= number_format($calc->delivery_cost_cny, 2) ?> ¥</td>
                                    <td><strong><?= number_format($calc->total_cny, 2) ?> ¥</strong></td>
                                    <td><?= $calc->exchange_rate ?></td>
                                    <td style="color: var(--admin-success, #10b981); font-weight: 700;">
                                        <?= number_format($calc->total_local, 2) ?> BYN
                                    </td>
                                    <td>
                                        <?php if ($calc->note): ?>
                                            <span title="<?= Html::encode($calc->note) ?>">
                                                <?= Html::encode(mb_substr($calc->note, 0, 20)) ?><?= mb_strlen($calc->note) > 20 ? '...' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-muted, #94a3b8);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="history-empty">
                    <i class="bi bi-clock-history"></i>
                    <p>История расчетов пуста</p>
                    <p class="fs-sm">Выполните расчет в калькуляторе выше, чтобы он появился здесь</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="tariff-calc-config" class="d-none"
    data-calc-url="<?= Url::to(['calculate']) ?>"
    data-csrf-token="<?= Yii::$app->request->csrfToken ?>">
</div>
