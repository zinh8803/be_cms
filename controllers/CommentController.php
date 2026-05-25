<?php

namespace app\controllers;

use Yii;
use app\models\CommentForm;
use app\repositories\PostRepository;
use app\services\CommentService;
use yii\filters\auth\HttpBearerAuth;

/**
 * CommentController handles creation and listing of comments.
 */
class CommentController extends ApiController
{
    private $_postRepository;
    private $_commentService;

    public function __construct($id, $module, PostRepository $postRepository, CommentService $commentService, $config = [])
    {
        $this->_postRepository = $postRepository;
        $this->_commentService = $commentService;
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'only' => ['create'],
        ];
        return $behaviors;
    }

    /**
     * GET /api/posts/<id>/comments
     * Fetch comments tree for a post.
     */
    public function actionIndex($id)
    {
        $post = $this->_postRepository->findById((int)$id);
        if (!$post) {
            return $this->errorResponse(404, 'Bài viết không tồn tại.');
        }

        $commentsTree = $this->_postRepository->findCommentsTree($post->id);
        return $this->successResponse($commentsTree);
    }

    /**
     * POST /api/posts/<id>/comments
     * Submit a comment on a post.
     */
    public function actionCreate($id)
    {
        $post = $this->_postRepository->findById((int)$id);
        if (!$post) {
            return $this->errorResponse(404, 'Bài viết không tồn tại.');
        }

        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        
        $form = new CommentForm();
        $form->attributes = $body;
        $form->post_id = $post->id;
        $form->author_name = $user->username;
        $form->author_email = $user->email;

        if ($form->validate()) {
            $comment = $this->_commentService->createComment($form);
            if ($comment) {
                return $this->successResponse(
                    [
                        'id' => $comment->id,
                        'author_name' => $comment->author_name,
                        'status' => $comment->status,
                        'created_at' => $comment->created_at,
                    ],
                    'Bình luận đã được gửi và đang chờ duyệt.'
                );
            }
        }

        return $this->errorResponse(422, 'Dữ liệu bình luận không hợp lệ.', $form->getErrors());
    }
}
