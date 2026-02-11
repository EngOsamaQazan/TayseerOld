-- إضافة صلاحيات الديوان إلى نظام RBAC
-- ════════════════════════════════════════

-- 1. إضافة الصلاحيات كـ auth_item (type=2 = permission)
INSERT IGNORE INTO os_auth_item (name, type, created_at, updated_at)
VALUES
    ('الديوان', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('تقارير الديوان', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. إسناد الصلاحيات للمستخدم الأدمن (user_id = 1)
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
VALUES
    ('الديوان', '1', UNIX_TIMESTAMP()),
    ('تقارير الديوان', '1', UNIX_TIMESTAMP());

-- 3. إضافة مسارات الديوان (routes)
INSERT IGNORE INTO os_auth_item (name, type, created_at, updated_at)
VALUES
    ('/diwan/*', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/*', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/index', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/create', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/transactions', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/view', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/receipt', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/delete', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/search', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/reports', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    ('/diwan/diwan/document-history', 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 4. إسناد مسارات الديوان للأدمن
INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at)
VALUES
    ('/diwan/*', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/*', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/index', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/create', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/transactions', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/view', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/receipt', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/delete', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/search', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/reports', '1', UNIX_TIMESTAMP()),
    ('/diwan/diwan/document-history', '1', UNIX_TIMESTAMP());
