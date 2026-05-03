<?php
/**
 * Общий компонент «пустое состояние».
 *
 * @var string $icon           — Bootstrap-icon класс (например 'bi-bag-x')
 * @var string $title          — Заголовок
 * @var string $message        — Описание (optional)
 * @var string $actionUrl      — URL основной кнопки (optional)
 * @var string $actionLabel    — Текст основной кнопки (optional)
 * @var string $secondaryUrl   — URL вторичной ссылки (optional)
 * @var string $secondaryLabel — Текст вторичной ссылки (optional)
 * @var string $cssClass       — Дополнительный CSS-класс (optional)
 */

$icon = $icon ?? 'bi-emoji-frown';
$title = $title ?? '';
$message = $message ?? '';
$actionUrl = $actionUrl ?? '';
$actionLabel = $actionLabel ?? '';
$secondaryUrl = $secondaryUrl ?? '';
$secondaryLabel = $secondaryLabel ?? '';
$cssClass = $cssClass ?? '';
?>
<div class="empty-state <?= $cssClass ?>">
    <i class="bi <?= $icon ?>"></i>
    <?php if ($title): ?>
        <h3><?= $title ?></h3>
    <?php endif; ?>
    <?php if ($message): ?>
        <p><?= $message ?></p>
    <?php endif; ?>
    <?php if ($actionUrl && $actionLabel): ?>
        <a href="<?= $actionUrl ?>" class="btn btn-primary"><?= $actionLabel ?></a>
    <?php endif; ?>
    <?php if ($secondaryUrl && $secondaryLabel): ?>
        <a href="<?= $secondaryUrl ?>" class="empty-state__secondary"><?= $secondaryLabel ?></a>
    <?php endif; ?>
</div>
