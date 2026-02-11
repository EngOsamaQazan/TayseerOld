-- ============================================================
-- SMART MEDIA SYSTEM — Document AI & WebCam
-- Upgrade customers_document + API usage tracking
-- ============================================================

-- 1) Upgrade os_customers_document with AI columns
ALTER TABLE os_customers_document
    ADD COLUMN IF NOT EXISTS file_path VARCHAR(500) DEFAULT NULL COMMENT 'مسار الملف المباشر',
    ADD COLUMN IF NOT EXISTS file_size INT UNSIGNED DEFAULT 0 COMMENT 'حجم الملف بالبايت',
    ADD COLUMN IF NOT EXISTS mime_type VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS capture_method ENUM('upload','webcam','scan') DEFAULT 'upload' COMMENT 'طريقة الالتقاط',
    ADD COLUMN IF NOT EXISTS ai_classification VARCHAR(100) DEFAULT NULL COMMENT 'تصنيف AI التلقائي',
    ADD COLUMN IF NOT EXISTS ai_confidence DECIMAL(5,2) DEFAULT NULL COMMENT 'نسبة ثقة التصنيف 0-100',
    ADD COLUMN IF NOT EXISTS ai_extracted_text TEXT DEFAULT NULL COMMENT 'النص المستخرج من OCR',
    ADD COLUMN IF NOT EXISTS ai_extracted_data JSON DEFAULT NULL COMMENT 'بيانات منظمة مستخرجة',
    ADD COLUMN IF NOT EXISTS ai_labels JSON DEFAULT NULL COMMENT 'تصنيفات Vision API الخام',
    ADD COLUMN IF NOT EXISTS is_verified TINYINT(1) DEFAULT 0 COMMENT 'تم التحقق يدوياً',
    ADD COLUMN IF NOT EXISTS verified_by INT UNSIGNED DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS verified_at DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS thumbnail_path VARCHAR(500) DEFAULT NULL COMMENT 'مسار الصورة المصغرة';

-- 2) Customer photos table (webcam captures & profile photos)
CREATE TABLE IF NOT EXISTS os_customer_photos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    photo_type      ENUM('profile','webcam','id_front','id_back','selfie','other') NOT NULL DEFAULT 'webcam',
    file_path       VARCHAR(500) NOT NULL,
    thumbnail_path  VARCHAR(500) DEFAULT NULL,
    file_size       INT UNSIGNED DEFAULT 0,
    mime_type       VARCHAR(100) DEFAULT 'image/jpeg',
    capture_method  ENUM('webcam','upload') DEFAULT 'webcam',
    ai_face_data    JSON DEFAULT NULL COMMENT 'بيانات التعرف على الوجه',
    is_primary      TINYINT(1) DEFAULT 0 COMMENT 'الصورة الرئيسية للعميل',
    created_by      INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_customer (customer_id),
    KEY idx_type (photo_type),
    KEY idx_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Vision API usage tracking (for cost monitoring)
CREATE TABLE IF NOT EXISTS os_vision_api_usage (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_feature     VARCHAR(50) NOT NULL COMMENT 'TEXT_DETECTION / LABEL_DETECTION / DOCUMENT_TEXT_DETECTION',
    customer_id     INT DEFAULT NULL,
    document_id     INT DEFAULT NULL COMMENT 'reference to customers_document or customer_photos',
    document_table  VARCHAR(50) DEFAULT NULL COMMENT 'os_customers_document / os_customer_photos',
    request_status  ENUM('success','error','timeout') NOT NULL DEFAULT 'success',
    response_time_ms INT UNSIGNED DEFAULT NULL COMMENT 'زمن الاستجابة بالملي ثانية',
    cost_estimate   DECIMAL(8,4) DEFAULT 0 COMMENT 'التكلفة التقديرية بالدولار',
    error_message   TEXT DEFAULT NULL,
    request_by      INT UNSIGNED DEFAULT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_date (created_at),
    KEY idx_feature (api_feature),
    KEY idx_customer (customer_id),
    KEY idx_status (request_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Monthly usage summary view (for dashboard)
CREATE OR REPLACE VIEW v_vision_api_monthly_usage AS
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') AS month,
    api_feature,
    COUNT(*) AS total_requests,
    SUM(CASE WHEN request_status = 'success' THEN 1 ELSE 0 END) AS successful,
    SUM(CASE WHEN request_status = 'error' THEN 1 ELSE 0 END) AS failed,
    SUM(cost_estimate) AS total_cost,
    AVG(response_time_ms) AS avg_response_ms
FROM os_vision_api_usage
GROUP BY DATE_FORMAT(created_at, '%Y-%m'), api_feature;
