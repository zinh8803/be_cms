<?php

namespace app\services;

use Yii;
use app\models\User;
use app\models\LoginForm;
use app\models\RegisterForm;

class AuthService
{
    /**
     * Authenticates an admin/editor and issues an access token.
     * @param array $loginData
     * @return array|false
     */
    public function login($loginData)
    {
        $model = new LoginForm();
        $model->attributes = $loginData;

        if ($model->validate()) {
            $user = $model->getUser();
            $user->generateAccessToken();
            if ($user->save(false)) {
                // Get role
                $roles = Yii::$app->authManager->getRolesByUser($user->id);
                $roleName = !empty($roles) ? key($roles) : 'user';

                return [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => $roleName,
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * Retrieves profile and permissions for a user.
     * @param User $user
     * @return array
     */
    public function getUserInfo($user)
    {
        $roles = Yii::$app->authManager->getRolesByUser($user->id);
        $roleName = !empty($roles) ? key($roles) : 'user';

        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $roleName,
        ];
    }

    /**
     * Registers a new user and issues access token.
     * @param RegisterForm $form
     * @return array|false
     * @throws \Exception
     */
    public function register(RegisterForm $form)
    {
        $user = new User();
        $user->username = $form->username;
        $user->email = $form->email;
        $user->setPassword($form->password);
        $user->generateAuthKey();
        $user->generateAccessToken();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($user->save(false)) {
                // Assign role
                $auth = Yii::$app->authManager;
                $userRole = $auth->getRole('user');
                if ($userRole) {
                    $auth->assign($userRole, $user->id);
                }
                $transaction->commit();

                return [
                    'access_token' => $user->access_token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'role' => 'user',
                    ]
                ];
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return false;
    }
}
