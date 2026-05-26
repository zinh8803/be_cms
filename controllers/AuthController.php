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
}
