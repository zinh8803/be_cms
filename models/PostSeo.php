<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * PostSeo model representing the "post_seo" table.
 *
 * @property int $post_id
 * @property string|null $title
 * @property string|null $title_en
 * @property string|null $description
 * @property string|null $description_en
 * @property string|null $keywords
 * @property string|null $keywords_en
 * @property int $created_at
 * @property int $updated_at
 */
class PostSeo extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%post_seo}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['post_id'], 'required'],
            [['post_id'], 'integer'],
            [['description', 'description_en'], 'string'],
            [['title', 'title_en', 'keywords', 'keywords_en'], 'string', 'max' => 255],
            [['post_id'], 'unique'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }
}
