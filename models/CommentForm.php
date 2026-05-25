<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * CommentForm handles validation of comments.
 */
class CommentForm extends Model
{
    public $post_id;
    public $parent_id;
    public $author_name;
    public $author_email;
    public $content;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['post_id', 'author_name', 'author_email', 'content'], 'required', 'message' => '{attribute} không được bỏ trống.'],
            [['post_id', 'parent_id'], 'integer'],
            ['author_email', 'email', 'message' => 'Địa chỉ email không hợp lệ.'],
            [['author_name'], 'string', 'max' => 100],
            [['content'], 'string'],
            ['post_id', 'exist', 'targetClass' => Post::class, 'targetAttribute' => 'id', 'message' => 'Bài viết không tồn tại.'],
            ['parent_id', 'exist', 'targetClass' => Comment::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true, 'message' => 'Bình luận phản hồi không tồn tại.'],
        ];
    }

    /**
     * Get labels.
     */
    public function attributeLabels()
    {
        return [
            'author_name' => 'Tên của bạn',
            'author_email' => 'Địa chỉ Email',
            'content' => 'Nội dung bình luận',
        ];
    }
}
