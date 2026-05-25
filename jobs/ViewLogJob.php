<?php

namespace app\jobs;

use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;
use app\models\PostView;
use app\models\Post;

/**
 * Job to log page views and increment view count asynchronously.
 */
class ViewLogJob extends BaseObject implements JobInterface
{
    public $postId;
    public $ipAddress;
    public $userAgent;

    /**
     * Executes the job.
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        // 1. Create a view log record
        $viewLog = new PostView();
        $viewLog->post_id = $this->postId;
        $viewLog->ip_address = $this->ipAddress;
        $viewLog->user_agent = $this->userAgent;
        if (!$viewLog->save()) {
            Yii::error("Không thể ghi nhận lượt xem trong Queue: " . json_encode($viewLog->getErrors()));
        }

        // 2. Increment post view count in database
        Post::updateAllCounters(['view_count' => 1], ['id' => $this->postId]);

        // 3. Clear cache of this specific post so frontend displays updated view count
        $post = Post::findOne($this->postId);
        if ($post) {
            Yii::$app->cache->delete('post_detail_' . $post->slug);
        }
    }
}
