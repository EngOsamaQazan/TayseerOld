import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

db_user = 'osama'
db_pass = 'O$amaDaTaBase@123'

commands = [
    # Search EVERYWHERE on the server for today's specific file hashes
    'echo "=== Searching for 12378 hash: WXPXFIYqg3cF ===" && find / -name "*WXPXFIYqg3cF*" 2>/dev/null | head -5',
    'echo "=== Searching for 12394 hash: gne4Re-M983P ===" && find / -name "*gne4Re-M983P*" 2>/dev/null | head -5',
    
    # Check the relative path resolution: what does ../../backend/web/images/imagemanager resolve to from DocumentRoot?
    'echo "=== Resolve relative path from DocumentRoot ===" && cd /var/www/jadal.aqssat.co/backend/web && realpath ../../backend/web/images/imagemanager 2>/dev/null',
    
    # Check if there's a different imagemanager dir somewhere
    'echo "=== All imagemanager dirs ===" && find /var/www -name "imagemanager" -type d 2>/dev/null',
    
    # Check the old domain's imagemanager (old.jadal)
    'echo "=== old.jadal imagemanager ===" && ls -lt /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null | head -5',
    
    # Check all webroot subdirectories
    'echo "=== All web directories ===" && ls -d /var/www/*/backend/web/images/imagemanager/ 2>/dev/null',
    
    # Check if there's open_basedir that might be blocking
    'echo "=== open_basedir ===" && php -r "echo ini_get(\"open_basedir\");" 2>/dev/null',
    
    # Check the PHP upload_tmp_dir and temp directory
    'echo "=== PHP temp ===" && php -r "echo sys_get_temp_dir();" && echo "" && ls -lt /tmp/php* 2>/dev/null | head -5 || echo "no php tmp files"',
    
    # Critical: check if the working directory during upload is what we expect
    # Let's create a test PHP script to check
    'echo "<?php echo getcwd(); echo chr(10); echo realpath(\"../../backend/web/images/imagemanager\"); echo chr(10); echo Yii::getAlias(\"@backend/web/images/imagemanager\"); ?>" > /var/www/jadal.aqssat.co/backend/web/test_cwd.php',
    'echo "=== Test CWD from web ===" && curl -s "https://jadal.aqssat.co/test_cwd.php" --insecure 2>/dev/null',
    'rm -f /var/www/jadal.aqssat.co/backend/web/test_cwd.php',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and 'Warning' not in err and 'Permission' not in err:
        print(f'ERR: {err}')
    print()

ssh.close()
print("DONE")
