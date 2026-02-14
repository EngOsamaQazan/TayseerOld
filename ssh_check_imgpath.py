import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    # Find ImageManagerGetPath
    'find /var/www/jadal.aqssat.co/vendor -name "ImageManagerGetPath*" 2>/dev/null',
    # Find the component file
    'find /var/www/jadal.aqssat.co -path "*/imagemanager/components/*" -name "*.php" 2>/dev/null',
    # Check vendor imagemanager structure
    'ls -la /var/www/jadal.aqssat.co/vendor/noam148/yii2-image-manager/src/components/ 2>/dev/null || echo "dir not found"',
    # Maybe in a different vendor structure
    'find /var/www/jadal.aqssat.co -name "ImageManagerGetPath*" 2>/dev/null',
    # Check the module directory
    'ls -la /var/www/jadal.aqssat.co/backend/modules/imagemanager/ 2>/dev/null',
    'ls -la /var/www/jadal.aqssat.co/backend/modules/imagemanager/components/ 2>/dev/null || echo "no components dir"',
    # Find any mediaPath reference 
    'grep -rn "mediaPath" /var/www/jadal.aqssat.co/backend/modules/imagemanager/ 2>/dev/null | head -10',
    # Check PHP error log for today
    'tail -100 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | tail -50',
    # Check if there are Imagine/GD errors
    'grep -i "imagine\|gd\|imagick\|image" /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | tail -10',
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
