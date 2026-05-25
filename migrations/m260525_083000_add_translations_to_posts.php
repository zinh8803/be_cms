<?php

use yii\db\Migration;

/**
 * Class m260525_083000_add_translations_to_posts
 */
class m260525_083000_add_translations_to_posts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%posts}}', 'title_en', $this->string()->after('title'));
        $this->addColumn('{{%posts}}', 'content_en', $this->text()->after('content'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%posts}}', 'title_en');
        $this->dropColumn('{{%posts}}', 'content_en');
    }
}
