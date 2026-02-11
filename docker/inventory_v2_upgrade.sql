SET NAMES utf8mb4;

-- ═══════════════════════════════════════════════════════════════
--  ترقية نظام المخزون v2 — إعادة بناء احترافية
-- ═══════════════════════════════════════════════════════════════

-- 1) جدول حركات المخزون — سجل كامل لكل تغيير بالكميات
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

-- 2) إضافة حد أدنى للمخزون + وحدة القياس للأصناف
ALTER TABLE os_inventory_items
    ADD COLUMN IF NOT EXISTS `min_stock_level` INT(11) NOT NULL DEFAULT 0 COMMENT 'الحد الأدنى للتنبيه' AFTER `unit_price`,
    ADD COLUMN IF NOT EXISTS `unit` VARCHAR(30) DEFAULT 'قطعة' COMMENT 'وحدة القياس' AFTER `min_stock_level`;

-- 3) إصلاح auto_increment للموردين
ALTER TABLE os_inventory_suppliers MODIFY COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY IF NOT EXISTS (`id`);

-- 4) إصلاح auto_increment لمواقع التخزين  
ALTER TABLE os_inventory_stock_locations MODIFY COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY IF NOT EXISTS (`id`);

-- 5) إضافة عمود تاريخ الفاتورة المُدخل (ليس تلقائي)
ALTER TABLE os_inventory_invoices
    ADD COLUMN IF NOT EXISTS `invoice_number` VARCHAR(50) DEFAULT NULL COMMENT 'رقم الفاتورة' AFTER `id`,
    ADD COLUMN IF NOT EXISTS `total_amount` DOUBLE DEFAULT 0 AFTER `company_id`;

-- 6) تعبئة حركات مخزون من الفواتير الحالية (لسلامة البيانات)
INSERT IGNORE INTO os_stock_movements (item_id, movement_type, quantity, reference_type, reference_id, unit_cost, supplier_id, company_id, created_by, created_at)
SELECT 
    ii.inventory_items_id,
    'IN',
    ii.number,
    'invoice',
    ii.inventory_invoices_id,
    ii.single_price,
    inv.suppliers_id,
    inv.company_id,
    COALESCE(ii.created_by, inv.created_by, 1),
    COALESCE(ii.created_at, inv.created_at, UNIX_TIMESTAMP())
FROM os_items_inventory_invoices ii
JOIN os_inventory_invoices inv ON inv.id = ii.inventory_invoices_id
WHERE ii.is_deleted = 0 AND inv.is_deleted = 0
AND ii.inventory_items_id IS NOT NULL;
