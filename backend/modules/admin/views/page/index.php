<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Редактор страниц';

$this->params['headerActions'] = [];

if (!$available):
?>
<div class="admin-card" style="margin-bottom:1.5rem;border-left:4px solid var(--admin-warning,#f59e0b)">
    <div class="admin-card-body" style="display:flex;gap:0.75rem;align-items:flex-start;padding:1rem 1.25rem">
        <i class="bi bi-exclamation-triangle" style="color:var(--admin-warning,#f59e0b);font-size:1.25rem;margin-top:1px;flex-shrink:0"></i>
        <div>
            <strong>Таблица базы данных не создана</strong><br>
            <span style="font-size:13px;color:var(--admin-text-secondary)">
                Запустите миграцию для активации редактора страниц:<br>
                <code>php yii migrate --migrationPath=migrations</code>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-file-earmark-text"></i> Статические страницы</h2>
        <span class="admin-badge admin-badge-secondary"><?= count($pages) ?> страниц</span>
    </div>
    <div style="overflow-x:auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Страница</th>
                    <th>Слаг (URL)</th>
                    <th>Контент</th>
                    <th>Изменено</th>
                    <th style="text-align:right">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                <tr>
                    <td style="font-weight:500"><?= Html::encode($page['title']) ?></td>
                    <td><code>/<?= Html::encode($page['slug']) ?></code></td>
                    <td>
                        <?php if ($page['has_content']): ?>
                        <span class="admin-badge admin-badge-success"><i class="bi bi-check"></i> Есть</span>
                        <?php else: ?>
                        <span class="admin-badge admin-badge-secondary">Статичный файл</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:13px;color:var(--admin-text-secondary)">
                        <?= $page['updated_at'] ? date('d.m.Y H:i', $page['updated_at']) : '—' ?>
                    </td>
                    <td style="text-align:right">
                        <div style="display:flex;gap:6px;justify-content:flex-end">
                            <a href="/<?= $page['slug'] ?>" target="_blank"
                               class="admin-btn admin-btn-sm admin-btn-secondary" title="Открыть страницу">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php if ($available): ?>
                            <a href="<?= Url::to(['/admin/page/edit', 'slug' => $page['slug']]) ?>"
                               class="admin-btn admin-btn-sm admin-btn-primary">
                                <i class="bi bi-pencil"></i> Редактировать
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
