<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%refresh_token}}`.
 */
class m260526_083253_create_refresh_token_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%refresh_token}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->string(255)->notNull()->unique(),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex(
            'idx-refresh_token-token',
            '{{%refresh_token}}',
            'token'
        );

        $this->addForeignKey(
            'fk-refresh_token-user_id',
            '{{%refresh_token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-refresh_token-user_id', '{{%refresh_token}}');
        $this->dropTable('{{%refresh_token}}');
    }
}
