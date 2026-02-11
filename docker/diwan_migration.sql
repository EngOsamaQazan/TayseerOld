-- ==========================================================================
-- قسم الديوان - إنشاء جداول قاعدة البيانات
-- نظام استلام وتسليم الوثائق والعقود
-- ==========================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ──────────────────────────────────────────────────────────────
-- 1. جدول المعاملات (استلام / تسليم)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `os_diwan_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_type` ENUM('استلام', 'تسليم') NOT NULL COMMENT 'نوع المعاملة',
    `from_employee_id` INT(11) NOT NULL COMMENT 'من موظف',
    `to_employee_id` INT(11) NOT NULL COMMENT 'إلى موظف',
    `receipt_number` VARCHAR(50) DEFAULT NULL COMMENT 'رقم الإيصال',
    `notes` TEXT COMMENT 'ملاحظات',
    `transaction_date` DATETIME NOT NULL COMMENT 'تاريخ المعاملة',
    `created_by` INT(11) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` INT(11) DEFAULT NULL,
    `updated_at` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_receipt_number` (`receipt_number`),
    KEY `idx_transaction_type` (`transaction_type`),
    KEY `idx_transaction_date` (`transaction_date`),
    KEY `idx_from_employee` (`from_employee_id`),
    KEY `idx_to_employee` (`to_employee_id`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='معاملات الديوان';

-- ──────────────────────────────────────────────────────────────
-- 2. جدول تفاصيل المعاملات (العقود في كل معاملة)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `os_diwan_transaction_details` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_id` INT(11) NOT NULL COMMENT 'رقم المعاملة',
    `contract_number` VARCHAR(100) NOT NULL COMMENT 'رقم العقد',
    `contract_id` INT(11) DEFAULT NULL COMMENT 'ربط مع جدول العقود',
    `created_at` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_id` (`transaction_id`),
    KEY `idx_contract_number` (`contract_number`),
    KEY `idx_contract_id` (`contract_id`),
    CONSTRAINT `fk_detail_transaction` FOREIGN KEY (`transaction_id`)
        REFERENCES `os_diwan_transactions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تفاصيل معاملات الديوان';

-- ──────────────────────────────────────────────────────────────
-- 3. جدول تتبع الوثائق (من يحمل كل عقد حالياً)
-- ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `os_diwan_document_tracker` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `contract_number` VARCHAR(100) NOT NULL COMMENT 'رقم العقد',
    `contract_id` INT(11) DEFAULT NULL COMMENT 'ربط مع جدول العقود',
    `current_holder_id` INT(11) DEFAULT NULL COMMENT 'الحامل الحالي',
    `last_transaction_id` INT(11) DEFAULT NULL COMMENT 'آخر معاملة',
    `status` VARCHAR(50) DEFAULT 'في الأرشيف' COMMENT 'الحالة',
    `updated_at` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_contract_number` (`contract_number`),
    KEY `idx_current_holder` (`current_holder_id`),
    KEY `idx_last_transaction` (`last_transaction_id`),
    CONSTRAINT `fk_tracker_holder` FOREIGN KEY (`current_holder_id`)
        REFERENCES `os_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_tracker_transaction` FOREIGN KEY (`last_transaction_id`)
        REFERENCES `os_diwan_transactions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='تتبع وثائق الديوان';

SET FOREIGN_KEY_CHECKS = 1;
