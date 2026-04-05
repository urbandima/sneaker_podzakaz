<?php

/**
 * Loyalty Section - Секция программы лояльности в личном кабинете
 * 
 * Отображает:
 * - Карточку уровня клиента
 * - Прогресс до следующего уровня
 * - Преимущества текущего уровня
 * - Историю баллов
 */

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $loyaltyInfo */

$level = $loyaltyInfo['level'] ?? null;
$balance = $loyaltyInfo['balance'] ?? 0;
$nextLevel = $loyaltyInfo['nextLevel'] ?? null;
$pointsToNext = $loyaltyInfo['pointsToNextLevel'] ?? 0;
$history = $loyaltyInfo['history'] ?? [];

$levelColors = [
    'bronze' => ['#CD7F32', 'Бронза'],
    'silver' => ['#C0C0C0', 'Серебро'],
    'gold' => ['#FFD700', 'Золото'],
    'platinum' => ['#E5E4E2', 'Платина'],
];

$levelInfo = $levelColors[$level->level ?? 'bronze'] ?? $levelColors['bronze'];
?>

<div class="loyalty-section">
    <div class="section-header">
        <h2>Программа лояльности</h2>
    </div>
    
    <!-- Loyalty Card -->
    <div class="loyalty-card" data-level="<?= strtolower($levelInfo[1] ?? 'bronze') ?>" style="--level-color: <?= $levelInfo[0] ?>">
        <div class="card-header">
            <div class="level-badge">
                <div class="badge-icon">
                    <i class="bi bi-gem"></i>
                </div>
                <div class="badge-info">
                    <span class="level-name"><?= $levelInfo[1] ?></span>
                    <span class="level-status">Текущий уровень</span>
                </div>
            </div>
            <div class="points-display">
                <div class="points-value"><?= number_format($balance, 0, '', ' ') ?></div>
                <div class="points-label">баллов</div>
            </div>
        </div>
        
        <?php if ($nextLevel): ?>
        <div class="card-progress">
            <div class="progress-header">
                <span>До уровня "<?= $nextLevel->name ?>"</span>
                <span><?= number_format($pointsToNext, 0, '', ' ') ?> баллов</span>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="--progress-width: <?= ($balance / $nextLevel->min_points) * 100 ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="card-benefits">
            <h4>Ваши преимущества:</h4>
            <ul class="benefits-list">
                <?php if ($level): ?>
                    <?php if ($level->points_multiplier > 1): ?>
                    <li>
                        <i class="bi bi-check-circle-fill"></i>
                        <span>+<?= ($level->points_multiplier - 1) * 100 ?>% к начислению баллов</span>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($level->discount_percent > 0): ?>
                    <li>
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Скидка <?= $level->discount_percent ?>% на все покупки</span>
                    </li>
                    <?php endif; ?>
                    
                    <?php foreach ($level->getBenefitsList() as $benefit): ?>
                    <li>
                        <i class="bi bi-check-circle-fill"></i>
                        <span><?= Html::encode($benefit) ?></span>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>
                        <i class="bi bi-check-circle-fill"></i>
                        <span>Базовое начисление баллов за покупки</span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="card-info">
            <div class="info-item">
                <i class="bi bi-info-circle"></i>
                <span>1 балл = 0.01 BYN. Минимум для списания: 100 баллов</span>
            </div>
        </div>
    </div>
    
    <!-- How to Earn -->
    <div class="earn-section">
        <h3>Как заработать баллы</h3>
        <div class="earn-grid">
            <div class="earn-item">
                <div class="earn-icon"><i class="bi bi-cart"></i></div>
                <div class="earn-info">
                    <div class="earn-title">Покупки</div>
                    <div class="earn-value">10 баллов за 1 BYN</div>
                </div>
            </div>
            <div class="earn-item">
                <div class="earn-icon"><i class="bi bi-pencil-square"></i></div>
                <div class="earn-info">
                    <div class="earn-title">Отзыв о товаре</div>
                    <div class="earn-value">+50 баллов</div>
                </div>
            </div>
            <div class="earn-item">
                <div class="earn-icon"><i class="bi bi-people"></i></div>
                <div class="earn-info">
                    <div class="earn-title">Приглашение друга</div>
                    <div class="earn-value">+200 баллов</div>
                </div>
            </div>
            <div class="earn-item">
                <div class="earn-icon"><i class="bi bi-gift"></i></div>
                <div class="earn-info">
                    <div class="earn-title">Регистрация</div>
                    <div class="earn-value">+100 баллов</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Points History -->
    <div class="history-section">
        <h3>История баллов</h3>
        
        <?php if (empty($history)): ?>
            <div class="empty-history">
                <i class="bi bi-clock-history"></i>
                <p>История баллов пуста</p>
            </div>
        <?php else: ?>
            <div class="history-table">
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Баллы</th>
                            <th>Баланс</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                        <tr>
                            <td><?= Yii::$app->formatter->asDate($item->created_at, 'dd.MM.yyyy') ?></td>
                            <td>
                                <span class="operation-type type-<?= $item->type ?>">
                                    <?= $item->description ?: $item->getTypeName() ?>
                                </span>
                            </td>
                            <td class="<?= $item->points > 0 ? 'points-positive' : 'points-negative' ?>">
                                <?= $item->points > 0 ? '+' : '' ?><?= $item->points ?>
                            </td>
                            <td><?= $item->balance ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.loyalty-section {
    padding: 2rem 0;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-header h2 i {
    color: #f59e0b;
}

/* Loyalty Card */
.loyalty-card {
    background: linear-gradient(135deg, var(--level-color) 0%, #1e293b 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    margin-bottom: 2rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.level-badge {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.badge-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.badge-info {
    display: flex;
    flex-direction: column;
}

.level-name {
    font-size: 1.5rem;
    font-weight: 700;
}

.level-status {
    font-size: 0.875rem;
    opacity: 0.8;
}

.points-display {
    text-align: right;
}

.points-value {
    font-size: 2.5rem;
    font-weight: 800;
}

.points-label {
    font-size: 0.875rem;
    opacity: 0.8;
}

.card-progress {
    margin-bottom: 1.5rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    opacity: 0.9;
}

.progress-bar-container {
    height: 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: white;
    border-radius: 4px;
    transition: width 1s ease;
}

.card-benefits h4 {
    font-size: 1rem;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.benefits-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.benefits-list i {
    color: #10b981;
}

.card-info {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Earn Section */
.earn-section {
    margin-bottom: 2rem;
}

.earn-section h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.earn-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.earn-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.earn-icon {
    font-size: 2rem;
}

.earn-title {
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 0.25rem;
}

.earn-value {
    font-size: 0.875rem;
    color: #10b981;
    font-weight: 600;
}

/* History Section */
.history-section h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.empty-history {
    text-align: center;
    padding: 3rem;
    background: #f8fafc;
    border-radius: 12px;
    color: #64748b;
}

.empty-history i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

.history-table table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.history-table th,
.history-table td {
    padding: 1rem;
    text-align: left;
}

.history-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #64748b;
    font-size: 0.875rem;
}

.history-table tr:not(:last-child) td {
    border-bottom: 1px solid #f1f5f9;
}

.operation-type {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 100px;
    font-size: 0.8125rem;
    font-weight: 500;
}

.type-purchase { background: #dbeafe; color: #1e40af; }
.type-redeem { background: #fef3c7; color: #92400e; }
.type-bonus { background: #d1fae5; color: #065f46; }
.type-referral { background: #ede9fe; color: #5b21b6; }
.type-signup { background: #fce7f3; color: #9d174d; }
.type-review { background: #e0e7ff; color: #3730a3; }

.points-positive {
    color: #10b981;
    font-weight: 600;
}

.points-negative {
    color: #ef4444;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .earn-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .points-display {
        text-align: left;
    }
}
</style>
