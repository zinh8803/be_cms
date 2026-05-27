<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * PostSearch represents the model behind the search form of `app\models\Post`.
 */
class PostSearch extends Model
{
    public $title;
    public $category_id;
    public $category_slug;
    public $tag_slug;
    public $status;
    public $visibility;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'category_slug', 'tag_slug', 'status', 'visibility'], 'safe'],
            [['category_id'], 'integer'],
        ];
    }

    /**
     * Creates data provider instance with search query applied for public visitor site.
     * Only returns published, public, and non-deleted posts.
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function searchPublic($params)
    {
        // Select only columns needed for the public post card list
        // Explicitly exclude nothing — but ensure content is loaded for summary generation
        $query = Post::find()
            ->select([
                '{{%posts}}.id',
                '{{%posts}}.title',
                '{{%posts}}.title_en',
                '{{%posts}}.slug',
                '{{%posts}}.content',     // needed for 160-char summary
                '{{%posts}}.content_en',  // needed for bilingual summary
                '{{%posts}}.category_id',
                '{{%posts}}.thumbnail_id',
                '{{%posts}}.view_count',
                '{{%posts}}.published_at',
                '{{%posts}}.status',
                '{{%posts}}.visibility',
                '{{%posts}}.deleted_at',
            ])
            ->publicActive()
            ->with(['category', 'tags', 'thumbnail']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 6,
            ],
            'sort' => [
                'defaultOrder' => [
                    'published_at' => SORT_DESC,
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params, '');

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Apply filters
        if (!empty($this->title)) {
            $query->andWhere(['or', 
                ['like', '{{%posts}}.title', $this->title],
                ['like', '{{%posts}}.content', $this->title]
            ]);
        }

        if (!empty($this->category_id)) {
            $query->andWhere(['{{%posts}}.category_id' => $this->category_id]);
        }

        if (!empty($this->category_slug)) {
            $query->joinWith('category')
                ->andWhere(['{{%categories}}.slug' => $this->category_slug]);
        }

        if (!empty($this->tag_slug)) {
            $query->joinWith('tags')
                ->andWhere(['{{%tags}}.slug' => $this->tag_slug]);
        }

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied for administrator panels.
     * Handles roles (admin sees all, editor sees own).
     *
     * @param array $params
     * @param int $userId
     * @param string $userRole
     * @return ActiveDataProvider
     */
    public function searchAdmin($params, $userId, $userRole)
    {
        // Select only columns needed for the admin post list view
        $query = Post::find()
            ->select([
                '{{%posts}}.id',
                '{{%posts}}.title',
                '{{%posts}}.title_en',
                '{{%posts}}.slug',
                '{{%posts}}.category_id',
                '{{%posts}}.author_id',
                '{{%posts}}.thumbnail_id',
                '{{%posts}}.status',
                '{{%posts}}.visibility',
                '{{%posts}}.view_count',
                '{{%posts}}.created_at',
                '{{%posts}}.deleted_at',
            ])
            ->notDeleted()
            ->with(['category', 'tags', 'thumbnail', 'author']);

        // RBAC constraint: Editor can only manage their own posts
        if ($userRole === 'editor') {
            $query->andWhere(['{{%posts}}.author_id' => $userId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params, '');

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Apply filters
        if (!empty($this->title)) {
            $query->andWhere(['like', '{{%posts}}.title', $this->title]);
        }

        if (!empty($this->category_id)) {
            $query->andWhere(['{{%posts}}.category_id' => $this->category_id]);
        }

        if (!empty($this->status)) {
            $query->andWhere(['{{%posts}}.status' => $this->status]);
        }

        if (!empty($this->visibility)) {
            $query->andWhere(['{{%posts}}.visibility' => $this->visibility]);
        }

        return $dataProvider;
    }
}
