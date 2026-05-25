<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * PostView model representing the "post_views" table.
 *
 * @property int $id
 * @property int $post_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int $viewed_at
 */
class PostView extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%post_views}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\AttributeBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'viewed_at',
                ],
                'value' => function () {
                    return time();
                },
            ],
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
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string'],
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
