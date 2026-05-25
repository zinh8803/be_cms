<?php

namespace app\controllers;

use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use app\services\UploadService;

/**
 * UploadController manages file uploads.
 */
class UploadController extends ApiController
{
    private $_uploadService;

    public function __construct($id, $module, UploadService $uploadService, $config = [])
    {
        $this->_uploadService = $uploadService;
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // 1. Authenticate with HttpBearerAuth
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * POST /api/upload
     * Uploads a file.
     */
    public function actionCreate()
    {
        $uploadedFile = UploadedFile::getInstanceByName('file');
        if (!$uploadedFile) {
            return $this->errorResponse(400, 'Không tìm thấy file tải lên. Vui lòng gửi file với khoá tên là "file".');
        }

        try {
            $fileModel = $this->_uploadService->upload($uploadedFile);
            if ($fileModel) {
                return $this->successResponse([
                    'id' => $fileModel->id,
                    'filename' => $fileModel->filename,
                    'url' => $fileModel->filepath,
                ], 'Tải ảnh lên thành công.');
            }
        } catch (\Exception $e) {
            return $this->errorResponse(422, $e->getMessage());
        }

        return $this->errorResponse(500, 'Có lỗi xảy ra khi xử lý file.');
    }
}
