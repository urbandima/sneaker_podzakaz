<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Категории';
$this->params['breadcrumbs'][] = $this->title;

$categories = $dataProvider->getModels();
$pagination = $dataProvider->getPagination();
?>

<style>
.cat-thumb { width:40px; height:40px; object-fit:cover; border-radius:6px; cursor:pointer; transition:transform .15s; }
.cat-thumb:hover { transform:scale(1.1); }
.badge-no-photo { font-size:.7rem; padding:3px 7px; background:#f59e0b; color:#fff; border-radius:6px; cursor:pointer; white-space:nowrap; }
.upload-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; }
.upload-overlay.active { display:flex; }
.upload-box { background:#fff; border-radius:16px; padding:32px; width:360px; box-shadow:0 8px 32px rgba(0,0,0,.15); }
.upload-box h5 { margin:0 0 16px; font-weight:700; }
.upload-box .drop-zone {
    border:2px dashed #d1d5db; border-radius:10px; padding:32px 16px; text-align:center;
    color:#6b7280; cursor:pointer; transition:border-color .15s;
}
.upload-box .drop-zone:hover { border-color:#2563eb; color:#2563eb; }
.upload-box .drop-zone input { display:none; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="mb-1">Категории</h1>
        <span class="text-muted" style="font-size:.875rem">Всего: <?= $pagination->totalCount ?></span>
    </div>
    <?= Html::a('<i class="bi bi-plus-lg"></i> Создать категорию', ['/admin/category/create'], ['class' => 'btn btn-primary']) ?>
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
                    <th style="width:56px">Фото</th>
                    <th>Название</th>
                    <th>Slug</th>
                    <th>Родитель</th>
                    <th style="width:80px">Порядок</th>
                    <th style="width:80px">Статус</th>
                    <th style="width:80px">Товары</th>
                    <th style="width:140px">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <?php
                    $productCount = \app\backend\modules\catalog\models\Product::find()
                        ->where(['category_id' => $cat->id])
                        ->count();
                    ?>
                    <tr>
                        <td class="text-muted" style="font-size:.8rem"><?= $cat->id ?></td>
                        <td>
                            <?php if ($cat->image): ?>
                                <img src="<?= Html::encode($cat->image) ?>"
                                     class="cat-thumb"
                                     title="Нажмите для смены фото"
                                     onclick="openUpload(<?= $cat->id ?>, '<?= Html::encode($cat->name) ?>')"
                                     onerror="this.replaceWith(makeNoBadge(<?= $cat->id ?>, '<?= Html::encode($cat->name) ?>'))">
                            <?php else: ?>
                                <span class="badge-no-photo"
                                      onclick="openUpload(<?= $cat->id ?>, '<?= Html::encode($cat->name) ?>')">
                                    Без фото
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= Html::encode($cat->name) ?></strong>
                            <?php if ($cat->description): ?>
                                <br><small class="text-muted"><?= Html::encode(mb_substr($cat->description, 0, 60)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size:.8rem"><?= Html::encode($cat->slug) ?></code></td>
                        <td>
                            <?php if ($cat->parent_id): ?>
                                <?= Html::encode($cat->parent ? $cat->parent->name : $cat->parent_id) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $cat->sort_order ?></td>
                        <td>
                            <?php if ($cat->is_active): ?>
                                <span class="badge bg-success">Активна</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Неактивна</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark"><?= $productCount ?></span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <?= Html::a('<i class="bi bi-pencil"></i>', ['/admin/category/'.$cat->id.'/edit'], ['class' => 'btn btn-outline-primary', 'title' => 'Редактировать']) ?>
                                <?= Html::a('<i class="bi bi-trash"></i>', ['/admin/category/'.$cat->id.'/delete'],
                                    ['class' => 'btn btn-outline-danger', 'title' => 'Удалить',
                                     'data-confirm' => 'Удалить категорию «'.$cat->name.'»?',
                                     'data-method' => 'post']) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Категории не найдены</td></tr>
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
            <h5 id="uploadTitle">Загрузить фото</h5>
            <button type="button" class="btn-close" onclick="closeUpload()"></button>
        </div>
        <form id="uploadForm" method="post" enctype="multipart/form-data">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <div class="drop-zone" onclick="document.getElementById('photoFile').click()">
                <i class="bi bi-cloud-upload" style="font-size:2rem"></i>
                <p class="mt-2 mb-0">Нажмите или перетащите файл<br><small>JPG, PNG, WebP до 5 МБ</small></p>
                <input type="file" id="photoFile" name="image" accept="image/*" onchange="submitUpload(this)">
            </div>
            <div id="uploadStatus" class="mt-2 text-center" style="display:none"></div>
        </form>
    </div>
</div>

<script>
var currentUploadId = null;
function makeNoBadge(id, name) {
    var s = document.createElement('span');
    s.className = 'badge-no-photo';
    s.textContent = 'Без фото';
    s.onclick = function() { openUpload(id, name); };
    return s;
}
function openUpload(id, name) {
    currentUploadId = id;
    document.getElementById('uploadTitle').textContent = 'Фото: ' + name;
    document.getElementById('uploadStatus').style.display = 'none';
    document.getElementById('uploadOverlay').classList.add('active');
}
function closeUpload() {
    document.getElementById('uploadOverlay').classList.remove('active');
    currentUploadId = null;
}
function submitUpload(input) {
    if (!input.files || !input.files[0]) return;
    var status = document.getElementById('uploadStatus');
    status.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split"></i> Загрузка...</span>';
    status.style.display = 'block';

    var fd = new FormData();
    fd.append('image', input.files[0]);
    fd.append('<?= Yii::$app->request->csrfParam ?>', '<?= Yii::$app->request->csrfToken ?>');

    fetch('/admin/category/' + currentUploadId + '/upload-image', {
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
