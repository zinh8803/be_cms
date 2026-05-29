<?php

namespace app\controllers;

use Yii;
use app\models\Post;
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

        $this->setCacheHeaders(60);
        return $this->successResponse($data);
    }

    /**
     * Set public cache headers for GET responses.
     * Tells browser + CDN to cache for $seconds seconds.
     */
    private function setCacheHeaders(int $seconds = 60)
    {
        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', "public, max-age={$seconds}, stale-while-revalidate=30");
        $response->headers->set('Vary', 'Accept-Encoding');
    }

    /**
     * GET /api/posts/<slug>
     * Fetch detail of post (cached, increments views via queue).
     */
    public function actionView($slug)
    {
        $post = $this->_postRepository->findPublicActiveBySlug($slug);
        if (!$post) {
            // Check slug history!
            $history = (new \yii\db\Query())
                ->select(['post_id'])
                ->from('{{%post_slug_history}}')
                ->where(['old_slug' => $slug])
                ->one();

            if ($history) {
                $realPost = Post::find()
                    ->publicActive()
                    ->andWhere(['{{%posts}}.id' => $history['post_id']])
                    ->one();

                if ($realPost) {
                    return $this->successResponse([
                        'redirect' => true,
                        'new_slug' => $realPost->slug,
                    ], 'Bài viết đã được chuyển hướng.');
                }
            }

            return $this->errorResponse(404, 'Bài viết không tồn tại hoặc đã được gỡ xuống.');
        }

        // Fetch related posts (same category, active, limit 3)
        $related = [];
        if ($post->category_id) {
            $relatedModels = Post::find()
                ->publicActive()
                ->andWhere(['category_id' => $post->category_id])
                ->andWhere(['not', ['{{%posts}}.id' => $post->id]])
                ->orderBy(['published_at' => SORT_DESC])
                ->limit(3)
                ->all();
            foreach ($relatedModels as $r) {
                $related[] = [
                    'title' => $r->title,
                    'title_en' => $r->title_en,
                    'slug' => $r->slug,
                    'thumbnail_url' => $r->thumbnail ? $r->thumbnail->filepath : null,
                    'published_at' => $r->published_at,
                ];
            }
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
            'updated_at' => $post->updated_at,
            'related' => $related,
            'seo' => [
                'title' => ($post->postSeo && $post->postSeo->title) ? $post->postSeo->title : $post->title,
                'title_en' => ($post->postSeo && $post->postSeo->title_en) ? $post->postSeo->title_en : $post->title_en,
                'description' => ($post->postSeo && $post->postSeo->description) ? $post->postSeo->description : mb_strimwidth(strip_tags($post->content), 0, 160, '...'),
                'description_en' => ($post->postSeo && $post->postSeo->description_en) ? $post->postSeo->description_en : ($post->content_en ? mb_strimwidth(strip_tags($post->content_en), 0, 160, '...') : null),
                'keywords' => ($post->postSeo && $post->postSeo->keywords) ? $post->postSeo->keywords : '',
                'keywords_en' => ($post->postSeo && $post->postSeo->keywords_en) ? $post->postSeo->keywords_en : '',
            ],
        ];

        // Push logging of view to background queue
        Yii::$app->queue->push(new ViewLogJob([
            'postId'    => $postData['id'],
            'ipAddress' => Yii::$app->request->userIP,
            'userAgent' => Yii::$app->request->userAgent,
            'referrer'  => Yii::$app->request->getReferrer(),
        ]));

        // Cache detail for a short period; rely on CDN / browser cache
        $this->setCacheHeaders(30);
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

        $this->setCacheHeaders(300);
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

        $this->setCacheHeaders(300);
        return $this->successResponse($data);
    }

    /**
     * GET /api/posts/suggestions
     * Returns matching suggestions for search queries.
     */
    public function actionSuggestions()
    {
        $q = Yii::$app->request->getQueryParam('q');
        if (empty($q) || mb_strlen($q) < 2) {
            return $this->successResponse([]);
        }

        // Search active public posts — only select columns needed for suggestions
        $posts = Post::find()
            ->select(['{{%posts}}.id', '{{%posts}}.title', '{{%posts}}.title_en', '{{%posts}}.slug'])
            ->publicActive()
            ->andWhere(['or',
                ['like', '{{%posts}}.title', $q],
                ['like', '{{%posts}}.title_en', $q]
            ])
            ->orderBy(['published_at' => SORT_DESC])
            ->limit(8)
            ->asArray()
            ->all();

        $suggestions = array_map(function($post) {
            return [
                'id'       => (int)$post['id'],
                'title'    => $post['title'],
                'title_en' => $post['title_en'],
                'slug'     => $post['slug'],
            ];
        }, $posts);

        // Very short cache so it stays fresh
        $this->setCacheHeaders(10);
        return $this->successResponse($suggestions);
    }
}
