<?php

/**
 * ProductTagSearch — Модель поиска тегов
 * 
 * НАЗНАЧЕНИЕ:
 * Фильтрация и поиск тегов в админ-панели.
 * Используется в GridView для списка тегов.
 * 
 * ИСПОЛЬЗОВАНИЕ:
 * - ProductTagController::actionIndex() — список тегов с фильтрами
 */

namespace app\backend\modules\catalog\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ProductTagSearch — модель поиска тегов
 */
class ProductTagSearch extends ProductTag
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'is_active', 'sort_order'], 'integer'],
            [['name', 'slug', 'color', 'description'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = ProductTag::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'sort_order' => SORT_ASC,
                    'id' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'slug', $this->slug])
            ->andFilterWhere(['like', 'color', $this->color])
            ->andFilterWhere(['like', 'description', $this->description]);

        return $dataProvider;
    }

    /**
     * Получить список активных тегов для фильтра
     * @return array
     */
    public static function getActiveTagsList()
    {
        return ProductTag::find()
            ->select(['name', 'id'])
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->indexBy('id')
            ->column();
    }
}
