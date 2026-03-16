<?php

/** @var string $title */
/** @var string $icon */
/** @var string $description */

use yii\helpers\Html;

?>

<div class="admin-page">
    <div class="page-header">
        <?php if (!empty($icon)): ?>
            <div class="page-icon"><?= $icon ?></div>
        <?php endif; ?>
        <h1 class="page-title"><?= Html::encode($title) ?></h1>
        <?php if (!empty($description)): ?>
            <p class="page-description"><?= Html::encode($description) ?></p>
        <?php endif; ?>
    </div>
    
    <div class="page-content">
        <?= $content ?>
    </div>
</div>
