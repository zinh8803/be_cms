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
        $extension = strtolower($file->extension);
        $uniqueName = md5(uniqid((string)rand(), true)) . '.' . $extension;
        $filePath = $uploadDir . '/' . $uniqueName;
 
        // 6. Save physical file to disk
        if ($file->saveAs($filePath)) {
            $finalPath = $filePath;
            $finalUrl = '/uploads/' . $uniqueName;
            $finalMime = $file->type;
            $finalSize = $file->size;
            $finalName = $file->name;

            // Automatically convert JPEG/PNG/GIF to WebP if supported by GD
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif']) && function_exists('imagewebp')) {
                $webpUniqueName = md5(uniqid((string)rand(), true)) . '.webp';
                $webpPath = $uploadDir . '/' . $webpUniqueName;
                if ($this->convertToWebp($filePath, $webpPath, 85)) {
                    // Delete original file
                    @unlink($filePath);
                    $finalPath = $webpPath;
                    $finalUrl = '/uploads/' . $webpUniqueName;
                    $finalMime = 'image/webp';
                    $finalSize = filesize($webpPath);
                    $finalName = pathinfo($file->name, PATHINFO_FILENAME) . '.webp';
                }
            }

            // 7. Save metadata in DB
            $dbFile = new File();
            $dbFile->filename = $finalName;
            $dbFile->filepath = $finalUrl; // Accessible relative URL path
            $dbFile->file_size = $finalSize;
            $dbFile->mime_type = $finalMime;
 
            if ($dbFile->save()) {
                return $dbFile;
            } else {
                @unlink($finalPath);
                throw new \Exception('Không thể ghi nhận thông tin file vào cơ sở dữ liệu.');
            }
        }
 
        throw new \Exception('Không thể lưu file lên máy chủ.');
    }

    /**
     * Converts a PNG/JPEG/GIF image to WebP format if GD library is available.
     * @param string $sourcePath Path to source file
     * @param string $destPath Path to WebP destination file
     * @param int $quality WebP quality (0-100)
     * @return bool
     */
    private function convertToWebp($sourcePath, $destPath, $quality = 80)
    {
        if (!function_exists('imagewebp')) {
            return false;
        }

        $info = getimagesize($sourcePath);
        if (!$info) {
            return false;
        }

        $mime = $info['mime'];
        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/pjpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                if ($image) {
                    imagepalettetotruecolor($image);
                    imagealphablending($image, true);
                    imagesavealpha($image, true);
                }
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $result = imagewebp($image, $destPath, $quality);
        imagedestroy($image);
        return $result;
    }
}
