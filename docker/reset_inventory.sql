SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- حذف كل البيانات من جداول المخزون
TRUNCATE TABLE os_stock_movements;
TRUNCATE TABLE os_items_inventory_invoices;
TRUNCATE TABLE os_inventory_item_quantities;
TRUNCATE TABLE os_inventory_invoices;
TRUNCATE TABLE os_inventory_items;
TRUNCATE TABLE os_inventory_stock_locations;
TRUNCATE TABLE os_inventory_suppliers;

SET FOREIGN_KEY_CHECKS = 1;

-- إضافة المورد
INSERT INTO os_inventory_suppliers (name, phone_number, adress, company_id, created_by, created_at, updated_at, is_deleted)
VALUES ('عمار ناصر معوض ابو السيلات', '0789080839', '', 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 0);
