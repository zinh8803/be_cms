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
    public $referrer;

    /**
     * Executes the job.
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        $cooldown = 300; // 5 minutes cooldown for F5/reload spam
        $isSpam = 0;

        if ($this->ipAddress) {
            $lastView = PostView::find()
                ->andWhere(['post_id' => $this->postId, 'ip_address' => $this->ipAddress])
                ->orderBy(['viewed_at' => SORT_DESC])
                ->one();
            
            if ($lastView && (time() - $lastView->viewed_at) < $cooldown) {
                $isSpam = 1;
            }
        }

        // 1. Create a view log record
        $viewLog = new PostView();
        $viewLog->post_id = $this->postId;
        $viewLog->ip_address = $this->ipAddress;
        $viewLog->user_agent = $this->userAgent;
        $viewLog->referrer = $this->referrer;
        $viewLog->is_spam = $isSpam;
        
        if (!$viewLog->save()) {
            Yii::error("Không thể ghi nhận lượt xem trong Queue: " . json_encode($viewLog->getErrors()));
        }

        // 2. Increment post view count in database ONLY if it is not F5 spam
        if ($isSpam === 0) {
            Post::updateAllCounters(['view_count' => 1], ['id' => $this->postId]);
        }
    }
}
