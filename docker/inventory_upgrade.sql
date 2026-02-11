SET NAMES utf8mb4;

-- ═══════════════════════════════════════════════
-- Inventory Items: add status, approval, and detail columns
-- ═══════════════════════════════════════════════
ALTER TABLE os_inventory_items
    ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_deleted`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT(11) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_at` INT(11) DEFAULT NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(500) DEFAULT NULL AFTER `approved_at`,
    ADD COLUMN IF NOT EXISTS `serial_number` VARCHAR(100) DEFAULT NULL AFTER `item_barcode`,
    ADD COLUMN IF NOT EXISTS `description` VARCHAR(500) DEFAULT NULL AFTER `serial_number`,
    ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT NULL AFTER `description`,
    ADD COLUMN IF NOT EXISTS `unit_price` DOUBLE DEFAULT NULL AFTER `category`,
    ADD COLUMN IF NOT EXISTS `supplier_id` INT(11) DEFAULT NULL AFTER `unit_price`,
    ADD COLUMN IF NOT EXISTS `company_id` INT(11) DEFAULT NULL AFTER `supplier_id`;

-- indexes
ALTER TABLE os_inventory_items
    ADD INDEX IF NOT EXISTS `idx_inv_items_status` (`status`),
    ADD INDEX IF NOT EXISTS `idx_inv_items_supplier` (`supplier_id`);

-- ═══════════════════════════════════════════════
-- Inventory Invoices: add status and approval columns
-- ═══════════════════════════════════════════════
ALTER TABLE os_inventory_invoices
    ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_deleted`,
    ADD COLUMN IF NOT EXISTS `approved_by` INT(11) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `approved_at` INT(11) DEFAULT NULL AFTER `approved_by`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` VARCHAR(500) DEFAULT NULL AFTER `approved_at`,
    ADD COLUMN IF NOT EXISTS `invoice_notes` TEXT DEFAULT NULL AFTER `rejection_reason`;

ALTER TABLE os_inventory_invoices
    ADD INDEX IF NOT EXISTS `idx_inv_invoices_status` (`status`);

-- ═══════════════════════════════════════════════
-- Items Inventory Invoices (line items): add status, serial, notes
-- ═══════════════════════════════════════════════
ALTER TABLE os_items_inventory_invoices
    ADD COLUMN IF NOT EXISTS `status` ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `is_deleted`,
    ADD COLUMN IF NOT EXISTS `serial_number` VARCHAR(100) DEFAULT NULL AFTER `status`,
    ADD COLUMN IF NOT EXISTS `notes` VARCHAR(500) DEFAULT NULL AFTER `serial_number`;

-- ═══════════════════════════════════════════════
-- Add missing permissions
-- ═══════════════════════════════════════════════
INSERT IGNORE INTO os_auth_item (name, type, description, created_at, updated_at) VALUES
('الحركات المالية: تعديل', 2, 'الحركات المالية: تعديل', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('الحركات المالية: حذف', 2, 'الحركات المالية: حذف', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('الحركات المالية: استيراد', 2, 'الحركات المالية: استيراد', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('الحركات المالية: ترحيل', 2, 'الحركات المالية: ترحيل', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('الدخل: تعديل', 2, 'الدخل: تعديل', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('الدخل: حذف', 2, 'الدخل: حذف', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('الدخل: ارجاع', 2, 'الدخل: ارجاع', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('المصاريف: تعديل', 2, 'المصاريف: تعديل', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('المصاريف: حذف', 2, 'المصاريف: حذف', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('المصاريف: ارجاع', 2, 'المصاريف: ارجاع', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('أرشيف', 2, 'أرشيف', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('تحديد', 2, 'تحديد', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('استعلام عناصر المخزون', 2, 'استعلام عناصر المخزون', UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Assign new permissions to the admin user (user with most permissions)
SET @admin_id = (SELECT user_id FROM os_auth_assignment GROUP BY user_id ORDER BY COUNT(*) DESC LIMIT 1);

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at) VALUES
('الحركات المالية: تعديل', @admin_id, UNIX_TIMESTAMP()),
('الحركات المالية: حذف', @admin_id, UNIX_TIMESTAMP()),
('الحركات المالية: استيراد', @admin_id, UNIX_TIMESTAMP()),
('الحركات المالية: ترحيل', @admin_id, UNIX_TIMESTAMP()),
('الدخل: تعديل', @admin_id, UNIX_TIMESTAMP()),
('الدخل: حذف', @admin_id, UNIX_TIMESTAMP()),
('الدخل: ارجاع', @admin_id, UNIX_TIMESTAMP()),
('المصاريف: تعديل', @admin_id, UNIX_TIMESTAMP()),
('المصاريف: حذف', @admin_id, UNIX_TIMESTAMP()),
('المصاريف: ارجاع', @admin_id, UNIX_TIMESTAMP()),
('أرشيف', @admin_id, UNIX_TIMESTAMP()),
('تحديد', @admin_id, UNIX_TIMESTAMP()),
('استعلام عناصر المخزون', @admin_id, UNIX_TIMESTAMP());

-- Also assign to users that have base permissions
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الحركات المالية: تعديل', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الحركات المالية';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الحركات المالية: حذف', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الحركات المالية';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الحركات المالية: استيراد', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الحركات المالية';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الحركات المالية: ترحيل', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الحركات المالية';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الدخل: تعديل', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الدخل';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الدخل: حذف', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الدخل';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'الدخل: ارجاع', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'الدخل';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'المصاريف: تعديل', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'المصاريف';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'المصاريف: حذف', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'المصاريف';
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
SELECT 'المصاريف: ارجاع', user_id, UNIX_TIMESTAMP() FROM os_auth_assignment WHERE item_name = 'المصاريف';
