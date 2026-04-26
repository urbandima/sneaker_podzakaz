<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Бренды';
$this->params['breadcrumbs'][] = $this->title;

$brands = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
?>

<style>
.brand-thumb { width:40px; height:40px; object-fit:contain; border-radius:4px; cursor:pointer; background:#f8fafc; padding:2px; border:1px solid #e5e7eb; transition:transform .15s; }
.brand-thumb:hover { transform:scale(1.1); }
.badge-no-logo { font-size:.7rem; padding:3px 7px; background:#f59e0b; color:#fff; border-radius:6px; cursor:pointer; white-space:nowrap; }
.upload-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; }
.upload-overlay.active { display:flex; }
.upload-box { background:#fff; border-radius:16px; padding:32px; width:360px; box-shadow:0 8px 32px rgba(0,0,0,.15); }
.upload-box h5 { margin:0 0 16px; font-weight:700; }
.drop-zone { border:2px dashed #d1d5db; border-radius:10px; padding:32px 16px; text-align:center; color:#6b7280; cursor:pointer; transition:border-color .15s; }
.drop-zone:hover { border-color:#2563eb; color:#2563eb; }
.drop-zone input { display:none; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Бренды</h1>
        <span class="text-muted" style="font-size:.875rem">Всего: <?= $pagination->totalCount ?></span>
    </div>
    <?= Html::a('<i class="bi bi-plus-lg"></i> Создать бренд', ['/admin/brand/create'], ['class' => 'btn btn-primary']) ?>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible">
        <?= Html::encode(Yii::$app->session->getFlash('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible">
        <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:50px">ID</th>
                    <th style="width:56px">Лого</th>
                    <th>Название</th>
                    <th>Slug</th>
                    <th style="width:80px">Порядок</th>
                    <th style="width:80px">Статус</th>
                    <th style="width:80px">Товары</th>
                    <th style="width:140px">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($brands as $brand): ?>
                    <?php
                    $productCount = \app\backend\modules\catalog\models\Product::find()
                        ->where(['brand_id' => $brand->id])
                        ->count();
                    $logoSrc = $brand->logo ?: ($brand->logo_url ?: null);
                    ?>
                    <tr>
                        <td class="text-muted" style="font-size:.8rem"><?= $brand->id ?></td>
                        <td>
                            <?php if ($logoSrc): ?>
                                <img src="<?= Html::encode($logoSrc) ?>"
                                     class="brand-thumb"
                                     title="Нажмите для смены логотипа"
                                     onclick="openUpload(<?= $brand->id ?>, '<?= Html::encode($brand->name) ?>')"
                                     onerror="this.replaceWith(makeNoBadge(<?= $brand->id ?>, '<?= Html::encode($brand->name) ?>'))">
                            <?php else: ?>
                                <span class="badge-no-logo"
                                      onclick="openUpload(<?= $brand->id ?>, '<?= Html::encode($brand->name) ?>')">
                                    Без лого
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= Html::encode($brand->name) ?></strong>
                            <?php if ($brand->description): ?>
                                <br><small class="text-muted"><?= Html::encode(mb_substr($brand->description, 0, 60)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size:.8rem"><?= Html::encode($brand->slug) ?></code></td>
                        <td class="text-center"><?= $brand->sort_order ?></td>
                        <td>
                            <?php if ($brand->is_active): ?>
                                <span class="badge bg-success">Активен</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark"><?= $productCount ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?= Html::a('<i class="bi bi-pencil"></i>', ['/admin/brand/'.$brand->id.'/edit'], ['class' => 'btn btn-outline-primary', 'title' => 'Редактировать']) ?>
                                <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/brand/'.$brand->id.'/delete'],
                                    ['class' => 'btn btn-outline-danger', 'title' => 'Удалить',
                                     'data-confirm' => 'Удалить бренд «'.$brand->name.'»?',
                                     'data-method' => 'post']) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($brands)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Бренды не найдены</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pagination->pageCount > 1): ?>
<div class="mt-3">
    <?= \yii\widgets\LinkPager::widget(['pagination' => $pagination]) ?>
</div>
<?php endif; ?>

<!-- Quick Upload Overlay -->
<div class="upload-overlay" id="uploadOverlay">
    <div class="upload-box">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 id="uploadTitle">Загрузить логотип</h5>
            <button type="button" class="btn-close" onclick="closeUpload()"></button>
        </div>
        <div class="drop-zone" onclick="document.getElementById('logoFile').click()">
            <i class="bi bi-cloud-upload" style="font-size:2rem"></i>
            <p class="mt-2 mb-0">Нажмите или перетащите файл<br><small>PNG с прозрачностью, ~300×150 px</small></p>
            <input type="file" id="logoFile" name="logo" accept="image/*" onchange="submitLogoUpload(this)">
        </div>
        <div id="uploadStatus" class="mt-2 text-center" style="display:none"></div>
    </div>
</div>

<script>
var currentUploadId = null;
function makeNoBadge(id, name) {
    var s = document.createElement('span');
    s.className = 'badge-no-logo';
    s.textContent = 'Без лого';
    s.onclick = function() { openUpload(id, name); };
    return s;
}
function openUpload(id, name) {
    currentUploadId = id;
    document.getElementById('uploadTitle').textContent = 'Логотип: ' + name;
    document.getElementById('uploadStatus').style.display = 'none';
    document.getElementById('uploadOverlay').classList.add('active');
}
function closeUpload() {
    document.getElementById('uploadOverlay').classList.remove('active');
    currentUploadId = null;
}
function submitLogoUpload(input) {
    if (!input.files || !input.files[0]) return;
    var status = document.getElementById('uploadStatus');
    status.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split"></i> Загрузка...</span>';
    status.style.display = 'block';

    var fd = new FormData();
    fd.append('logo', input.files[0]);
    fd.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/admin/brand/' + currentUploadId + '/upload-logo', {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Загружено!</span>';
            setTimeout(function() { location.reload(); }, 800);
        } else {
            status.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> ' + (data.message || 'Ошибка') + '</span>';
        }
    })
    .catch(function() {
        status.innerHTML = '<span class="text-danger">Ошибка соединения</span>';
    });
}
document.getElementById('uploadOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeUpload();
});
</script>
