<?php
/**
 * Общий компонент «пустое состояние».
 * Использование:
 *   echo $this->render('/partials/_empty_state', [
 *       'icon'    => 'bi-bag-x',
 *       'title'   => 'Заказов пока нет',
 *       'message' => 'Перейдите в каталог…',
 *       'actionUrl'   => '/catalog',
 *       'actionLabel' => 'Перейти в каталог',
 *   ]);
 *
 * @var string $icon        — Bootstrap-icon класс (например 'bi-bag-x')
 * @var string $title       — Заголовок
 * @var string $message     — Описание (optional)
 * @var string $actionUrl   — URL кнопки (optional)
 * @var string $actionLabel — Текст кнопки (optional)
 * @var string $cssClass    — Дополнительный CSS-класс (optional)
 */

$icon = $icon ?? 'bi-emoji-frown';
$title = $title ?? '';
$message = $message ?? '';
$actionUrl = $actionUrl ?? '';
$actionLabel = $actionLabel ?? '';
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
</div>
