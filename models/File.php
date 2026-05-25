<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * File model representing the "files" table.
 *
 * @property int $id
 * @property string $filename
 * @property string $filepath
 * @property int $file_size
 * @property string $mime_type
 * @property int $created_at
 */
class File extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%files}}';
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
            [['filename', 'filepath', 'file_size', 'mime_type'], 'required'],
            [['file_size'], 'integer'],
            [['filename', 'filepath'], 'string', 'max' => 255],
            [['mime_type'], 'string', 'max' => 100],
        ];
    }
}
