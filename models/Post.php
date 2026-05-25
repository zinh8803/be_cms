<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Post model representing the "posts" table.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property int $category_id
 * @property int $author_id
 * @property int|null $thumbnail_id
 * @property string $status
 * @property string $visibility
 * @property int $view_count
 * @property int|null $published_at
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $deleted_at
 */
class Post extends ActiveRecord
{
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';

    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%posts}}';
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
            [['title', 'slug', 'content', 'category_id', 'author_id'], 'required'],
            [['content', 'content_en'], 'string'],
            [['category_id', 'author_id', 'thumbnail_id', 'view_count', 'published_at', 'deleted_at'], 'integer'],
            [['title', 'title_en', 'slug', 'status', 'visibility'], 'string', 'max' => 255],
            ['slug', 'unique'],
            ['status', 'default', 'value' => self::STATUS_DRAFT],
            ['status', 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED]],
            ['visibility', 'default', 'value' => self::VISIBILITY_PUBLIC],
            ['visibility', 'in', 'range' => [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE]],
        ];
    }

    /**
     * {@inheritdoc}
     * @return PostQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PostQuery(get_called_class());
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'author_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThumbnail()
    {
        return $this->hasOne(File::class, ['id' => 'thumbnail_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPostSeo()
    {
        return $this->hasOne(PostSeo::class, ['post_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('{{%post_tags}}', ['post_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(Comment::class, ['post_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getViews()
    {
        return $this->hasMany(PostView::class, ['post_id' => 'id']);
    }

    /**
     * Performs a soft delete on this post.
     * @return bool
     */
    public function softDelete()
    {
        $this->deleted_at = time();
        return $this->save(false, ['deleted_at']);
    }
}
