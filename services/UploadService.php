<?php

namespace app\services;

use Yii;
use app\models\File;
use yii\web\UploadedFile;

class UploadService
{
    /**
     * Upload an image file securely.
     * @param UploadedFile $file
     * @return File|false
     * @throws \Exception
     */
    public function upload(UploadedFile $file)
    {
        // 1. Validate extension
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (!in_array(strtolower($file->extension), $allowedExtensions)) {
            throw new \Exception('Định dạng file không hợp lệ. Chỉ cho phép: ' . implode(', ', $allowedExtensions));
        }

        // 2. Validate MIME type
        $allowedMimeTypes = ['image/png', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/webp'];
        if (!in_array($file->type, $allowedMimeTypes)) {
            throw new \Exception('Loại file không hợp lệ (MIME type không khớp).');
        }

        // 3. Validate size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file->size > $maxSize) {
            throw new \Exception('Dung lượng file vượt quá giới hạn cho phép (tối đa 5MB).');
        }

        // 4. Create directory if not exists
        $uploadDir = Yii::getAlias('@app/web/uploads');
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new \Exception('Không thể tạo thư mục lưu trữ uploads.');
            }
        }

        // 5. Generate secure unique filename
        $uniqueName = md5(uniqid((string)rand(), true)) . '.' . $file->extension;
        $filePath = $uploadDir . '/' . $uniqueName;

        // 6. Save physical file to disk
        if ($file->saveAs($filePath)) {
            // 7. Save metadata in DB
            $dbFile = new File();
            $dbFile->filename = $file->name;
            $dbFile->filepath = '/uploads/' . $uniqueName; // Accessible relative URL path
            $dbFile->file_size = $file->size;
            $dbFile->mime_type = $file->type;

            if ($dbFile->save()) {
                return $dbFile;
            } else {
                @unlink($filePath);
                throw new \Exception('Không thể ghi nhận thông tin file vào cơ sở dữ liệu.');
            }
        }

        throw new \Exception('Không thể lưu file lên máy chủ.');
    }
}
