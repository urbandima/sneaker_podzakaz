<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\SitemapNotifier;

/**
 * Модель FilterHistory (История фильтрации)
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $filter_params JSON строка с параметрами фильтров
 * @property int $results_count Количество результатов
 * @property string $created_at
 * 
 * @property User $user
 */
class FilterHistory extends ActiveRecord
{
    public static function tableName()
    {
        return 'filter_history';
    }

    public function rules()
    {
        return [
            [['filter_params', 'results_count'], 'required'],
            [['user_id', 'results_count'], 'integer'],
            [['session_id'], 'string', 'max' => 255],
            [['filter_params'], 'string'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Пользователь',
            'session_id' => 'ID сессии',
            'filter_params' => 'Параметры фильтров',
            'results_count' => 'Количество результатов',
            'created_at' => 'Создано',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /**
     * Сохранить историю фильтрации
     */
    public static function saveFilters($filterParams, $resultsCount, $userId = null, $sessionId = null)
    {
        $history = new static();
        $history->user_id = $userId;
        $history->session_id = $sessionId;
        $history->filter_params = json_encode($filterParams);
        $history->results_count = $resultsCount;
        
        $saved = $history->save();

        if ($saved) {
            SitemapNotifier::scheduleRegeneration();
        }

        return $saved;
    }

    /**
     * Получить последние фильтры пользователя
     */
    public static function getLastFilters($userId = null, $sessionId = null, $limit = 5)
    {
        $query = static::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit);
            
        if ($userId) {
            $query->where(['user_id' => $userId]);
        } elseif ($sessionId) {
            $query->where(['session_id' => $sessionId]);
        }
        
        return $query->all();
    }

    /**
     * Получить параметры фильтров как массив
     */
    public function getFilterParamsArray()
    {
        return json_decode($this->filter_params, true);
    }
}
