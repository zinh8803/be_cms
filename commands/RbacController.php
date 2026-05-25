<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Initializes RBAC roles and assignments.
 */
class RbacController extends Controller
{
    /**
     * Initializes the RBAC system.
     * @return int
     */
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        // Clear existing RBAC rules
        $auth->removeAll();

        // 1. Create permissions
        $createPost = $auth->createPermission('createPost');
        $createPost->description = 'Create a post';
        $auth->add($createPost);

        $updatePost = $auth->createPermission('updatePost');
        $updatePost->description = 'Update a post';
        $auth->add($updatePost);

        $deletePost = $auth->createPermission('deletePost');
        $deletePost->description = 'Delete a post';
        $auth->add($deletePost);

        $manageCategories = $auth->createPermission('manageCategories');
        $manageCategories->description = 'Manage categories';
        $auth->add($manageCategories);

        $manageComments = $auth->createPermission('manageComments');
        $manageComments->description = 'Manage and moderate comments';
        $auth->add($manageComments);

        // 2. Create roles
        // Role: user (reader)
        $user = $auth->createRole('user');
        $user->description = 'Regular reader';
        $auth->add($user);

        // Role: editor
        $editor = $auth->createRole('editor');
        $editor->description = 'Content editor';
        $auth->add($editor);
        $auth->addChild($editor, $createPost);
        $auth->addChild($editor, $updatePost); // Specific ownership check done in logic

        // Role: admin
        $admin = $auth->createRole('admin');
        $admin->description = 'System administrator';
        $auth->add($admin);
        $auth->addChild($admin, $editor);
        $auth->addChild($admin, $deletePost);
        $auth->addChild($admin, $manageCategories);
        $auth->addChild($admin, $manageComments);

        // 3. Assign roles to seeded users (using database IDs from user migration)
        // User 1 is 'admin'
        $auth->assign($admin, 1);
        // User 2 is 'editor'
        $auth->assign($editor, 2);
        // User 3 is 'user'
        $auth->assign($user, 3);

        $this->stdout("RBAC structure initialized successfully.\n");
        return ExitCode::OK;
    }
}
