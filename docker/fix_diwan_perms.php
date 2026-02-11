<?php
/**
 * إصلاح صلاحيات الديوان — يُنفّذ مرة واحدة
 * php /var/www/html/docker/fix_diwan_perms.php
 */

$host = 'mysql';
$db   = 'namaa_jadal';
$user = 'root';
$pass = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Connected.\n";

    // 1. حذف السجلات بالترميز الخاطئ
    $pdo->exec("DELETE FROM os_auth_assignment WHERE item_name LIKE '%Ø%' OR item_name LIKE '%Ù%'");
    $pdo->exec("DELETE FROM os_auth_item WHERE name LIKE '%Ø%' OR name LIKE '%Ù%'");
    echo "Cleaned broken records.\n";

    // 2. إضافة الصلاحيات بالترميز الصحيح
    $now = time();
    $perms = ['الديوان', 'تقارير الديوان'];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO os_auth_item (name, type, created_at, updated_at) VALUES (?, 2, ?, ?)");
    foreach ($perms as $p) {
        $stmt->execute([$p, $now, $now]);
        echo "Added permission: $p\n";
    }

    // 3. إسناد الصلاحيات للأدمن (user_id = 1)
    $stmt2 = $pdo->prepare("INSERT IGNORE INTO os_auth_assignment (item_name, user_id, created_at) VALUES (?, '1', ?)");
    foreach ($perms as $p) {
        $stmt2->execute([$p, $now]);
        echo "Assigned to admin: $p\n";
    }

    // 4. التحقق
    $check = $pdo->query("SELECT name FROM os_auth_item WHERE name IN ('الديوان', 'تقارير الديوان')")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nVerification - Permissions in auth_item:\n";
    foreach ($check as $c) {
        echo "  ✓ $c\n";
    }

    $check2 = $pdo->query("SELECT item_name FROM os_auth_assignment WHERE user_id='1' AND item_name IN ('الديوان', 'تقارير الديوان')")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nVerification - Assigned to user 1:\n";
    foreach ($check2 as $c) {
        echo "  ✓ $c\n";
    }

    echo "\nDone!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
