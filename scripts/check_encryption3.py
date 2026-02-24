import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

path = '/var/www/namaa.aqssat.co'

php_code = r"""<?php
define('YII_DEBUG', true);
define('YII_ENV', 'prod');
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/backend/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'namaa.aqssat.co';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['HTTP_HOST'] = 'namaa.aqssat.co';

require __DIR__ . '/vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/backend/config/main.php',
    require __DIR__ . '/backend/config/main-local.php'
);

$app = new yii\web\Application($config);

$salt = Yii::$app->params['encryptionSalt'] ?? null;
echo "encryptionSalt param: " . ($salt !== null ? var_export($salt, true) : "NOT SET") . "\n";
echo "cookieValidationKey: " . substr(Yii::$app->request->cookieValidationKey, 0, 20) . "...\n";

$encKey = substr(hash('sha256', Yii::$app->request->cookieValidationKey), 0, 32);
echo "Derived encryption key: " . substr($encKey, 0, 16) . "...\n";

$row = (new yii\db\Query())->from('os_system_settings')
    ->where(['setting_group' => 'google_cloud', 'setting_key' => 'private_key'])
    ->one();
echo "\nDB private_key row:\n";
echo "  is_encrypted: " . ($row['is_encrypted'] ?? 'null') . "\n";
echo "  value length: " . strlen($row['setting_value'] ?? '') . "\n";

$decrypted = common\models\SystemSettings::get('google_cloud', 'private_key', '__DEFAULT__');
echo "\nDecryption result:\n";
if ($decrypted === '__DEFAULT__') {
    echo "  RETURNED DEFAULT (decryption returned empty)\n";
} else {
    echo "  Length: " . strlen($decrypted) . "\n";
    echo "  Contains 'BEGIN': " . (strpos($decrypted, 'BEGIN') !== false ? 'YES' : 'NO') . "\n";
    echo "  First 50 chars: " . substr($decrypted, 0, 50) . "\n";
}

$all = common\models\SystemSettings::getGroup('google_cloud');
echo "\nAll google_cloud settings:\n";
foreach ($all as $k => $v) {
    if ($k === 'private_key') {
        echo "  $k: len=" . strlen($v) . ", has_BEGIN=" . (strpos($v, 'BEGIN') !== false ? 'YES' : 'NO') . "\n";
    } else {
        echo "  $k: '$v'\n";
    }
}
"""

run(f"cat > {path}/test_enc.php << 'PHPEOF'\n{php_code}\nPHPEOF")
out, err = run(f"cd {path} && php test_enc.php 2>&1")
print(out)
if err:
    print("STDERR:", err[:1000])
run(f"rm -f {path}/test_enc.php")

ssh.close()
