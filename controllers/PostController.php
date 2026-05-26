<?php

namespace app\controllers;

use Yii;
use app\models\PostSearch;
use app\repositories\PostRepository;
use app\jobs\ViewLogJob;

/**
 * PostController handles public facing post actions.
 */
class PostController extends ApiController
{
    private $_postRepository;

    public function __construct($id, $module, PostRepository $postRepository, $config = [])
    {
        $this->_postRepository = $postRepository;
        parent::__construct($id, $module, $config);
    }

    /**
     * GET /api/posts
     * Lists public posts (cached).
     */
    public function actionIndex()
    {
        $params = Yii::$app->request->queryParams;
        $cacheKey = 'public_posts_list_' . md5(json_encode($params));
        
        $data = Yii::$app->cache->get($cacheKey);
        if ($data === false) {
            $searchModel = new PostSearch();
            $dataProvider = $searchModel->searchPublic($params);
            $models = $dataProvider->getModels();
            
            $postsData = [];
            foreach ($models as $post) {
                $postsData[] = [
                    'id' => $post->id,
                    'title' => $post->title,
                    'title_en' => $post->title_en,
                    'slug' => $post->slug,
                    'summary' => mb_strimwidth(strip_tags($post->content), 0, 160, '...'),
                    'summary_en' => $post->content_en ? mb_strimwidth(strip_tags($post->content_en), 0, 160, '...') : null,
                    'category' => $post->category ? ['name' => $post->category->name, 'slug' => $post->category->slug] : null,
                    'tags' => array_map(function($tag) {
                        return ['name' => $tag->name, 'slug' => $tag->slug];
                    }, $post->tags),
                    'thumbnail_url' => $post->thumbnail ? $post->thumbnail->filepath : null,
                    'view_count' => $post->view_count,
                    'published_at' => $post->published_at,
                ];
            }
            
            $data = [
                'posts' => $postsData,
                'pagination' => [
                    'totalCount' => (int)$dataProvider->totalCount,
                    'pageSize' => (int)$dataProvider->pagination->pageSize,
                    'currentPage' => (int)($dataProvider->pagination->page + 1),
                    'pageCount' => (int)$dataProvider->pagination->pageCount,
                ],
            ];
            
            // Cache in Redis for 10 minutes (600s)
            Yii::$app->cache->set($cacheKey, $data, 600);
        }

        return $this->successResponse($data);
    }

    /**
     * GET /api/posts/<slug>
     * Fetch detail of post (cached, increments views via queue).
     */
    public function actionView($slug)
    {
        $cacheKey = 'post_detail_' . $slug;
        $postData = Yii::$app->cache->get($cacheKey);

        if ($postData === false) {
            $post = $this->_postRepository->findPublicActiveBySlug($slug);
            if (!$post) {
                return $this->errorResponse(404, 'Bài viết không tồn tại hoặc đã được gỡ xuống.');
            }

            $postData = [
                'id' => $post->id,
                'title' => $post->title,
                'title_en' => $post->title_en,
                'slug' => $post->slug,
                'content' => $post->content,
                'content_en' => $post->content_en,
                'category' => $post->category ? [
                    'id' => $post->category->id,
                    'name' => $post->category->name,
                    'slug' => $post->category->slug
                ] : null,
                'tags' => array_map(function($tag) {
                    return ['name' => $tag->name, 'slug' => $tag->slug];
                }, $post->tags),
                'thumbnail_url' => $post->thumbnail ? $post->thumbnail->filepath : null,
                'view_count' => (int)$post->view_count,
                'published_at' => $post->published_at,
                'seo' => $post->postSeo ? [
                    'title' => $post->postSeo->title,
                    'description' => $post->postSeo->description,
                    'keywords' => $post->postSeo->keywords,
                ] : [
                    'title' => $post->title,
                    'description' => mb_strimwidth(strip_tags($post->content), 0, 160, '...'),
                    'keywords' => '',
                ],
            ];

            // Cache in Redis for 10 minutes
            Yii::$app->cache->set($cacheKey, $postData, 600);
        }

        // Push logging of view to background queue
        Yii::$app->queue->push(new ViewLogJob([
            'postId' => $postData['id'],
            'ipAddress' => Yii::$app->request->userIP,
            'userAgent' => Yii::$app->request->userAgent,
        ]));

        return $this->successResponse($postData);
    }

    /**
     * GET /api/categories
     * Lists all public categories.
     */
    public function actionCategories()
    {
        $cacheKey = 'public_categories_list';
        $data = Yii::$app->cache->get($cacheKey);
        
        if ($data === false) {
            $categories = \app\models\Category::find()->orderBy(['name' => SORT_ASC])->all();
            $data = [];
            foreach ($categories as $category) {
                $data[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ];
            }
            Yii::$app->cache->set($cacheKey, $data, 3600);
        }
        
        return $this->successResponse($data);
    }

    /**
     * GET /api/tags
     * Lists all public tags.
     */
    public function actionTags()
    {
        $cacheKey = 'public_tags_list';
        $data = Yii::$app->cache->get($cacheKey);
        
        if ($data === false) {
            $tags = \app\models\Tag::find()->orderBy(['name' => SORT_ASC])->all();
            $data = [];
            foreach ($tags as $tag) {
                $data[] = $tag->name;
            }
            Yii::$app->cache->set($cacheKey, $data, 3600);
        }
        
        return $this->successResponse($data);
    }
}
