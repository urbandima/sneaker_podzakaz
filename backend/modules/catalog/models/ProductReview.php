<?php

/**
 * ProductReview — Временная заглушка для модели отзыва
 * 
 * НАЗНАЧЕНИЕ:
 * Временная реализация для предотвращения ошибок.
 * TODO: Создать полную реализацию модели.
 */
namespace app\backend\modules\catalog\models;

use yii\db\ActiveRecord;
use app\backend\modules\admin\models\User;

class ProductReview extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%product_review}}';
    }
    
    public function rules()
    {
        return [
            [['product_id', 'user_id', 'rating', 'content'], 'required'],
            [['product_id', 'user_id', 'rating', 'is_published'], 'integer'],
            [['content'], 'string'],
            [['created_at'], 'safe'],
        ];
    }
    
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
    
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
