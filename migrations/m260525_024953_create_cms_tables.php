<?php

use yii\db\Migration;

class m260525_024953_create_cms_tables extends Migration
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

        // 1. Categories
        $this->createTable('{{%categories}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'slug' => $this->string()->notNull()->unique(),
            'description' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // 2. Files
        $this->createTable('{{%files}}', [
            'id' => $this->primaryKey(),
            'filename' => $this->string()->notNull(),
            'filepath' => $this->string()->notNull(),
            'file_size' => $this->integer()->notNull(),
            'mime_type' => $this->string(100)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // 3. Posts
        $this->createTable('{{%posts}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'slug' => $this->string()->notNull()->unique(),
            'content' => $this->text()->notNull(), // text (supports long content)
            'category_id' => $this->integer()->notNull(),
            'author_id' => $this->integer()->notNull(),
            'thumbnail_id' => $this->integer()->defaultValue(null),
            'status' => $this->string(50)->defaultValue('draft'),
            'visibility' => $this->string(50)->defaultValue('public'),
            'view_count' => $this->integer()->defaultValue(0),
            'published_at' => $this->integer()->defaultValue(null),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'deleted_at' => $this->integer()->defaultValue(null),
        ], $tableOptions);

        // Indexes & FKs for posts
        $this->createIndex('idx-posts-category_id', '{{%posts}}', 'category_id');
        $this->addForeignKey('fk-posts-category_id', '{{%posts}}', 'category_id', '{{%categories}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createIndex('idx-posts-author_id', '{{%posts}}', 'author_id');
        $this->addForeignKey('fk-posts-author_id', '{{%posts}}', 'author_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('idx-posts-thumbnail_id', '{{%posts}}', 'thumbnail_id');
        $this->addForeignKey('fk-posts-thumbnail_id', '{{%posts}}', 'thumbnail_id', '{{%files}}', 'id', 'SET NULL', 'CASCADE');

        // 4. Tags
        $this->createTable('{{%tags}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull()->unique(),
            'slug' => $this->string()->notNull()->unique(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        // 5. Post Tags (Junction Table)
        $this->createTable('{{%post_tags}}', [
            'post_id' => $this->integer()->notNull(),
            'tag_id' => $this->integer()->notNull(),
            'PRIMARY KEY(post_id, tag_id)',
        ], $tableOptions);

        $this->createIndex('idx-post_tags-post_id', '{{%post_tags}}', 'post_id');
        $this->addForeignKey('fk-post_tags-post_id', '{{%post_tags}}', 'post_id', '{{%posts}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('idx-post_tags-tag_id', '{{%post_tags}}', 'tag_id');
        $this->addForeignKey('fk-post_tags-tag_id', '{{%post_tags}}', 'tag_id', '{{%tags}}', 'id', 'CASCADE', 'CASCADE');

        // 6. Post SEO (One-to-One)
        $this->createTable('{{%post_seo}}', [
            'post_id' => $this->integer()->notNull(),
            'title' => $this->string(),
            'description' => $this->text(),
            'keywords' => $this->string(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'PRIMARY KEY(post_id)',
        ], $tableOptions);

        $this->addForeignKey('fk-post_seo-post_id', '{{%post_seo}}', 'post_id', '{{%posts}}', 'id', 'CASCADE', 'CASCADE');

        // 7. Post Views (Log table)
        $this->createTable('{{%post_views}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'ip_address' => $this->string(45),
            'user_agent' => $this->text(),
            'viewed_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-post_views-post_id', '{{%post_views}}', 'post_id');
        $this->addForeignKey('fk-post_views-post_id', '{{%post_views}}', 'post_id', '{{%posts}}', 'id', 'CASCADE', 'CASCADE');

        // 8. Comments (Self-referencing parent_id for nested structure)
        $this->createTable('{{%comments}}', [
            'id' => $this->primaryKey(),
            'post_id' => $this->integer()->notNull(),
            'parent_id' => $this->integer()->defaultValue(null),
            'author_name' => $this->string()->notNull(),
            'author_email' => $this->string()->notNull(),
            'content' => $this->text()->notNull(),
            'status' => $this->string(50)->defaultValue('pending'),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-comments-post_id', '{{%comments}}', 'post_id');
        $this->addForeignKey('fk-comments-post_id', '{{%comments}}', 'post_id', '{{%posts}}', 'id', 'CASCADE', 'CASCADE');

        $this->createIndex('idx-comments-parent_id', '{{%comments}}', 'parent_id');
        $this->addForeignKey('fk-comments-parent_id', '{{%comments}}', 'parent_id', '{{%comments}}', 'id', 'CASCADE', 'CASCADE');

        // 9. Admin Logs (Audit logging)
        $this->createTable('{{%admin_logs}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'action' => $this->string()->notNull(),
            'details' => $this->text(),
            'created_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->createIndex('idx-admin_logs-user_id', '{{%admin_logs}}', 'user_id');
        $this->addForeignKey('fk-admin_logs-user_id', '{{%admin_logs}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        // Seed default categories & tags
        $now = time();
        $this->batchInsert('{{%categories}}', ['name', 'slug', 'description', 'created_at', 'updated_at'], [
            ['Công nghệ', 'cong-nghe', 'Các bài viết về công nghệ lập trình, phần mềm, phần cứng.', $now, $now],
            ['Đời sống', 'doi-song', 'Các góc nhìn cuộc sống, chia sẻ kinh nghiệm, phong cách sống.', $now, $now],
            ['Học tập', 'hoc-tap', 'Tài liệu học tập, hướng dẫn lập trình cho người mới bắt đầu.', $now, $now],
        ]);

        $this->batchInsert('{{%tags}}', ['name', 'slug', 'created_at', 'updated_at'], [
            ['VueJS', 'vuejs', $now, $now],
            ['Yii2', 'yii2', $now, $now],
            ['Javascript', 'javascript', $now, $now],
            ['PHP', 'php', $now, $now],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%admin_logs}}');
        $this->dropTable('{{%comments}}');
        $this->dropTable('{{%post_views}}');
        $this->dropTable('{{%post_seo}}');
        $this->dropTable('{{%post_tags}}');
        $this->dropTable('{{%tags}}');
        $this->dropTable('{{%posts}}');
        $this->dropTable('{{%files}}');
        $this->dropTable('{{%categories}}');
    }
}

