<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * PostForm is the model behind creating/updating posts.
 */
class PostForm extends Model
{
    public $id;
    public $title;
    public $title_en;
    public $content;
    public $content_en;
    public $category_id;
    public $thumbnail_id;
    public $status = Post::STATUS_DRAFT;
    public $visibility = Post::VISIBILITY_PUBLIC;
    public $tags = []; // array of tag strings
    public $seo_title;
    public $seo_title_en;
    public $seo_description;
    public $seo_description_en;
    public $seo_keywords;
    public $seo_keywords_en;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'content', 'category_id'], 'required', 'message' => '{attribute} không được để trống.'],
            [['category_id', 'thumbnail_id'], 'integer'],
            [['content', 'content_en', 'seo_title', 'seo_title_en', 'seo_description', 'seo_description_en', 'seo_keywords', 'seo_keywords_en'], 'string'],
            [['title_en'], 'string', 'max' => 255],
            [['status', 'visibility'], 'string', 'max' => 50],
            ['category_id', 'exist', 'targetClass' => Category::class, 'targetAttribute' => 'id', 'message' => 'Danh mục không tồn tại.'],
            ['thumbnail_id', 'exist', 'targetClass' => File::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true, 'message' => 'File thumbnail không tồn tại.'],
            ['tags', 'safe'],
            ['title', 'validateSlug'],
        ];
    }

    /**
     * Validates if the slug generated from title is unique.
     */
    public function validateSlug($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $slug = self::slugify($this->title);
            $query = Post::find()->andWhere(['slug' => $slug]);
            if ($this->id) {
                $query->andWhere(['not', ['id' => $this->id]]);
            }
            if ($query->exists()) {
                $this->addError($attribute, 'Tiêu đề này tạo ra slug đã tồn tại trong hệ thống. Vui lòng chọn tiêu đề khác.');
            }
        }
    }

    /**
     * Utility method to generate clean slugs, specifically handling Vietnamese characters.
     * @param string $str
     * @return string
     */
    public static function slugify($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|U|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ẹ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        
        $str = preg_replace("/[^A-Za-z0-9 ]/", '', $str);
        $str = preg_replace("/\s+/", '-', $str);
        $str = strtolower(trim($str, '-'));
        
        return $str ? $str : 'n-a';
    }

    /**
     * Get label names.
     */
    public function attributeLabels()
    {
        return [
            'title' => 'Tiêu đề bài viết',
            'content' => 'Nội dung',
            'category_id' => 'Danh mục',
            'thumbnail_id' => 'Ảnh đại diện',
            'status' => 'Trạng thái',
            'visibility' => 'Chế độ hiển thị',
            'tags' => 'Thẻ bài viết',
        ];
    }
}
