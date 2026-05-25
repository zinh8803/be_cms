<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\Cors;

/**
 * Base ApiController for all CMS REST APIs.
 */
class ApiController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public $enableCsrfValidation = false;

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // 1. Configure CORS dynamically from environment variables
        $frontendUrls = $_ENV['FRONTEND_URL'] ?? getenv('FRONTEND_URL') ?: 'http://localhost:5173';
        $origins = array_map('trim', explode(',', $frontendUrls));

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => $origins,
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 3600,
            ],
        ];

        return $behaviors;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        // For pre-flight OPTIONS requests, respond with 200 OK immediately to satisfy CORS checks
        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 200;
            Yii::$app->end();
        }

        if (parent::beforeAction($action)) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return true;
        }

        return false;
    }

    /**
     * Helper to return standard error response.
     * @param int $statusCode
     * @param string $message
     * @param array $errors
     * @return array
     */
    protected function errorResponse($statusCode, $message, $errors = [])
    {
        Yii::$app->response->statusCode = $statusCode;
        return [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ];
    }

    /**
     * Helper to return standard success response.
     * @param mixed $data
     * @param string $message
     * @return array
     */
    protected function successResponse($data = null, $message = 'Success')
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];
    }
}
