<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Notification model representing the "notifications" table.
 *
 * @property int $id
 * @property string $type
 * @property string $content
 * @property int $is_read
 * @property int $created_at
 */
class Notification extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%notifications}}';
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
                    ActiveRecord::EVENT_BEFORE_INSERT => 'created_at',
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
            [['type', 'content'], 'required'],
            [['type'], 'string', 'max' => 50],
            [['content'], 'string'],
            [['is_read'], 'integer'],
        ];
    }
}
