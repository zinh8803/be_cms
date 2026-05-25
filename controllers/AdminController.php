<?php

namespace app\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use app\models\Post;
use app\models\PostForm;
use app\models\PostSearch;
use app\models\Category;
use app\models\Tag;
use app\models\Comment;
use app\models\AdminLog;
use app\models\File;
use app\repositories\PostRepository;
use app\services\PostService;
use app\services\CommentService;
use app\services\AuditLogService;

/**
 * AdminController manages CMS backend dashboard actions.
 */
class AdminController extends ApiController
{
    private $_postRepository;
    private $_postService;
    private $_commentService;
    private $_auditLogService;

    public function __construct(
        $id,
        $module,
        PostRepository $postRepository,
        PostService $postService,
        CommentService $commentService,
        AuditLogService $auditLogService,
        $config = []
    ) {
        $this->_postRepository = $postRepository;
        $this->_postService = $postService;
        $this->_commentService = $commentService;
        $this->_auditLogService = $auditLogService;
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Authenticate all admin actions with HttpBearerAuth
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Helper to verify if the user has admin or editor role.
     * @param string &$role Returns user role
     * @return bool
     */
    private function verifyStaffRole(&$role)
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return false;
        }
        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        $role = !empty($roles) ? key($roles) : 'user';

        return $role === 'admin' || $role === 'editor';
    }

    /**
     * GET /api/admin/posts
     * Lists posts with backend filters.
     */
    public function actionPostIndex()
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        $user = Yii::$app->user->identity;
        $searchModel = new PostSearch();
        $dataProvider = $searchModel->searchAdmin(Yii::$app->request->queryParams, $user->id, $role);
        
        $models = $dataProvider->getModels();
        $postsData = [];
        foreach ($models as $post) {
            $postsData[] = [
                'id' => $post->id,
                'title' => $post->title,
                'title_en' => $post->title_en,
                'slug' => $post->slug,
                'author' => $post->author ? $post->author->username : null,
                'category' => $post->category ? ['id' => $post->category->id, 'name' => $post->category->name] : null,
                'tags' => array_map(function($tag) { return $tag->name; }, $post->tags),
                'thumbnail_url' => $post->thumbnail ? $post->thumbnail->filepath : null,
                'status' => $post->status,
                'visibility' => $post->visibility,
                'view_count' => (int)$post->view_count,
                'created_at' => $post->created_at,
            ];
        }

        return $this->successResponse([
            'posts' => $postsData,
            'pagination' => [
                'totalCount' => (int)$dataProvider->totalCount,
                'pageSize' => (int)$dataProvider->pagination->pageSize,
                'currentPage' => (int)($dataProvider->pagination->page + 1),
                'pageCount' => (int)$dataProvider->pagination->pageCount,
            ]
        ]);
    }

    /**
     * GET /api/admin/posts/<id>
     * Fetch full post details for editing.
     */
    public function actionPostView($id)
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        $user = Yii::$app->user->identity;
        $post = $this->_postRepository->findById((int)$id);
        if (!$post) {
            return $this->errorResponse(404, 'Không tìm thấy bài viết.');
        }

        // RBAC constraint: Editors can only view their own posts
        if ($role === 'editor' && $post->author_id !== $user->id) {
            return $this->errorResponse(403, 'Bạn không có quyền xem chi tiết bài viết của người khác.');
        }

        // Load relations manually to ensure they are available
        $tags = array_map(function($t) { return $t->name; }, $post->tags);

        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'title_en' => $post->title_en,
            'content' => $post->content,
            'content_en' => $post->content_en,
            'category_id' => $post->category_id,
            'thumbnail_id' => $post->thumbnail_id,
            'thumbnail_url' => $post->thumbnail ? $post->thumbnail->filepath : null,
            'status' => $post->status,
            'visibility' => $post->visibility,
            'tags' => $tags,
            'seo' => $post->postSeo ? [
                'title' => $post->postSeo->title,
                'description' => $post->postSeo->description,
                'keywords' => $post->postSeo->keywords,
            ] : null,
        ];

        return $this->successResponse($data);
    }

    /**
     * POST /api/admin/posts
     * Creates a new post.
     */
    public function actionPostCreate()
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();

        $form = new PostForm();
        $form->attributes = $body;

        if ($form->validate()) {
            $post = $this->_postService->createPost($user->id, $form);
            if ($post) {
                return $this->successResponse([
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                ], 'Đăng bài viết thành công.');
            }
        }

        return $this->errorResponse(422, 'Dữ liệu không hợp lệ.', $form->getErrors());
    }

    /**
     * PUT /api/admin/posts/<id>
     * Updates an existing post.
     */
    public function actionPostUpdate($id)
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        $user = Yii::$app->user->identity;
        $post = $this->_postRepository->findById((int)$id);
        if (!$post) {
            return $this->errorResponse(404, 'Không tìm thấy bài viết.');
        }

        // RBAC constraint: Editors can only edit their own posts
        if ($role === 'editor' && $post->author_id !== $user->id) {
            return $this->errorResponse(403, 'Bạn không có quyền chỉnh sửa bài viết của người khác.');
        }

        $body = Yii::$app->request->getBodyParams();
        $form = new PostForm();
        $form->id = $post->id;
        $form->attributes = $body;

        if ($form->validate()) {
            $updatedPost = $this->_postService->updatePost($user->id, $post, $form);
            if ($updatedPost) {
                return $this->successResponse([
                    'id' => $updatedPost->id,
                    'title' => $updatedPost->title,
                    'slug' => $updatedPost->slug,
                ], 'Cập nhật bài viết thành công.');
            }
        }

        return $this->errorResponse(422, 'Dữ liệu không hợp lệ.', $form->getErrors());
    }

    /**
     * DELETE /api/admin/posts/<id>
     * Soft deletes a post.
     */
    public function actionPostDelete($id)
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        $user = Yii::$app->user->identity;
        $post = $this->_postRepository->findById((int)$id);
        if (!$post) {
            return $this->errorResponse(404, 'Không tìm thấy bài viết.');
        }

        // RBAC constraint: Editors can only delete their own posts
        if ($role === 'editor' && $post->author_id !== $user->id) {
            return $this->errorResponse(403, 'Bạn không có quyền xóa bài viết của người khác.');
        }

        if ($this->_postService->softDeletePost($user->id, $post)) {
            return $this->successResponse(null, 'Đã lưu trữ (xóa tạm thời) bài viết thành công.');
        }

        return $this->errorResponse(500, 'Không thể xóa bài viết.');
    }

    /**
     * GET /api/admin/categories
     * Returns categories for form selection.
     */
    public function actionCategoryIndex()
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập.');
        }

        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();
        $data = array_map(function($c) {
            return ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'description' => $c->description];
        }, $categories);

        return $this->successResponse($data);
    }

    /**
     * POST /api/admin/categories
     * Creates a new category (admin only).
     */
    public function actionCategoryCreate()
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được tạo danh mục mới.');
        }

        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();

        $category = new Category();
        $category->name = isset($body['name']) ? $body['name'] : '';
        $category->description = isset($body['description']) ? $body['description'] : '';
        $category->slug = PostForm::slugify($category->name);

        if ($category->validate()) {
            if ($category->save()) {
                $this->_auditLogService->log($user->id, 'TẠO_DANH_MỤC', ['category_id' => $category->id, 'name' => $category->name]);
                return $this->successResponse($category, 'Tạo danh mục mới thành công.');
            }
        }

        return $this->errorResponse(422, 'Dữ liệu không hợp lệ.', $category->getErrors());
    }

    /**
     * PUT /api/admin/categories/<id>
     * Updates an existing category (admin only).
     */
    public function actionCategoryUpdate($id)
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được chỉnh sửa danh mục.');
        }

        $user = Yii::$app->user->identity;
        $category = Category::findOne((int)$id);
        if (!$category) {
            return $this->errorResponse(404, 'Không tìm thấy danh mục.');
        }

        $body = Yii::$app->request->getBodyParams();
        $category->name = isset($body['name']) ? $body['name'] : $category->name;
        $category->description = isset($body['description']) ? $body['description'] : $category->description;
        $category->slug = PostForm::slugify($category->name);

        if ($category->validate()) {
            if ($category->save()) {
                $this->_auditLogService->log($user->id, 'CẬP_NHẬT_DANH_MỤC', ['category_id' => $category->id, 'name' => $category->name]);
                return $this->successResponse($category, 'Cập nhật danh mục thành công.');
            }
        }

        return $this->errorResponse(422, 'Dữ liệu không hợp lệ.', $category->getErrors());
    }

    /**
     * DELETE /api/admin/categories/<id>
     * Deletes a category (admin only).
     */
    public function actionCategoryDelete($id)
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được xóa danh mục.');
        }

        $user = Yii::$app->user->identity;
        $category = Category::findOne((int)$id);
        if (!$category) {
            return $this->errorResponse(404, 'Không tìm thấy danh mục.');
        }

        $postCount = Post::find()->where(['category_id' => $category->id])->count();
        if ($postCount > 0) {
            return $this->errorResponse(400, 'Không thể xóa danh mục này vì đang có bài viết sử dụng.');
        }

        if ($category->delete()) {
            $this->_auditLogService->log($user->id, 'XÓA_DANH_MỤC', ['category_id' => $category->id, 'name' => $category->name]);
            return $this->successResponse(null, 'Xóa danh mục thành công.');
        }

        return $this->errorResponse(500, 'Không thể xóa danh mục.');
    }

    /**
     * GET /api/admin/tags
     * Returns tag suggestions.
     */
    public function actionTagIndex()
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền.');
        }

        $tags = Tag::find()->orderBy(['name' => SORT_ASC])->all();
        $data = array_map(function($t) { return $t->name; }, $tags);
        return $this->successResponse($data);
    }

    /**
     * GET /api/admin/tags-full
     * Returns full tag details (admin only/staff).
     */
    public function actionTagFullIndex()
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập.');
        }

        $tags = Tag::find()->orderBy(['name' => SORT_ASC])->all();
        $data = array_map(function($t) {
            return ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug];
        }, $tags);

        return $this->successResponse($data);
    }

    /**
     * POST /api/admin/tags
     * Creates a new tag (admin only).
     */
    public function actionTagCreate()
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được tạo thẻ mới.');
        }

        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();

        $tag = new Tag();
        $tag->name = isset($body['name']) ? $body['name'] : '';
        $tag->slug = PostForm::slugify($tag->name);

        if ($tag->validate()) {
            if ($tag->save()) {
                $this->_auditLogService->log($user->id, 'TẠO_THẺ', ['tag_id' => $tag->id, 'name' => $tag->name]);
                return $this->successResponse($tag, 'Tạo thẻ mới thành công.');
            }
        }

        return $this->errorResponse(422, 'Dữ liệu không hợp lệ.', $tag->getErrors());
    }

    /**
     * PUT /api/admin/tags/<id>
     * Updates an existing tag (admin only).
     */
    public function actionTagUpdate($id)
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được chỉnh sửa thẻ.');
        }

        $user = Yii::$app->user->identity;
        $tag = Tag::findOne((int)$id);
        if (!$tag) {
            return $this->errorResponse(404, 'Không tìm thấy thẻ.');
        }

        $body = Yii::$app->request->getBodyParams();
        $tag->name = isset($body['name']) ? $body['name'] : $tag->name;
        $tag->slug = PostForm::slugify($tag->name);

        if ($tag->validate()) {
            if ($tag->save()) {
                $this->_auditLogService->log($user->id, 'CẬP_NHẬT_THẺ', ['tag_id' => $tag->id, 'name' => $tag->name]);
                return $this->successResponse($tag, 'Cập nhật thẻ thành công.');
            }
        }

        return $this->errorResponse(422, 'Dữ liệu không hợp lệ.', $tag->getErrors());
    }

    /**
     * DELETE /api/admin/tags/<id>
     * Deletes a tag (admin only).
     */
    public function actionTagDelete($id)
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được xóa thẻ.');
        }

        $user = Yii::$app->user->identity;
        $tag = Tag::findOne((int)$id);
        if (!$tag) {
            return $this->errorResponse(404, 'Không tìm thấy thẻ.');
        }

        // Delete references in post_tags first
        Yii::$app->db->createCommand()
            ->delete('{{%post_tags}}', ['tag_id' => $tag->id])
            ->execute();

        if ($tag->delete()) {
            $this->_auditLogService->log($user->id, 'XÓA_THẺ', ['tag_id' => $tag->id, 'name' => $tag->name]);
            return $this->successResponse(null, 'Xóa thẻ thành công.');
        }

        return $this->errorResponse(500, 'Không thể xóa thẻ.');
    }

    /**
     * GET /api/admin/comments
     * Moderate list (admin only).
     */
    public function actionCommentIndex()
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được quản lý bình luận.');
        }

        $comments = Comment::find()
            ->with(['post'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $data = array_map(function($c) {
            return [
                'id' => $c->id,
                'post_title' => $c->post ? $c->post->title : 'N/A',
                'post_id' => $c->post_id,
                'author_name' => $c->author_name,
                'author_email' => $c->author_email,
                'content' => $c->content,
                'status' => $c->status,
                'created_at' => $c->created_at,
            ];
        }, $comments);

        return $this->successResponse($data);
    }

    /**
     * PUT /api/admin/comments/<id>
     * Approves/hides/deletes comments (admin only).
     */
    public function actionCommentUpdate($id)
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được duyệt bình luận.');
        }

        $user = Yii::$app->user->identity;
        $body = Yii::$app->request->getBodyParams();
        $status = isset($body['status']) ? $body['status'] : '';

        if (!in_array($status, ['approved', 'hidden', 'delete'])) {
            return $this->errorResponse(400, 'Trạng thái duyệt không hợp lệ. Chỉ chấp nhận: approved, hidden, delete.');
        }

        if ($this->_commentService->moderateComment($user->id, (int)$id, $status)) {
            $msg = $status === 'delete' ? 'Đã xóa bình luận thành công.' : 'Cập nhật trạng thái duyệt bình luận thành công.';
            return $this->successResponse(null, $msg);
        }

        return $this->errorResponse(500, 'Không thể thực hiện kiểm duyệt bình luận.');
    }

    /**
     * GET /api/admin/logs
     * Audit logs (admin only).
     */
    public function actionLogIndex()
    {
        if (!$this->verifyStaffRole($role) || $role !== 'admin') {
            return $this->errorResponse(403, 'Chỉ quản trị viên mới được xem nhật ký hệ thống.');
        }

        $logs = AdminLog::find()
            ->with(['user'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(100)
            ->all();

        $data = array_map(function($l) {
            return [
                'id' => $l->id,
                'username' => $l->user ? $l->user->username : 'N/A',
                'action' => $l->action,
                'details' => $l->details,
                'created_at' => $l->created_at,
            ];
        }, $logs);

        return $this->successResponse($data);
    }

    /**
     * GET /api/admin/files
     * Returns uploaded files for the media library/gallery.
     */
    public function actionFileIndex()
    {
        if (!$this->verifyStaffRole($role)) {
            return $this->errorResponse(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        $files = File::find()->orderBy(['created_at' => SORT_DESC])->all();
        $data = array_map(function($f) {
            return [
                'id' => $f->id,
                'filename' => $f->filename,
                'url' => $f->filepath,
                'file_size' => (int)$f->file_size,
                'mime_type' => $f->mime_type,
                'created_at' => $f->created_at,
            ];
        }, $files);

        return $this->successResponse($data);
    }
}
