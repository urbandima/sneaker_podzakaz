<?php

namespace app\backend\modules\admin\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use app\backend\modules\admin\models\import\ImportSource;
use app\backend\modules\admin\models\import\ImportTask;

/**
 * ImportWidget — Виджет импорта товаров
 */
class ImportWidget extends Widget
{
    public function run()
    {
        // Получаем статистику импорта
        $stats = $this->getImportStats();
        $sources = ImportSource::find()->where(['is_active' => true])->all();
        $recentTasks = ImportTask::find()->orderBy(['created_at' => SORT_DESC])->limit(3)->all();

        return $this->render('import', [
            'stats' => $stats,
            'sources' => $sources,
            'recentTasks' => $recentTasks,
        ]);
    }

    /**
     * Получить статистику импорта
     */
    protected function getImportStats()
    {
        return [
            'total_sources' => ImportSource::find()->count(),
            'active_sources' => ImportSource::find()->where(['is_active' => true])->count(),
            'total_tasks' => ImportTask::find()->count(),
            'running_tasks' => ImportTask::find()->where(['status' => 'running'])->count(),
            'completed_tasks' => ImportTask::find()->where(['status' => 'completed'])->count(),
            'total_imported' => (int) ImportTask::find()->sum('imported_count'),
            'total_updated' => (int) ImportTask::find()->sum('updated_count'),
            'total_errors' => (int) ImportTask::find()->sum('failed_count'),
        ];
    }
}
