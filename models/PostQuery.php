<?php

namespace app\models;

use yii\db\ActiveQuery;

/**
 * Custom ActiveQuery class for the Post model to handle soft deletes and publication scopes.
 */
class PostQuery extends ActiveQuery
{
    /**
     * Exclude soft-deleted posts.
     * @return $this
     */
    public function notDeleted()
    {
        return $this->andWhere(['{{%posts}}.deleted_at' => null]);
    }

    /**
     * Filter only published posts.
     * @return $this
     */
    public function published()
    {
        return $this->andWhere(['{{%posts}}.status' => Post::STATUS_PUBLISHED]);
    }

    /**
     * Filter only public visibility posts.
     * @return $this
     */
    public function publicVisibility()
    {
        return $this->andWhere(['{{%posts}}.visibility' => Post::VISIBILITY_PUBLIC]);
    }

    /**
     * Helper to get active public posts for visitor frontend.
     * @return $this
     */
    public function publicActive()
    {
        return $this->notDeleted()->published()->publicVisibility();
    }
}
