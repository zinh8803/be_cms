<?php

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Comment model representing the "comments" table.
 *
 * @property int $id
 * @property int $post_id
 * @property int|null $parent_id
 * @property string $author_name
 * @property string $author_email
 * @property string $content
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 */
class Comment extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_HIDDEN = 'hidden';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%comments}}';
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
            [['post_id', 'author_name', 'author_email', 'content'], 'required'],
            [['post_id', 'parent_id'], 'integer'],
            [['content'], 'string'],
            [['author_name', 'author_email', 'status'], 'string', 'max' => 255],
            ['author_email', 'email'],
            ['status', 'default', 'value' => self::STATUS_PENDING],
            ['status', 'in', 'range' => [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_HIDDEN]],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPost()
    {
        return $this->hasOne(Post::class, ['id' => 'post_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReplies()
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])
            ->where(['status' => self::STATUS_APPROVED]);
    }
}
