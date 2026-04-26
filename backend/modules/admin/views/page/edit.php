<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/**
 * @var \yii\web\View $this
 * @var \app\backend\modules\admin\models\PageContent $model
 * @var array  $revisions   last 10 revisions
 * @var string $frontendUrl public URL for this page
 */

$this->title = 'Редактировать: ' . $model->title;

// Pass variables to JS
$pageId    = $model->isNewRecord ? 0 : (int)$model->id;
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$autosaveUrl      = Url::to(['/admin/page/autosave']);
$uploadImageUrl   = Url::to(['/admin/page/upload-image']);
$restoreUrl       = Url::to(['/admin/page/restore-revision']);

// Detect frontend CSS path
$frontendCssPath = '/css/app.css';
if (file_exists(Yii::getAlias('@frontend/web/css/app.css'))) {
    $frontendCssPath = '/css/app.css';
}
?>
<style>
/* ── Split-view layout ─────────────────────────────────────────── */
.pe-shell {
    display: flex;
    height: 600px; /* fallback, overridden by JS */
    overflow: hidden;
    margin: 0 -1.5rem -1.5rem; /* bleed left/right/bottom to admin-main edges */
}
.pe-left {
    width: 50%;
    min-width: 320px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-right: 1px solid var(--admin-border, #e5e7eb);
    background: var(--admin-bg, #fff);
}
.pe-divider {
    width: 5px;
    background: var(--admin-border, #e5e7eb);
    cursor: col-resize;
    flex-shrink: 0;
    transition: background .15s;
}
.pe-divider:hover, .pe-divider.active { background: var(--admin-primary, #2563eb); }
.pe-right {
    flex: 1;
    overflow: hidden;
    background: #f5f5f5;
    display: flex;
    flex-direction: column;
}
/* ── Top bar ────────────────────────────────────────────────────── */
.pe-topbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    flex-shrink: 0;
    background: var(--admin-surface-2, #f8f9fa);
    flex-wrap: wrap;
}
.pe-breadcrumb {
    font-size: 13px;
    color: var(--admin-text-secondary, #6b7280);
    flex: 1;
    min-width: 120px;
}
.pe-breadcrumb a { color: inherit; text-decoration: none; }
.pe-breadcrumb a:hover { text-decoration: underline; }
/* ── Title input ────────────────────────────────────────────────── */
.pe-title-wrap {
    padding: 12px 16px 0;
    flex-shrink: 0;
}
.pe-title-input {
    width: 100%;
    font-size: 22px;
    font-weight: 700;
    border: none;
    border-bottom: 2px solid transparent;
    outline: none;
    padding: 4px 0;
    background: transparent;
    transition: border-color .15s;
    color: var(--admin-text-primary, #111);
}
.pe-title-input:focus { border-bottom-color: var(--admin-primary, #2563eb); }
/* ── Editor area ────────────────────────────────────────────────── */
.pe-editor-wrap {
    flex: 1;
    overflow: hidden;
    padding: 8px 16px 0;
    display: flex;
    flex-direction: column;
}
/* TinyMCE container fills remaining height */
.tox-tinymce { border-radius: 8px !important; }
/* ── Accordion sections ─────────────────────────────────────────── */
.pe-accordion {
    flex-shrink: 0;
    border-top: 1px solid var(--admin-border, #e5e7eb);
}
.pe-accordion-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    cursor: pointer;
    user-select: none;
    font-size: 13px;
    font-weight: 600;
    color: var(--admin-text-secondary, #6b7280);
    background: var(--admin-surface-2, #f8f9fa);
}
.pe-accordion-header:hover { background: var(--admin-surface-3, #f0f1f3); }
.pe-accordion-arrow { margin-left: auto; transition: transform .2s; }
.pe-accordion-body {
    padding: 12px 16px 14px;
    display: none;
    background: var(--admin-bg, #fff);
}
.pe-accordion-body.open { display: block; }
.pe-accordion-header.open .pe-accordion-arrow { transform: rotate(180deg); }
/* ── Form fields ────────────────────────────────────────────────── */
.pe-field { margin-bottom: 10px; }
.pe-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--admin-text-secondary, #6b7280);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.pe-field input[type=text],
.pe-field textarea,
.pe-field select {
    width: 100%;
    padding: 7px 10px;
    font-size: 13px;
    border: 1px solid var(--admin-border, #e5e7eb);
    border-radius: 6px;
    background: var(--admin-bg, #fff);
    color: var(--admin-text-primary, #111);
    outline: none;
    transition: border-color .15s;
    box-sizing: border-box;
}
.pe-field input:focus, .pe-field textarea:focus, .pe-field select:focus {
    border-color: var(--admin-primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37,99,235,.1);
}
.pe-field textarea { resize: vertical; min-height: 72px; }
/* ── History panel ──────────────────────────────────────────────── */
.pe-history-list { max-height: 200px; overflow-y: auto; }
.pe-rev-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 0;
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    font-size: 12px;
}
.pe-rev-item:last-child { border-bottom: none; }
.pe-rev-time { color: var(--admin-text-secondary, #6b7280); flex: 1; }
.pe-rev-author { color: var(--admin-text-secondary, #6b7280); font-size: 11px; }
/* ── Preview iframe ─────────────────────────────────────────────── */
.pe-preview-bar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: var(--admin-surface-2, #f8f9fa);
    border-bottom: 1px solid var(--admin-border, #e5e7eb);
    font-size: 12px;
    color: var(--admin-text-secondary, #6b7280);
}
#previewFrame {
    flex: 1;
    border: none;
    width: 100%;
    background: #fff;
}
/* ── Toast ──────────────────────────────────────────────────────── */
#pe-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #111;
    color: #fff;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    z-index: 9999;
    opacity: 0;
    transform: translateY(8px);
    transition: opacity .25s, transform .25s;
    pointer-events: none;
}
#pe-toast.show { opacity: 1; transform: translateY(0); }
/* ── Dirty indicator ────────────────────────────────────────────── */
#pe-dirty-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: var(--admin-warning, #f59e0b);
    display: inline-block;
    margin-right: 4px;
    opacity: 0;
    transition: opacity .2s;
}
#pe-dirty-dot.visible { opacity: 1; }
/* ── Responsive collapse preview ───────────────────────────────── */
@media (max-width: 900px) {
    .pe-right { display: none; }
    .pe-left  { width: 100%; }
    .pe-divider { display: none; }
}
</style>

<div class="pe-shell">

    <!-- ═══════════════════ LEFT PANEL ═══════════════════ -->
    <div class="pe-left" id="peLeft">

        <!-- Top bar -->
        <div class="pe-topbar">
            <div class="pe-breadcrumb">
                <a href="<?= Url::to(['/admin/page']) ?>"><i class="bi bi-file-earmark-text"></i> Страницы</a>
                &rsaquo; <?= Html::encode($model->title) ?>
            </div>

            <span id="pe-dirty-dot" title="Есть несохранённые изменения"></span>

            <?php if (!$model->isNewRecord): ?>
            <a href="<?= Html::encode($frontendUrl) ?>" target="_blank"
               class="admin-btn admin-btn-sm admin-btn-secondary" title="Открыть на сайте">
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
            <?php endif; ?>

            <button type="button" class="admin-btn admin-btn-sm admin-btn-secondary" id="btnAjaxSave"
                    <?= $model->isNewRecord ? 'disabled title="Сначала сохраните страницу обычным способом"' : '' ?>>
                <i class="bi bi-cloud-arrow-up"></i> Сохранить черновик
            </button>

            <button type="button" class="admin-btn admin-btn-sm admin-btn-primary" id="btnSubmit">
                <i class="bi bi-check-lg"></i> Сохранить
            </button>
        </div>

        <!-- Main form -->
        <?php $form = ActiveForm::begin([
            'id'      => 'pe-form',
            'options' => ['enctype' => 'multipart/form-data'],
        ]); ?>

        <!-- Title -->
        <div class="pe-title-wrap">
            <input type="text" id="page-title" name="PageContent[title]"
                   class="pe-title-input"
                   value="<?= Html::encode($model->title) ?>"
                   placeholder="Заголовок страницы"
                   required>
        </div>

        <!-- TinyMCE textarea -->
        <div class="pe-editor-wrap">
            <textarea id="page-content" name="PageContent[content]"><?= Html::encode($model->content ?? '') ?></textarea>
        </div>

        <!-- SEO accordion -->
        <div class="pe-accordion">
            <div class="pe-accordion-header" id="seoToggle">
                <i class="bi bi-search"></i> SEO и настройки
                <i class="bi bi-chevron-down pe-accordion-arrow"></i>
            </div>
            <div class="pe-accordion-body" id="seoBody">
                <div class="pe-field">
                    <label>Слаг (URL)</label>
                    <input type="text" value="/<?= Html::encode($model->slug) ?>" readonly
                           style="background:var(--admin-surface-2,#f8f9fa);cursor:default">
                </div>
                <div class="pe-field">
                    <label>Meta-описание</label>
                    <?= $form->field($model, 'meta_desc', ['template' => '{input}{error}'])->textarea([
                        'placeholder' => 'Краткое описание для поисковиков (до 160 символов)',
                        'rows' => 3,
                    ]) ?>
                </div>
                <div class="pe-field">
                    <label>Статус</label>
                    <?= $form->field($model, 'is_active', ['template' => '{input}{error}'])->dropDownList([
                        1 => 'Активна',
                        0 => 'Скрыта',
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- History accordion -->
        <?php if (!empty($revisions)): ?>
        <div class="pe-accordion">
            <div class="pe-accordion-header" id="histToggle">
                <i class="bi bi-clock-history"></i> История версий
                <span class="admin-badge admin-badge-secondary" style="font-size:11px"><?= count($revisions) ?></span>
                <i class="bi bi-chevron-down pe-accordion-arrow"></i>
            </div>
            <div class="pe-accordion-body" id="histBody">
                <div class="pe-history-list">
                    <?php foreach ($revisions as $rev): ?>
                    <div class="pe-rev-item">
                        <div class="pe-rev-time">
                            <?= date('d.m.Y H:i', $rev['saved_at']) ?>
                        </div>
                        <div class="pe-rev-author">
                            <?= Html::encode($rev['saved_by_name'] ?? 'система') ?>
                        </div>
                        <button type="button"
                                class="admin-btn admin-btn-xs admin-btn-secondary btn-restore"
                                data-rev-id="<?= (int)$rev['id'] ?>"
                                title="Восстановить эту версию">
                            <i class="bi bi-arrow-counterclockwise"></i> Восстановить
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php ActiveForm::end(); ?>
    </div><!-- /pe-left -->

    <!-- ═══════════════════ DIVIDER ═══════════════════ -->
    <div class="pe-divider" id="peDivider"></div>

    <!-- ═══════════════════ RIGHT PANEL (preview) ═══════════════════ -->
    <div class="pe-right" id="peRight">
        <div class="pe-preview-bar">
            <i class="bi bi-eye"></i> Предпросмотр
            <code style="font-size:11px;margin-left:4px"><?= Html::encode($frontendUrl) ?></code>
            <button type="button" class="admin-btn admin-btn-xs admin-btn-secondary" id="btnReloadPreview"
                    style="margin-left:auto" title="Обновить предпросмотр">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
        <?php if (!$model->isNewRecord): ?>
        <iframe id="previewFrame" src="<?= Html::encode($frontendUrl) ?>"
                title="Предпросмотр страницы"></iframe>
        <?php else: ?>
        <div style="display:flex;align-items:center;justify-content:center;flex:1;color:var(--admin-text-secondary);font-size:14px;gap:8px">
            <i class="bi bi-info-circle"></i> Предпросмотр появится после первого сохранения
        </div>
        <?php endif; ?>
    </div>

</div><!-- /pe-shell -->

<!-- Toast -->
<div id="pe-toast"></div>

<!-- ═══════════════════════════ SCRIPTS ══════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    var pageId     = <?= $pageId ?>;
    var csrfParam  = <?= json_encode($csrfParam) ?>;
    var csrfToken  = <?= json_encode($csrfToken) ?>;
    var autosaveUrl     = <?= json_encode($autosaveUrl) ?>;
    var uploadImageUrl  = <?= json_encode($uploadImageUrl) ?>;
    var restoreUrl      = <?= json_encode($restoreUrl) ?>;

    // ── Dirty state ──────────────────────────────────────────────────
    var isDirty        = false;
    var autosaveTimer  = null;
    var dirtyDot       = document.getElementById('pe-dirty-dot');

    function markDirty() {
        isDirty = true;
        if (dirtyDot) dirtyDot.classList.add('visible');
        clearTimeout(autosaveTimer);
        if (pageId) {
            autosaveTimer = setTimeout(doAutosave, 30000);
        }
    }

    function markClean() {
        isDirty = false;
        if (dirtyDot) dirtyDot.classList.remove('visible');
        clearTimeout(autosaveTimer);
    }

    // ── Toast helper ─────────────────────────────────────────────────
    var toastTimer;
    function showToast(msg, isError) {
        var el = document.getElementById('pe-toast');
        if (!el) return;
        el.textContent = msg;
        el.style.background = isError ? '#dc2626' : '#111';
        el.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { el.classList.remove('show'); }, 3000);
    }

    // ── CSRF helper ──────────────────────────────────────────────────
    function getCsrf() {
        // Yii2 stores CSRF in a meta tag as well as the form
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : csrfToken;
    }

    // ── Autosave ─────────────────────────────────────────────────────
    function doAutosave() {
        if (!isDirty || !pageId) return;
        var content = tinymce.get('page-content') ? tinymce.get('page-content').getContent() : '';
        var title   = (document.getElementById('page-title') || {}).value || '';
        fetch(autosaveUrl, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Csrf-Token': getCsrf(),
            },
            body: JSON.stringify({ id: pageId, content: content, title: title }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) {
                markClean();
                showToast('Автосохранение ' + new Date().toLocaleTimeString('ru'));
            } else {
                showToast('Ошибка автосохранения: ' + (data.error || '?'), true);
            }
        })
        .catch(function () { showToast('Ошибка автосохранения', true); });
    }

    // ── Preview reload ───────────────────────────────────────────────
    function reloadPreview() {
        var frame = document.getElementById('previewFrame');
        if (frame) {
            try { frame.contentWindow.location.reload(); } catch (e) {
                frame.src = frame.src; // cross-origin fallback
            }
        }
    }

    // ── TinyMCE init ─────────────────────────────────────────────────
    tinymce.init({
        selector:  '#page-content',
        height:    '100%',
        menubar:   false,
        plugins:   'advlist autolink lists link image table code fullscreen wordcount',
        toolbar:   'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link image table | code fullscreen | removeformat',
        content_css:  [<?= json_encode($frontendCssPath) ?>],
        images_upload_url:         uploadImageUrl,
        images_upload_credentials: true,
        automatic_uploads:         true,
        // Add CSRF token to image upload
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var fd = new FormData();
                fd.append('file', blobInfo.blob(), blobInfo.filename());
                fd.append(csrfParam, getCsrf());
                var xhr = new XMLHttpRequest();
                xhr.open('POST', uploadImageUrl);
                xhr.setRequestHeader('X-Csrf-Token', getCsrf());
                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) progress(e.loaded / e.total * 100);
                };
                xhr.onload = function () {
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject({ message: 'Ошибка загрузки: ' + xhr.status, remove: true });
                        return;
                    }
                    var json = JSON.parse(xhr.responseText);
                    if (!json || typeof json.location !== 'string') {
                        reject({ message: json.error || 'Ошибка сервера', remove: true });
                        return;
                    }
                    resolve(json.location);
                };
                xhr.onerror = function () { reject({ message: 'Сетевая ошибка', remove: true }); };
                xhr.send(fd);
            });
        },
        setup: function (editor) {
            editor.on('input change keyup', function () { markDirty(); });
            editor.on('init', function () {
                // Sync content to textarea on form submit
                document.getElementById('pe-form').addEventListener('submit', function () {
                    editor.save(); // writes back to <textarea>
                });
            });
        },
    });

    // ── Title field dirty tracking ────────────────────────────────────
    var titleInput = document.getElementById('page-title');
    if (titleInput) titleInput.addEventListener('input', markDirty);

    // ── Submit button ─────────────────────────────────────────────────
    var btnSubmit = document.getElementById('btnSubmit');
    if (btnSubmit) {
        btnSubmit.addEventListener('click', function () {
            if (tinymce.get('page-content')) tinymce.get('page-content').save();
            document.getElementById('pe-form').submit();
        });
    }

    // ── Ajax save (draft) button ──────────────────────────────────────
    var btnAjax = document.getElementById('btnAjaxSave');
    if (btnAjax) {
        btnAjax.addEventListener('click', function () {
            if (tinymce.get('page-content')) tinymce.get('page-content').save();
            doAutosave();
        });
    }

    // ── Reload preview button ─────────────────────────────────────────
    var btnReload = document.getElementById('btnReloadPreview');
    if (btnReload) btnReload.addEventListener('click', reloadPreview);

    // ── Accordions ────────────────────────────────────────────────────
    function initAccordion(headerId, bodyId) {
        var hdr  = document.getElementById(headerId);
        var body = document.getElementById(bodyId);
        if (!hdr || !body) return;
        hdr.addEventListener('click', function () {
            var open = body.classList.toggle('open');
            hdr.classList.toggle('open', open);
        });
    }
    initAccordion('seoToggle', 'seoBody');
    initAccordion('histToggle', 'histBody');

    // ── Restore revision ──────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-restore');
        if (!btn || !pageId) return;
        var revId = parseInt(btn.dataset.revId, 10);
        if (!confirm('Восстановить эту версию? Текущий контент будет сохранён как новая версия в истории.')) return;

        fetch(restoreUrl, {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Csrf-Token': getCsrf(),
            },
            body: JSON.stringify({ page_id: pageId, revision_id: revId }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.ok) {
                var ed = tinymce.get('page-content');
                if (ed) ed.setContent(data.content || '');
                if (data.title && titleInput) titleInput.value = data.title;
                markClean();
                showToast('Версия восстановлена');
                reloadPreview();
            } else {
                showToast('Ошибка: ' + (data.error || '?'), true);
            }
        })
        .catch(function () { showToast('Ошибка сети', true); });
    });

    // ── Warn on unload if dirty ───────────────────────────────────────
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'Есть несохранённые изменения. Покинуть страницу?';
        }
    });

    // ── Dynamic shell height (fills viewport below topbar) ───────────
    function adjustShellHeight() {
        var shell   = document.querySelector('.pe-shell');
        var topbar  = document.querySelector('.admin-topbar');
        if (!shell) return;
        var topbarBottom = topbar ? (topbar.getBoundingClientRect().bottom) : 0;
        var availH = window.innerHeight - topbarBottom;
        shell.style.height = Math.max(300, availH) + 'px';
    }
    // Run after layout is painted
    window.addEventListener('load', adjustShellHeight);
    window.addEventListener('resize', adjustShellHeight);
    // Also call it immediately (topbar may already be in DOM)
    document.addEventListener('DOMContentLoaded', adjustShellHeight);
    setTimeout(adjustShellHeight, 200); // after tinymce renders

    // ── Resizable divider ─────────────────────────────────────────────
    var divider = document.getElementById('peDivider');
    var shell   = document.querySelector('.pe-shell');
    var left    = document.getElementById('peLeft');
    if (divider && shell && left) {
        var dragging = false, startX, startW;
        divider.addEventListener('mousedown', function (e) {
            dragging = true;
            startX   = e.clientX;
            startW   = left.offsetWidth;
            divider.classList.add('active');
            document.body.style.cursor  = 'col-resize';
            document.body.style.userSelect = 'none';
        });
        document.addEventListener('mousemove', function (e) {
            if (!dragging) return;
            var dx      = e.clientX - startX;
            var newW    = Math.max(280, Math.min(shell.offsetWidth - 280, startW + dx));
            left.style.width = newW + 'px';
        });
        document.addEventListener('mouseup', function () {
            if (!dragging) return;
            dragging = false;
            divider.classList.remove('active');
            document.body.style.cursor    = '';
            document.body.style.userSelect = '';
        });
    }

})();
</script>
