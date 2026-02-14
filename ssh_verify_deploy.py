import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    # Verify the SiteController has the multi-location search
    'echo "=== JADAL: Multi-location search check ===" && grep -c "uploadsDir\|uploads/customers/documents\|smart_media" /var/www/jadal.aqssat.co/backend/controllers/SiteController.php',
    
    # Verify ImageManagerController has the fix
    'echo "=== JADAL: ImageManager upload fix check ===" && grep -c "absoluteMediaPath\|move_uploaded_file\|try.*catch" /var/www/jadal.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php',
    
    # Verify view has smart_media filter
    'echo "=== JADAL: View smart_media check ===" && grep -c "smart_media\|smart-badge\|statSmart" /var/www/jadal.aqssat.co/backend/views/site/image-manager.php',
    
    # Test the page loads (HTTP check)
    'echo "=== HTTP test: Jadal Image Manager ===" && curl -s -o /dev/null -w "%{http_code}" -L "https://jadal.aqssat.co/site/image-manager" --insecure',
    '',
    'echo "=== HTTP test: Jadal Image Manager Data ===" && curl -s -o /dev/null -w "%{http_code}" -L "https://jadal.aqssat.co/site/image-manager-data?page=1&per_page=5" --insecure',
    '',
    'echo "=== HTTP test: Jadal Stats ===" && curl -s -o /dev/null -w "%{http_code}" -L "https://jadal.aqssat.co/site/image-manager-stats" --insecure',
    
    # Check syntax errors
    'echo "=== PHP syntax check ===" && php -l /var/www/jadal.aqssat.co/backend/controllers/SiteController.php && php -l /var/www/jadal.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php',
    
    # Same for Namaa
    'echo "=== NAMAA checks ===" && php -l /var/www/namaa.aqssat.co/backend/controllers/SiteController.php && php -l /var/www/namaa.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php',
]

for cmd in commands:
    if not cmd:
        print()
        continue
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
