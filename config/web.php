<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'on beforeRequest' => function () {
        $request = Yii::$app->request;
        $response = Yii::$app->response;
        
        $origin = $request->headers->get('Origin');
        if ($origin === 'http://localhost:5173') {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Max-Age', '3600');
        }
        
        if ($request->isOptions) {
            $response->statusCode = 200;
            Yii::$app->end();
        }
    },
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'Xuuj_juVLa6xkIeo-ejSqv0inte9Hy8z',
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
        ],
        'redis' => [
            'class' => \yii\redis\Connection::class,
            'hostname' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => 6379,
            'database' => 0,
        ],
        'cache' => [
            'class' => \yii\redis\Cache::class,
            'redis' => 'redis',
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis',
            'channel' => 'cms_queue',
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST auth/login' => 'auth/login',
                'POST auth/register' => 'auth/register',
                'GET auth/me' => 'auth/me',
                'GET posts' => 'post/index',
                'GET posts/<slug:[a-zA-Z0-9\-]+>' => 'post/view',
                'GET post/<slug:[a-zA-Z0-9\-]+>' => 'post/view',
                'POST posts/<id:\d+>/comments' => 'comment/create',
                'POST post/<id:\d+>/comments' => 'comment/create',
                'GET posts/<id:\d+>/comments' => 'comment/index',
                'GET post/<id:\d+>/comments' => 'comment/index',
                'POST upload' => 'upload/create',
                
                'GET admin/posts' => 'admin/post-index',
                'GET admin/posts/<id:\d+>' => 'admin/post-view',
                'POST admin/posts' => 'admin/post-create',
                'PUT admin/posts/<id:\d+>' => 'admin/post-update',
                'DELETE admin/posts/<id:\d+>' => 'admin/post-delete',
                'GET admin/categories' => 'admin/category-index',
                'POST admin/categories' => 'admin/category-create',
                'PUT admin/categories/<id:\d+>' => 'admin/category-update',
                'DELETE admin/categories/<id:\d+>' => 'admin/category-delete',
                'GET admin/tags' => 'admin/tag-index',
                'GET admin/tags-full' => 'admin/tag-full-index',
                'POST admin/tags' => 'admin/tag-create',
                'PUT admin/tags/<id:\d+>' => 'admin/tag-update',
                'DELETE admin/tags/<id:\d+>' => 'admin/tag-delete',
                'GET admin/comments' => 'admin/comment-index',
                'PUT admin/comments/<id:\d+>' => 'admin/comment-update',
                'GET admin/logs' => 'admin/log-index',
                'GET admin/files' => 'admin/file-index',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
