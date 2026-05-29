<?php

use yii\db\Migration;

class m260529_022254_add_translations_to_post_seo extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%post_seo}}', 'title_en', $this->string()->after('title'));
        $this->addColumn('{{%post_seo}}', 'description_en', $this->text()->after('description'));
        $this->addColumn('{{%post_seo}}', 'keywords_en', $this->string()->after('keywords'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%post_seo}}', 'title_en');
        $this->dropColumn('{{%post_seo}}', 'description_en');
        $this->dropColumn('{{%post_seo}}', 'keywords_en');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260529_022254_add_translations_to_post_seo cannot be reverted.\n";

        return false;
    }
    */
}
