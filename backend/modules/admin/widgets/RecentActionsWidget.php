<?php

namespace app\backend\modules\admin\widgets;

use Yii;
use yii\base\Widget;
use app\backend\modules\admin\models\AdminLog;

/**
 * Виджет недавних действий администратора
 */
class RecentActionsWidget extends Widget
{
    /** @var int Количество записей для отображения */
    public int $limit = 10;

    /** @var bool Показывать ли все действия или только текущего пользователя */
    public bool $showAll = true;

    /** @var string|null CSS класс для контейнера */
    public ?string $containerClass = null;

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        try {
            $query = AdminLog::find();

            if (!$this->showAll && !Yii::$app->user->isGuest) {
                $query->where(['user_id' => Yii::$app->user->id]);
            }

            $logs = $query
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($this->limit)
                ->all();
        } catch (\yii\db\Exception $e) {
            // Таблица не существует или ошибка БД - возвращаем пустой массив
            $logs = [];
        }

        return $this->render('@backend/modules/admin/widgets/views/recent-actions', [
            'logs' => $logs,
            'containerClass' => $this->containerClass,
        ]);
    }
}
