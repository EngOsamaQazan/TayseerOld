import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    # Get DB credentials
    'echo "=== JADAL DB config ===" && cat /var/www/jadal.aqssat.co/common/config/main-local.php',
    
    # Check the ImageManager controller - what changed on Feb 9?
    'echo "=== ImageManagerController changes ===" && head -160 /var/www/jadal.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php',
    
    # Check writable
    'echo "=== Writable test ===" && sudo -u www-data touch /var/www/jadal.aqssat.co/backend/web/images/imagemanager/_test 2>&1 && echo "OK www-data can write" && rm /var/www/jadal.aqssat.co/backend/web/images/imagemanager/_test || echo "FAILED"',
    
    # Check permissions on imagemanager dir
    'echo "=== DIR permissions ===" && ls -la /var/www/jadal.aqssat.co/backend/web/images/',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and 'Warning' not in err:
        print(f'ERR: {err}')
    print()

ssh.close()
print("DONE")
