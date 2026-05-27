<?php
 
use yii\db\Migration;
 
class m260527_083253_create_post_slug_history extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post_slug_history}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'old_slug' => $this->string(255)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            '{{%idx-post_slug_history-old_slug}}',
            '{{%post_slug_history}}',
            'old_slug'
        );

        $this->addForeignKey(
            '{{%fk-post_slug_history-post_id}}',
            '{{%post_slug_history}}',
            'post_id',
            '{{%posts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-post_slug_history-post_id}}', '{{%post_slug_history}}');
        $this->dropTable('{{%post_slug_history}}');
    }
}

