<?php
/** @var yii\web\View $this */
/** @var int|null $id */
$this->title = isset($id) ? 'Редактировать выкуп #' . $id : 'Новый выкуп';
?>
<div class="admin-page-header">
    <a href="/admin/buyout" class="admin-btn admin-btn-ghost admin-btn-sm flex-shrink-0">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h1 class="admin-page-title"><?= $this->title ?></h1>
</div>
<div class="admin-card" style="text-align:center;padding:3rem 2rem">
    <i class="bi bi-bag-plus" style="font-size:3rem;opacity:.25;display:block;margin-bottom:1rem"></i>
    <h3 style="margin:0 0 .5rem;font-size:1.125rem">Раздел в разработке</h3>
    <p style="color:var(--admin-text-secondary,#6d7175);margin:0">Форма создания выкупа будет доступна в ближайшем обновлении.</p>
</div>
