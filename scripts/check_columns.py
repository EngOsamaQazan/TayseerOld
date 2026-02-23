import paramiko, os
os.environ['PYTHONIOENCODING'] = 'utf-8'
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=30)

php_script = r"""<?php
$dbname = $argv[1];
$pdo = new PDO("mysql:host=localhost;dbname=$dbname", 'osama', 'O$amaDaTaBase@123');
$stmt = $pdo->query("SHOW COLUMNS FROM os_stock_movements");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . "\n";
}
"""

sftp = ssh.open_sftp()
with sftp.open('/tmp/check_cols.php', 'w') as f:
    f.write(php_script)
sftp.close()

print('=== JADAL: os_stock_movements columns ===')
stdin, stdout, stderr = ssh.exec_command('php /tmp/check_cols.php namaa_jadal 2>&1', timeout=30)
out = stdout.read().decode('utf-8', errors='replace')
print(out)

print('=== NAMAA: os_stock_movements columns ===')
stdin, stdout, stderr = ssh.exec_command('php /tmp/check_cols.php namaa_erp 2>&1', timeout=30)
out = stdout.read().decode('utf-8', errors='replace')
print(out)

ssh.exec_command('rm /tmp/check_cols.php')
ssh.close()
