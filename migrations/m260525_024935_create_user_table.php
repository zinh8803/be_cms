<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user}}`.
 */
class m260525_024935_create_user_table extends Migration
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

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'email' => $this->string()->notNull()->unique(),
            'password_hash' => $this->string()->notNull(),
            'auth_key' => $this->string(32)->notNull(),
            'access_token' => $this->string(255)->unique()->defaultValue(null),
            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $now = time();
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $editorPassword = password_hash('editor123', PASSWORD_DEFAULT);
        $userPassword = password_hash('user123', PASSWORD_DEFAULT);

        $this->insert('{{%user}}', [
            'username' => 'admin',
            'email' => 'admin@cms.com',
            'password_hash' => $adminPassword,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'access_token' => 'admin_token_123',
            'status' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insert('{{%user}}', [
            'username' => 'editor',
            'email' => 'editor@cms.com',
            'password_hash' => $editorPassword,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'access_token' => 'editor_token_123',
            'status' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->insert('{{%user}}', [
            'username' => 'user',
            'email' => 'user@cms.com',
            'password_hash' => $userPassword,
            'auth_key' => Yii::$app->security->generateRandomString(),
            'access_token' => 'user_token_123',
            'status' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user}}');
    }
}

