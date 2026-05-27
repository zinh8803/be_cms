<?php

use yii\db\Migration;

class m260527_084659_add_referrer_and_is_spam_to_post_views extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%post_views}}', 'referrer', $this->text());
        $this->addColumn('{{%post_views}}', 'is_spam', $this->smallInteger()->defaultValue(0));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%post_views}}', 'is_spam');
        $this->dropColumn('{{%post_views}}', 'referrer');
    }
}

