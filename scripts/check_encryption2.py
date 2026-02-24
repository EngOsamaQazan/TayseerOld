import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

path = '/var/www/namaa.aqssat.co'

php_code = r"""
<?php
define('YII_DEBUG', true);
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/backend/web/index.php';

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/common/config/bootstrap.php';
require __DIR__ . '/backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/common/config/main.php',
    require __DIR__ . '/common/config/main-local.php',
    require __DIR__ . '/backend/config/main.php',
    require __DIR__ . '/backend/config/main-local.php'
);

$config['components']['request']['cookieValidationKey'] = $config['components']['request']['cookieValidationKey'] ?? '';

$app = new yii\web\Application($config);

// Check what key is being used for encryption
$salt = Yii::$app->params['encryptionSalt'] ?? null;
echo "encryptionSalt param: " . ($salt !== null ? var_export($salt, true) : "NOT SET") . "\n";
echo "cookieValidationKey: " . substr(Yii::$app->request->cookieValidationKey, 0, 20) . "...\n";

// Read raw value from DB
$row = (new yii\db\Query())->from('os_system_settings')
    ->where(['setting_group' => 'google_cloud', 'setting_key' => 'private_key'])
    ->one();
echo "\nDB row for private_key:\n";
echo "  is_encrypted: " . ($row['is_encrypted'] ?? 'null') . "\n";
echo "  value length: " . strlen($row['setting_value'] ?? '') . "\n";
echo "  value preview: " . substr($row['setting_value'] ?? '', 0, 60) . "...\n";

// Try to decrypt
$decrypted = common\models\SystemSettings::get('google_cloud', 'private_key', '__EMPTY__');
echo "\nDecryption result:\n";
if ($decrypted === '__EMPTY__') {
    echo "  EMPTY (returned default)\n";
} else {
    echo "  Length: " . strlen($decrypted) . "\n";
    echo "  Starts with: " . substr($decrypted, 0, 40) . "\n";
    echo "  Contains BEGIN: " . (strpos($decrypted, 'BEGIN') !== false ? 'YES' : 'NO') . "\n";
}

// Check all google_cloud settings
$all = common\models\SystemSettings::getGroup('google_cloud');
echo "\nAll google_cloud settings:\n";
foreach ($all as $k => $v) {
    if ($k === 'private_key') {
        echo "  $k: " . (strlen($v) > 0 ? "present (len=" . strlen($v) . ", starts=" . substr($v, 0, 30) . ")" : "EMPTY") . "\n";
    } else {
        echo "  $k: $v\n";
    }
}
"""

# Write PHP test file
run(f"cat > {path}/test_encryption.php << 'PHPEOF'\n{php_code}\nPHPEOF")

# Execute it
out, err = run(f"cd {path} && php test_encryption.php 2>&1")
print(out)
if err:
    print("STDERR:", err[:1000])

# Cleanup
run(f"rm -f {path}/test_encryption.php")

ssh.close()
