<?php

namespace app\repositories;

use app\models\Post;
use app\models\Comment;
use app\models\Category;
use app\models\Tag;

class PostRepository
{
    /**
     * Fetch active public post by its slug, including category, tags, and SEO data.
     * @param string $slug
     * @return Post|null
     */
    public function findPublicActiveBySlug($slug)
    {
        return Post::find()
            ->publicActive()
            ->andWhere(['{{%posts}}.slug' => $slug])
            ->with(['category', 'tags', 'thumbnail', 'postSeo'])
            ->one();
    }

    /**
     * Fetch single post by ID (must not be soft deleted).
     * @param int $id
     * @return Post|null
     */
    public function findById($id)
    {
        return Post::find()
            ->notDeleted()
            ->andWhere(['{{%posts}}.id' => $id])
            ->one();
    }

    /**
     * Fetch nested comment tree for a specific post (only approved comments).
     * @param int $postId
     * @return array
     */
    public function findCommentsTree($postId)
    {
        $comments = Comment::find()
            ->andWhere(['post_id' => $postId, 'status' => Comment::STATUS_APPROVED])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        // Build nested tree
        $tree = [];
        $mapped = [];

        foreach ($comments as $comment) {
            $commentData = [
                'id' => $comment->id,
                'parent_id' => $comment->parent_id,
                'author_name' => $comment->author_name,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'replies' => [],
            ];
            $mapped[$comment->id] = $commentData;
        }

        foreach ($mapped as $id => &$commentNode) {
            if ($commentNode['parent_id'] === null) {
                $tree[] = &$commentNode;
            } else {
                $parentId = $commentNode['parent_id'];
                if (isset($mapped[$parentId])) {
                    $mapped[$parentId]['replies'][] = &$commentNode;
                } else {
                    // Orphaned reply, push to top level for robustness
                    $tree[] = &$commentNode;
                }
            }
        }

        return $tree;
    }
}
