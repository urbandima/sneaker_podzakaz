<?php

namespace app\backend\modules\admin\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * PageContent — редактируемый контент статических страниц сайта
 *
 * @property int    $id
 * @property string $slug        URL-слаг (privacy, contacts, sale, ...)
 * @property string $title       Заголовок страницы
 * @property string $content     HTML-контент
 * @property string $meta_desc   Meta-description
 * @property bool   $is_active
 * @property int    $updated_by
 * @property int    $created_at
 * @property int    $updated_at
 */
class PageContent extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%page_content}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['slug', 'title'], 'required'],
            [['slug'],      'string', 'max' => 100],
            [['title'],     'string', 'max' => 255],
            [['meta_desc'], 'string', 'max' => 500],
            [['content'],   'string'],
            [['is_active'], 'boolean'],
            [['updated_by'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'slug'      => 'Слаг (URL)',
            'title'     => 'Заголовок',
            'content'   => 'Контент (HTML)',
            'meta_desc' => 'Meta-описание',
            'is_active' => 'Активна',
        ];
    }

    /** Check whether the table exists in the database */
    public static function isAvailable(): bool
    {
        try {
            return Yii::$app->db->schema->getTableSchema('{{%page_content}}') !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /** Get content by slug, returns null if table missing or slug not found */
    public static function getBySlug(string $slug): ?self
    {
        if (!self::isAvailable()) return null;
        try {
            return self::find()->where(['slug' => $slug, 'is_active' => 1])->one();
        } catch (\Exception $e) {
            return null;
        }
    }

    /** List of all known page slugs with human-readable titles */
    public static function knownPages(): array
    {
        return [
            'privacy'        => 'Политика конфиденциальности',
            'payment-terms'  => 'Способы оплаты',
            'return-policy'  => 'Возврат и обмен',
            'contacts'       => 'Контакты',
            'sale'           => 'Скидки и акции',
            'about'          => 'О компании',
            'delivery-terms' => 'Условия доставки',
        ];
    }

    /**
     * Save current content as a revision before updating.
     * Silently skips if the page_revision table doesn't exist yet.
     */
    public function saveRevision(): void
    {
        try {
            if (Yii::$app->db->schema->getTableSchema('{{%page_revision}}') === null) {
                return;
            }
            if ($this->isNewRecord || empty($this->id)) {
                return;
            }
            Yii::$app->db->createCommand()->insert('{{%page_revision}}', [
                'page_id'  => $this->id,
                'content'  => $this->content,
                'title'    => $this->title,
                'saved_by' => Yii::$app->user->isGuest ? null : Yii::$app->user->id,
                'saved_at' => time(),
            ])->execute();
        } catch (\Exception $e) {
            Yii::warning('saveRevision failed: ' . $e->getMessage(), 'page');
        }
    }

    /**
     * Get last N revisions for this page.
     *
     * @param int $limit
     * @return array
     */
    public function getRevisions(int $limit = 10): array
    {
        try {
            if (Yii::$app->db->schema->getTableSchema('{{%page_revision}}') === null) {
                return [];
            }
            return Yii::$app->db->createCommand(
                'SELECT r.*, u.username AS saved_by_name
                 FROM {{%page_revision}} r
                 LEFT JOIN {{%user}} u ON u.id = r.saved_by
                 WHERE r.page_id = :pid
                 ORDER BY r.saved_at DESC
                 LIMIT :lim',
                [':pid' => $this->id, ':lim' => $limit]
            )->queryAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check whether page_revision table is available.
     */
    public static function isRevisionAvailable(): bool
    {
        try {
            return Yii::$app->db->schema->getTableSchema('{{%page_revision}}') !== null;
        } catch (\Exception $e) {
            return false;
        }
    }
}
