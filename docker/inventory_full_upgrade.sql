-- ═══════════════════════════════════════════════════════════════════════════
--  ترقية نظام المخزون الكاملة — للتطبيق على الكلاود
--  يتضمن: إنشاء جدول حركات المخزون، أعمدة جديدة، تنظيف البيانات، المورد
-- ═══════════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────────
-- 1) أعمدة جديدة لجدول الأصناف os_inventory_items
-- ─────────────────────────────────────────────────────────────────────────
ALTER TABLE os_inventory_items
    ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_deleted`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT(11) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_at` INT(11) DEFAULT NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(500) DEFAULT NULL AFTER `approved_at`,
    ADD COLUMN IF NOT EXISTS `serial_number` VARCHAR(100) DEFAULT NULL AFTER `item_barcode`,
    ADD COLUMN IF NOT EXISTS `description` VARCHAR(500) DEFAULT NULL AFTER `serial_number`,
    ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `unit_price` DOUBLE DEFAULT NULL AFTER `category`,
    ADD COLUMN IF NOT EXISTS `min_stock_level` INT(11) NOT NULL DEFAULT 0 COMMENT 'الحد الأدنى للتنبيه' AFTER `unit_price`,
    ADD COLUMN IF NOT EXISTS `unit` VARCHAR(30) DEFAULT 'قطعة' COMMENT 'وحدة القياس' AFTER `min_stock_level`,
    ADD COLUMN IF NOT EXISTS `supplier_id` INT(11) DEFAULT NULL AFTER `unit`,
    ADD COLUMN IF NOT EXISTS `company_id` INT(11) DEFAULT NULL AFTER `supplier_id`;

-- فهارس للأصناف
ALTER TABLE os_inventory_items
    ADD INDEX IF NOT EXISTS `idx_items_status` (`status`),
    ADD INDEX IF NOT EXISTS `idx_items_category` (`category`),
    ADD INDEX IF NOT EXISTS `idx_items_supplier` (`supplier_id`);

-- ─────────────────────────────────────────────────────────────────────────
-- 2) أعمدة جديدة لجدول الفواتير os_inventory_invoices
-- ─────────────────────────────────────────────────────────────────────────
ALTER TABLE os_inventory_invoices
    ADD COLUMN IF NOT EXISTS `invoice_number` VARCHAR(50) DEFAULT NULL COMMENT 'رقم الفاتورة' AFTER `id`,
    ADD COLUMN IF NOT EXISTS `total_amount` DOUBLE DEFAULT 0 AFTER `company_id`,
    ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_deleted`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT(11) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_at` INT(11) DEFAULT NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(500) DEFAULT NULL AFTER `approved_at`,
    ADD COLUMN IF NOT EXISTS `invoice_notes` TEXT DEFAULT NULL AFTER `rejection_reason`;

-- ─────────────────────────────────────────────────────────────────────────
-- 3) أعمدة جديدة لجدول بنود الفواتير os_items_inventory_invoices
-- ─────────────────────────────────────────────────────────────────────────
ALTER TABLE os_items_inventory_invoices
    ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_deleted`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT(11) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_at` INT(11) DEFAULT NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(500) DEFAULT NULL AFTER `approved_at`;

-- ─────────────────────────────────────────────────────────────────────────
-- 4) ملاحظة: الموردين والمواقع تحتوي أصلاً على AUTO_INCREMENT + PRIMARY KEY
--    لا تحتاج تعديل
-- ─────────────────────────────────────────────────────────────────────────
-- ─────────────────────────────────────────────────────────────────────────
-- 5) إنشاء جدول حركات المخزون (جديد)
-- ─────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `os_stock_movements` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `item_id` INT(11) NOT NULL COMMENT 'الصنف',
    `movement_type` ENUM('IN','OUT','TRANSFER','ADJUSTMENT','RETURN') NOT NULL COMMENT 'نوع الحركة',
    `quantity` INT(11) NOT NULL COMMENT 'الكمية (موجب دائماً)',
    `from_location_id` INT(11) DEFAULT NULL COMMENT 'من موقع',
    `to_location_id` INT(11) DEFAULT NULL COMMENT 'إلى موقع',
    `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'نوع المرجع: invoice, adjustment, transfer, return',
    `reference_id` INT(11) DEFAULT NULL COMMENT 'رقم المرجع',
    `unit_cost` DOUBLE DEFAULT NULL COMMENT 'تكلفة الوحدة وقت الحركة',
    `notes` VARCHAR(500) DEFAULT NULL COMMENT 'ملاحظات',
    `supplier_id` INT(11) DEFAULT NULL COMMENT 'المورد',
    `company_id` INT(11) DEFAULT NULL COMMENT 'الشركة',
    `created_by` INT(11) NOT NULL,
    `created_at` INT(11) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_sm_item` (`item_id`),
    INDEX `idx_sm_type` (`movement_type`),
    INDEX `idx_sm_date` (`created_at`),
    INDEX `idx_sm_ref` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────
-- 6) تنظيف كل بيانات المخزون وإعادة الترقيم
-- ─────────────────────────────────────────────────────────────────────────
TRUNCATE TABLE os_stock_movements;
TRUNCATE TABLE os_items_inventory_invoices;
TRUNCATE TABLE os_inventory_item_quantities;
TRUNCATE TABLE os_inventory_invoices;
TRUNCATE TABLE os_inventory_items;
TRUNCATE TABLE os_inventory_stock_locations;
TRUNCATE TABLE os_inventory_suppliers;

-- ─────────────────────────────────────────────────────────────────────────
-- 7) إضافة المورد الأساسي
-- ─────────────────────────────────────────────────────────────────────────
INSERT INTO os_inventory_suppliers (name, phone_number, adress, company_id, created_by, created_at, updated_at, is_deleted)
VALUES ('عمار ناصر معوض ابو السيلات', '0789080839', '', 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0);

-- ─────────────────────────────────────────────────────────────────────────
-- 8) صلاحيات المخزون الجديدة (إذا لم تكن موجودة)
-- ─────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO os_auth_item (name, type, description, rule_name, `data`, created_at, updated_at)
VALUES ('استعلام عناصر المخزون', 2, 'استعلام عناصر المخزون', NULL, NULL, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
