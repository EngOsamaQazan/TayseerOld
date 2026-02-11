SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DELETE FROM os_inventory_item_quantities;
DELETE FROM os_inventory_invoices;
DELETE FROM os_inventory_items;
DELETE FROM os_inventory_suppliers WHERE name='ggg';
DELETE FROM os_inventory_stock_locations WHERE locations_name='nmn';
SET FOREIGN_KEY_CHECKS=1;
ALTER TABLE os_inventory_items AUTO_INCREMENT=1;
ALTER TABLE os_inventory_item_quantities AUTO_INCREMENT=1;
ALTER TABLE os_inventory_invoices AUTO_INCREMENT=1;
SET @now=UNIX_TIMESTAMP();

INSERT INTO os_inventory_suppliers (id,company_id,name,adress,phone_number,created_by,created_at,updated_at,last_update_by,is_deleted)
VALUES (1,1,'عمار ناصر معوض ابو السيلات','الاردن','0789080839',1,@now,@now,1,0)
ON DUPLICATE KEY UPDATE name=VALUES(name),phone_number=VALUES(phone_number);

INSERT INTO os_inventory_stock_locations (id,locations_name,company_id,created_by,created_at,updated_at,last_update_by,is_deleted)
VALUES (1,'المخزون الرئيسي - صويلح',1,1,@now,@now,1,0)
ON DUPLICATE KEY UPDATE locations_name=VALUES(locations_name);

INSERT INTO os_inventory_items (id,item_name,item_barcode,created_at,updated_at,created_by,last_update_by,is_deleted) VALUES
(1,'infinix smart10 64GB','INF-S10-64',@now,@now,1,1,0),
(2,'infinix smart10 128GB','INF-S10-128',@now,@now,1,1,0),
(3,'infinix smart10 Plus','INF-S10-PLUS',@now,@now,1,1,0),
(4,'HONOR X6C 128GB','HON-X6C-128',@now,@now,1,1,0),
(5,'TECNO SPARK GO 2','TEC-SGO2',@now,@now,1,1,0),
(6,'TECNO SPARK 30C','TEC-S30C',@now,@now,1,1,0),
(7,'SAMSUNG A06 128GB 6GB','SAM-A06-128-6',@now,@now,1,1,0),
(8,'SAMSUNG A07 128GB 6GB','SAM-A07-128-6',@now,@now,1,1,0),
(9,'SAMSUNG A07 128GB 4GB','SAM-A07-128-4',@now,@now,1,1,0),
(10,'SAMSUNG A07 64GB 4GB','SAM-A07-64-4',@now,@now,1,1,0),
(11,'Infinix hot 40i 256/4+4','INF-H40I-256',@now,@now,1,1,0),
(12,'TECNO SPARK GO 3 64/4+4','TEC-SGO3-64',@now,@now,1,1,0),
(13,'TECNO SPARK GO 3 128/4+4','TEC-SGO3-128',@now,@now,1,1,0),
(14,'HONOR X6C 256GB','HON-X6C-256',@now,@now,1,1,0),
(15,'HONOR X5c plus','HON-X5C-PLUS',@now,@now,1,1,0),
(16,'tecno 40c 256/8+8','TEC-40C-256',@now,@now,1,1,0);

INSERT INTO os_inventory_item_quantities (item_id,locations_id,suppliers_id,quantity,created_at,created_by,last_updated_by,updated_at,is_deleted,company_id) VALUES
(1,1,1,17,@now,1,1,@now,0,1),
(2,1,1,3,@now,1,1,@now,0,1),
(3,1,1,5,@now,1,1,@now,0,1),
(4,1,1,7,@now,1,1,@now,0,1),
(5,1,1,5,@now,1,1,@now,0,1),
(6,1,1,3,@now,1,1,@now,0,1),
(7,1,1,11,@now,1,1,@now,0,1),
(8,1,1,5,@now,1,1,@now,0,1),
(9,1,1,6,@now,1,1,@now,0,1),
(10,1,1,21,@now,1,1,@now,0,1),
(11,1,1,6,@now,1,1,@now,0,1),
(12,1,1,6,@now,1,1,@now,0,1),
(13,1,1,6,@now,1,1,@now,0,1),
(14,1,1,6,@now,1,1,@now,0,1),
(15,1,1,6,@now,1,1,@now,0,1),
(16,1,1,6,@now,1,1,@now,0,1);
