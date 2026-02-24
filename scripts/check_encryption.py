import paramiko
import os
os.environ['PYTHONIOENCODING'] = 'utf-8'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['namaa']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')

    # Check cookieValidationKey on server
    out, _ = run(f"grep cookieValidationKey {path}/backend/config/main-local.php")
    print(f"cookieValidationKey: {out.strip()}")

    # Check encryptionSalt
    out, _ = run(f"grep -r encryptionSalt {path}/common/config/ {path}/backend/config/ 2>/dev/null")
    print(f"encryptionSalt: {out.strip() or '(not defined)'}")

    # Check what google_cloud settings exist in DB
    out, _ = run(f"grep -oP \"'username' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbuser = out.strip()
    out, _ = run(f"grep -oP \"'password' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbpass = out.strip()
    out, _ = run(f"grep -oP 'dbname=\\K\\w+' {path}/common/config/main-local.php")
    dbname = out.strip()

    q = "SELECT id, setting_group, setting_key, LEFT(setting_value,50) as val_preview, is_encrypted, updated_at FROM os_system_settings WHERE setting_group='google_cloud'"
    out, err = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q}\"")
    print(f"google_cloud settings in DB:")
    print(out if out.strip() else "  (none)")

    # Try decrypting with PHP
    out, err = run(f"""cd {path} && php -r '
require "vendor/autoload.php";
require "common/config/bootstrap.php";
\$config = yii\\helpers\\ArrayHelper::merge(
    require "common/config/main.php",
    require "common/config/main-local.php",
    require "backend/config/main.php",
    require "backend/config/main-local.php"
);
\$app = new yii\\web\\Application(\$config);
\$val = common\\models\\SystemSettings::get("google_cloud","private_key","__EMPTY__");
echo "private_key decrypted: " . ((\$val && \$val !== "__EMPTY__") ? "YES (len=" . strlen(\$val) . ")" : "EMPTY/FAILED") . "\\n";
\$val2 = common\\models\\SystemSettings::get("google_cloud","client_email","__EMPTY__");
echo "client_email: " . \$val2 . "\\n";
\$val3 = common\\models\\SystemSettings::get("google_cloud","enabled","__EMPTY__");
echo "enabled: " . \$val3 . "\\n";
'""")
    print(f"Decryption test:")
    print(out if out.strip() else "  (no output)")
    if err.strip():
        print(f"ERR: {err[:500]}")

ssh.close()
