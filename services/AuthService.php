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

                $refreshTokenVal = $this->generateRefreshToken($user);

                return [
                    'access_token' => $user->access_token,
                    'refresh_token' => $refreshTokenVal,
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
                
                $refreshTokenVal = $this->generateRefreshToken($user);
                $transaction->commit();

                return [
                    'access_token' => $user->access_token,
                    'refresh_token' => $refreshTokenVal,
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

    /**
     * Generates a new refresh token for a user.
     * @param User $user
     * @return string
     * @throws \Exception
     */
    private function generateRefreshToken($user)
    {
        // Clean up expired tokens for this user first
        \app\models\RefreshToken::deleteAll([
            'and',
            ['user_id' => $user->id],
            ['<', 'expires_at', time()]
        ]);

        $token = Yii::$app->security->generateRandomString() . '_' . time();
        $expire = Yii::$app->params['user.refreshTokenExpire'] ?? 2592000;

        $refreshToken = new \app\models\RefreshToken();
        $refreshToken->user_id = $user->id;
        $refreshToken->token = $token;
        $refreshToken->expires_at = time() + $expire;
        $refreshToken->created_at = time();

        if ($refreshToken->save()) {
            return $token;
        }
        throw new \Exception('Không thể tạo refresh token.');
    }

    /**
     * Refreshes access token using a valid refresh token.
     * @param string $refreshTokenVal
     * @return array|false
     */
    public function refreshAccessToken($refreshTokenVal)
    {
        $refreshToken = \app\models\RefreshToken::findOne(['token' => $refreshTokenVal]);
        if (!$refreshToken) {
            return false;
        }

        if ($refreshToken->expires_at < time()) {
            $refreshToken->delete();
            return false;
        }

        $user = $refreshToken->user;
        if (!$user || $user->status !== User::STATUS_ACTIVE) {
            return false;
        }

        // Generate new access token
        $user->generateAccessToken();
        if ($user->save(false)) {
            return [
                'access_token' => $user->access_token,
                'refresh_token' => $refreshTokenVal,
            ];
        }

        return false;
    }
}
