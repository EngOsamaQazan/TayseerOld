<?php
/**
 * SmartMediaController — Smart Document & Photo Management
 * Handles: file upload, webcam capture, AI classification, usage stats
 */

namespace backend\modules\customers\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use backend\modules\customers\components\VisionService;

class SmartMediaController extends Controller
{
    /**
     * Disable CSRF for AJAX file upload & webcam
     */
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Upload file(s) via AJAX — supports drag-and-drop and traditional upload
     * POST: file (multipart), customer_id (optional)
     * Returns: JSON with file info + AI classification
     */
    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return ['success' => false, 'error' => 'لم يتم استلام الملف'];
        }

        // Validate
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($file->type, $allowed)) {
            return ['success' => false, 'error' => 'نوع الملف غير مدعوم. المسموح: JPG, PNG, WebP, PDF'];
        }

        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->size > $maxSize) {
            return ['success' => false, 'error' => 'حجم الملف أكبر من 10MB'];
        }

        $customerId = Yii::$app->request->post('customer_id');

        try {
            // Generate unique filename
            $ext = strtolower($file->extension);
            $filename = 'doc_' . date('Ymd_His') . '_' . Yii::$app->security->generateRandomString(8) . '.' . $ext;

            // Create upload directory
            $uploadDir = Yii::getAlias('@backend/web/uploads/customers/documents');
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filePath = $uploadDir . '/' . $filename;
            $webPath = '/uploads/customers/documents/' . $filename;

            // Save file
            if (!$file->saveAs($filePath)) {
                throw new \Exception('فشل في حفظ الملف');
            }

            // Create thumbnail for images
            $thumbPath = null;
            $thumbWebPath = null;
            if (strpos($file->type, 'image/') === 0) {
                $thumbDir = $uploadDir . '/thumbs';
                if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
                $thumbFile = 'thumb_' . $filename;
                $thumbFullPath = $thumbDir . '/' . $thumbFile;
                if (VisionService::createThumbnail($filePath, $thumbFullPath)) {
                    $thumbPath = $thumbFullPath;
                    $thumbWebPath = '/uploads/customers/documents/thumbs/' . $thumbFile;
                }
            }

            // AI Classification (only for images, not PDF)
            $aiResult = null;
            $autoClassify = Yii::$app->request->post('auto_classify', '1');
            if ($autoClassify === '1' && strpos($file->type, 'image/') === 0) {
                $aiResult = VisionService::classify($filePath, $customerId ? (int)$customerId : null);
            }

            return [
                'success' => true,
                'file' => [
                    'name' => $file->name,
                    'path' => $webPath,
                    'full_path' => $filePath,
                    'thumb' => $thumbWebPath,
                    'size' => $file->size,
                    'mime' => $file->type,
                    'capture_method' => 'upload',
                ],
                'ai' => $aiResult ? [
                    'classification' => $aiResult['classification'],
                    'text_preview' => mb_substr($aiResult['text'] ?? '', 0, 200),
                    'labels' => array_slice($aiResult['labels'] ?? [], 0, 5),
                    'response_time' => $aiResult['response_time_ms'] ?? 0,
                ] : null,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Capture webcam photo
     * POST: image_data (base64 data URL), customer_id (optional), photo_type
     */
    public function actionWebcamCapture()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $imageData = Yii::$app->request->post('image_data');
        if (!$imageData) {
            return ['success' => false, 'error' => 'لم يتم استلام بيانات الصورة'];
        }

        // Validate base64 data URL
        if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imageData, $matches)) {
            return ['success' => false, 'error' => 'صيغة البيانات غير صحيحة'];
        }

        $ext = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $mimeType = 'image/' . $matches[1];

        try {
            // Decode base64
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $binaryData = base64_decode($base64Data);

            if (!$binaryData) {
                throw new \Exception('فشل في فك تشفير الصورة');
            }

            $customerId = Yii::$app->request->post('customer_id');
            $photoType = Yii::$app->request->post('photo_type', 'webcam');

            // Generate filename
            $filename = 'cam_' . date('Ymd_His') . '_' . Yii::$app->security->generateRandomString(6) . '.' . $ext;

            // Create directory
            $uploadDir = Yii::getAlias('@backend/web/uploads/customers/photos');
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filePath = $uploadDir . '/' . $filename;
            $webPath = '/uploads/customers/photos/' . $filename;

            // Save file
            file_put_contents($filePath, $binaryData);

            // Create thumbnail
            $thumbWebPath = null;
            $thumbDir = $uploadDir . '/thumbs';
            if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
            $thumbFile = 'thumb_' . $filename;
            $thumbFullPath = $thumbDir . '/' . $thumbFile;
            if (VisionService::createThumbnail($filePath, $thumbFullPath, 150, 150)) {
                $thumbWebPath = '/uploads/customers/photos/thumbs/' . $thumbFile;
            }

            // Save to database
            $fileSize = filesize($filePath);
            Yii::$app->db->createCommand()->insert('os_customer_photos', [
                'customer_id' => $customerId ?: 0,
                'photo_type' => $photoType,
                'file_path' => $webPath,
                'thumbnail_path' => $thumbWebPath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'capture_method' => 'webcam',
                'is_primary' => ($photoType === 'profile') ? 1 : 0,
                'created_by' => Yii::$app->user->id ?? null,
                'created_at' => date('Y-m-d H:i:s'),
            ])->execute();

            $photoId = Yii::$app->db->getLastInsertID();

            return [
                'success' => true,
                'photo' => [
                    'id' => $photoId,
                    'path' => $webPath,
                    'thumb' => $thumbWebPath,
                    'size' => $fileSize,
                    'type' => $photoType,
                ],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Classify an already-uploaded document with AI
     * POST: file_path (server path to image)
     */
    public function actionClassify()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $webPath = Yii::$app->request->post('file_path');
        if (!$webPath) {
            return ['success' => false, 'error' => 'مسار الملف مطلوب'];
        }

        $filePath = Yii::getAlias('@backend/web') . $webPath;

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'الملف غير موجود'];
        }

        $customerId = Yii::$app->request->post('customer_id');

        $result = VisionService::classify($filePath, $customerId ? (int)$customerId : null);

        return [
            'success' => $result['success'],
            'classification' => $result['classification'] ?? null,
            'text_preview' => mb_substr($result['text'] ?? '', 0, 300),
            'labels' => array_slice($result['labels'] ?? [], 0, 8),
            'error' => $result['error'] ?? null,
            'response_time' => $result['response_time_ms'] ?? 0,
        ];
    }

    /**
     * Get usage statistics — local tracking data
     */
    public function actionUsageStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return VisionService::getUsageStats();
    }

    /**
     * Get LIVE Google Cloud data — real billing + real usage metrics
     * Pulls directly from Google Billing API & Monitoring API
     */
    public function actionGoogleStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return VisionService::getCombinedStats();
    }

    /**
     * Delete an uploaded file
     * POST: file_path, type (document|photo)
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $webPath = Yii::$app->request->post('file_path');
        $type = Yii::$app->request->post('type', 'document');

        if (!$webPath) {
            return ['success' => false, 'error' => 'مسار الملف مطلوب'];
        }

        $filePath = Yii::getAlias('@backend/web') . $webPath;

        // Security: make sure path is within uploads directory
        $uploadsDir = Yii::getAlias('@backend/web/uploads/');
        if (strpos(realpath($filePath), realpath($uploadsDir)) !== 0) {
            return ['success' => false, 'error' => 'مسار غير مسموح'];
        }

        try {
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete thumbnail
            $thumbPath = str_replace(basename($webPath), 'thumbs/thumb_' . basename($webPath), $filePath);
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }

            // Remove from DB if photo
            if ($type === 'photo') {
                Yii::$app->db->createCommand()->delete('os_customer_photos', ['file_path' => $webPath])->execute();
            }

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
