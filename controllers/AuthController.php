<?php

namespace app\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use app\services\AuthService;

/**
 * AuthController handles login and user identity actions.
 */
class AuthController extends ApiController
{
    private $_authService;

    public function __construct($id, $module, AuthService $authService, $config = [])
    {
        $this->_authService = $authService;
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // 1. Setup bearer authenticator for me and change-password actions
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'only' => ['me', 'change-password'],
        ];

        return $behaviors;
    }

    /**
     * POST /api/auth/login
     */
    public function actionLogin()
    {
        $body = Yii::$app->request->getBodyParams();
        $result = $this->_authService->login($body);

        if ($result) {
            return $this->successResponse($result, 'Đăng nhập thành công.');
        }

        return $this->errorResponse(422, 'Email hoặc mật khẩu không chính xác.');
    }

    /**
     * GET /api/auth/me
     */
    public function actionMe()
    {
        $user = Yii::$app->user->identity;
        $info = $this->_authService->getUserInfo($user);
        return $this->successResponse($info);
    }

    /**
     * POST /api/auth/register
     */
    public function actionRegister()
    {
        $body = Yii::$app->request->getBodyParams();
        $form = new \app\models\RegisterForm();
        $form->attributes = $body;

        if ($form->validate()) {
            $result = $this->_authService->register($form);
            if ($result) {
                return $this->successResponse($result, 'Đăng ký tài khoản thành công.');
            }
        }

        return $this->errorResponse(422, 'Đăng ký tài khoản không thành công.', $form->getErrors());
    }

    /**
     * POST /api/auth/change-password
     */
    public function actionChangePassword()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return $this->errorResponse(401, 'Bạn chưa đăng nhập.');
        }

        $body = Yii::$app->request->getBodyParams();
        $form = new \app\models\ChangePasswordForm($user);
        $form->attributes = $body;

        if ($form->change()) {
            return $this->successResponse(null, 'Đổi mật khẩu thành công.');
        }

        return $this->errorResponse(422, 'Đổi mật khẩu không thành công.', $form->getErrors());
    }

    /**
     * POST /api/auth/refresh-token
     */
    public function actionRefreshToken()
    {
        $body = Yii::$app->request->getBodyParams();
        $refreshTokenVal = $body['refresh_token'] ?? null;

        if (!$refreshTokenVal) {
            return $this->errorResponse(400, 'Refresh token không được để trống.');
        }

        $result = $this->_authService->refreshAccessToken($refreshTokenVal);
        if ($result) {
            return $this->successResponse($result, 'Làm mới token thành công.');
        }

        return $this->errorResponse(401, 'Refresh token không hợp lệ hoặc đã hết hạn.');
    }

    /**
     * POST /api/auth/logout
     */
    public function actionLogout()
    {
        $body = Yii::$app->request->getBodyParams();
        $refreshTokenVal = $body['refresh_token'] ?? null;
        if ($refreshTokenVal) {
            \app\models\RefreshToken::deleteAll(['token' => $refreshTokenVal]);
        }

        // Optional: clear access token if still valid / matching
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader && preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            $accessToken = $matches[1];
            $user = \app\models\User::findOne(['access_token' => $accessToken]);
            if ($user) {
                $user->access_token = null;
                $user->save(false);
            }
        }

        return $this->successResponse(null, 'Đăng xuất thành công.');
    }
}
