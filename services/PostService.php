<?php

namespace app\services;

use Yii;
use app\models\Post;
use app\models\PostForm;
use app\models\Tag;
use app\models\PostSeo;

class PostService
{
    private $_auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->_auditLog = $auditLog;
    }

    /**
     * Create a new post, tags, and SEO records inside a transaction.
     * @param int $authorId
     * @param PostForm $form
     * @return Post|false
     */
    public function createPost($authorId, PostForm $form)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $post = new Post();
            $post->title = $form->title;
            $post->title_en = $form->title_en;
            $post->content = $form->content;
            $post->content_en = $form->content_en;
            $post->category_id = $form->category_id;
            $post->thumbnail_id = $form->thumbnail_id;
            $post->status = $form->status;
            $post->visibility = $form->visibility;
            $post->author_id = $authorId;
            $post->slug = PostForm::slugify($form->title);

            if ($post->status === Post::STATUS_PUBLISHED) {
                $post->published_at = time();
            }

            if (!$post->save()) {
                $form->addErrors($post->getErrors());
                throw new \Exception('Không thể lưu thông tin bài viết.');
            }

            // Sync Tags
            $this->syncTags($post, $form->tags);

            // Sync SEO
            $seo = new PostSeo();
            $seo->post_id = $post->id;
            $seo->title = $form->seo_title ? $form->seo_title : $post->title;
            $seo->title_en = $form->seo_title_en ? $form->seo_title_en : $post->title_en;
            $seo->description = $form->seo_description;
            $seo->description_en = $form->seo_description_en;
            $seo->keywords = $form->seo_keywords;
            $seo->keywords_en = $form->seo_keywords_en;
            if (!$seo->save()) {
                $form->addErrors($seo->getErrors());
                throw new \Exception('Không thể lưu thông tin SEO.');
            }

            // Log action
            $this->_auditLog->log($authorId, 'TẠO_BÀI_VIẾT', [
                'post_id' => $post->id,
                'title' => $post->title
            ]);

            $transaction->commit();
            
            // Clear caching
            Yii::$app->cache->flush();

            return $post;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Lỗi tạo bài viết: " . $e->getMessage());
            if (empty($form->getErrors())) {
                $form->addError('title', $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Update a post, tags, and SEO records inside a transaction.
     * @param int $authorId
     * @param Post $post
     * @param PostForm $form
     * @return Post|false
     */
    public function updatePost($authorId, Post $post, PostForm $form)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $post->title = $form->title;
            $post->title_en = $form->title_en;
            $post->content = $form->content;
            $post->content_en = $form->content_en;
            $post->category_id = $form->category_id;
            $post->thumbnail_id = $form->thumbnail_id;
            
            // Handle published timestamp changes
            if ($form->status === Post::STATUS_PUBLISHED && $post->status !== Post::STATUS_PUBLISHED) {
                $post->published_at = time();
            } elseif ($form->status === Post::STATUS_DRAFT) {
                $post->published_at = null;
            }
            
            $oldSlug = $post->getOldAttribute('slug');
            $newSlug = PostForm::slugify($form->title);
            
            $post->status = $form->status;
            $post->visibility = $form->visibility;
            $post->slug = $newSlug;

            if ($oldSlug && $oldSlug !== $newSlug) {
                // Insert old slug to history if not exists
                $exists = (new \yii\db\Query())
                    ->from('{{%post_slug_history}}')
                    ->where(['post_id' => $post->id, 'old_slug' => $oldSlug])
                    ->exists();
                if (!$exists) {
                    Yii::$app->db->createCommand()->insert('{{%post_slug_history}}', [
                        'post_id' => $post->id,
                        'old_slug' => $oldSlug,
                        'created_at' => time(),
                    ])->execute();
                }
            }

            if (!$post->save()) {
                $form->addErrors($post->getErrors());
                throw new \Exception('Không thể cập nhật bài viết.');
            }

            // Sync Tags
            $this->syncTags($post, $form->tags);

            // Sync SEO
            $seo = PostSeo::findOne($post->id);
            if (!$seo) {
                $seo = new PostSeo();
                $seo->post_id = $post->id;
            }
            $seo->title = $form->seo_title ? $form->seo_title : $post->title;
            $seo->title_en = $form->seo_title_en ? $form->seo_title_en : $post->title_en;
            $seo->description = $form->seo_description;
            $seo->description_en = $form->seo_description_en;
            $seo->keywords = $form->seo_keywords;
            $seo->keywords_en = $form->seo_keywords_en;
            if (!$seo->save()) {
                $form->addErrors($seo->getErrors());
                throw new \Exception('Không thể lưu thông tin SEO.');
            }

            // Log action
            $this->_auditLog->log($authorId, 'CẬP_NHẬT_BÀI_VIẾT', [
                'post_id' => $post->id,
                'title' => $post->title
            ]);

            $transaction->commit();
            
            // Clear caching
            Yii::$app->cache->flush();

            return $post;
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::error("Lỗi cập nhật bài viết: " . $e->getMessage());
            if (empty($form->getErrors())) {
                $form->addError('title', $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Soft delete a post.
     * @param int $authorId
     * @param Post $post
     * @return bool
     */
    public function softDeletePost($authorId, Post $post)
    {
        if ($post->softDelete()) {
            $this->_auditLog->log($authorId, 'XÓA_BÀI_VIẾT', [
                'post_id' => $post->id,
                'title' => $post->title
            ]);
            Yii::$app->cache->flush();
            return true;
        }
        return false;
    }

    /**
     * Sync tags for a post (delete old associations and link/create new ones).
     * @param Post $post
     * @param array $tagNames
     */
    private function syncTags(Post $post, $tagNames)
    {
        // Unlink all current tags first
        Yii::$app->db->createCommand()
            ->delete('{{%post_tags}}', ['post_id' => $post->id])
            ->execute();

        if (empty($tagNames) || !is_array($tagNames)) {
            return;
        }

        foreach ($tagNames as $name) {
            $name = trim($name);
            if (empty($name)) {
                continue;
            }

            // Find or create tag
            $tag = Tag::findOne(['name' => $name]);
            if (!$tag) {
                $tag = new Tag();
                $tag->name = $name;
                $tag->slug = PostForm::slugify($name);
                if (!$tag->save()) {
                    Yii::warning("Không thể lưu thẻ tag: " . json_encode($tag->getErrors()));
                    continue;
                }
            }

            // Insert into junction table
            Yii::$app->db->createCommand()
                ->insert('{{%post_tags}}', [
                    'post_id' => $post->id,
                    'tag_id' => $tag->id
                ])->execute();
        }
    }
}
