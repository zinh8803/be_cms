<?php

namespace app\services;

use Yii;
use app\models\Comment;
use app\models\CommentForm;

class CommentService
{
    private $_auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->_auditLog = $auditLog;
    }

    /**
     * Post a new comment (default status is pending moderation).
     * @param CommentForm $form
     * @return Comment|false
     */
    public function createComment(CommentForm $form)
    {
        $comment = new Comment();
        $comment->post_id = $form->post_id;
        $comment->parent_id = $form->parent_id;
        $comment->author_name = $form->author_name;
        $comment->author_email = $form->author_email;
        $comment->content = $form->content;
        $comment->status = Comment::STATUS_APPROVED;

        if ($comment->save()) {
            return $comment;
        }

        $form->addErrors($comment->getErrors());
        return false;
    }

    /**
     * Moderate comment status (approve, hide, delete).
     * @param int $adminId
     * @param int $commentId
     * @param string $status
     * @return bool
     */
    public function moderateComment($adminId, $commentId, $status)
    {
        $comment = Comment::findOne($commentId);
        if (!$comment) {
            return false;
        }

        if ($status === 'delete') {
            if ($comment->delete()) {
                $this->_auditLog->log($adminId, 'XÓA_BÌNH_LUẬN', [
                    'comment_id' => $commentId,
                    'author' => $comment->author_name
                ]);
                Yii::$app->cache->flush();
                return true;
            }
            return false;
        }

        $comment->status = $status;
        if ($comment->save(false)) {
            $this->_auditLog->log($adminId, 'DUYỆT_BÌNH_LUẬN', [
                'comment_id' => $commentId,
                'status' => $status,
                'author' => $comment->author_name
            ]);
            Yii::$app->cache->flush();
            return true;
        }

        return false;
    }
}
