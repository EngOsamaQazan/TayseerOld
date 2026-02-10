-- ═══════════════════════════════════════════════════════════════════
--  Migration: Action-Level Permissions للإدارة المالية
--  ─────────────────────────────────────────────────────────────────
--  10 صلاحيات جديدة + إسنادها لجميع المستخدمين الحاليين
-- ═══════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ═══ 1. إدراج الصلاحيات الجديدة في os_auth_item (type=2 = permission) ═══
INSERT IGNORE INTO os_auth_item (name, type, created_at, updated_at) VALUES
  ('الحركات المالية: تعديل',   2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('الحركات المالية: حذف',     2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('الحركات المالية: استيراد',  2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('الحركات المالية: ترحيل',   2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('الدخل: تعديل',             2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('الدخل: حذف',               2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('الدخل: ارجاع',             2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('المصاريف: تعديل',          2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('المصاريف: حذف',            2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
  ('المصاريف: ارجاع',          2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ═══ 2. إسناد صلاحيات الحركات المالية لكل من يملك "الحركات المالية" ═══
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الحركات المالية: تعديل', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الحركات المالية';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الحركات المالية: حذف', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الحركات المالية';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الحركات المالية: استيراد', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الحركات المالية';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الحركات المالية: ترحيل', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الحركات المالية';

-- ═══ 3. إسناد صلاحيات الدخل لكل من يملك "الدخل" ═══
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الدخل: تعديل', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الدخل';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الدخل: حذف', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الدخل';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'الدخل: ارجاع', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'الدخل';

-- ═══ 4. إسناد صلاحيات المصاريف لكل من يملك "المصاريف" ═══
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'المصاريف: تعديل', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'المصاريف';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'المصاريف: حذف', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'المصاريف';

INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
  SELECT 'المصاريف: ارجاع', user_id, UNIX_TIMESTAMP()
  FROM os_auth_assignment WHERE item_name = 'المصاريف';

-- ═══ تم — التحقق ═══
SELECT item_name, COUNT(*) as user_count
FROM os_auth_assignment
WHERE item_name LIKE '%: %'
GROUP BY item_name
ORDER BY item_name;
