import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    # Try mysql with different auth methods
    'echo "=== JADAL DB: Latest ImageManager ===" && mysql -u root -e "SELECT id, fileName, LEFT(fileHash,10) as hash, contractId, groupName, created FROM os_ImageManager ORDER BY id DESC LIMIT 15;" namaa_jadal',
    
    # Find ANY files created in last 3 days across entire jadal web dir
    'echo "=== JADAL: ALL files from last 3 days ===" && find /var/www/jadal.aqssat.co/backend/web -type f -mtime -3 -not -path "*/assets/*" -not -path "*/runtime/*" 2>/dev/null | head -30',
    
    # Check if the ImageManager upload controller was modified recently
    'echo "=== ImageManager controller modified? ===" && ls -la /var/www/jadal.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php',
    
    # Check the mediaPath configuration
    'echo "=== Config: imagemanager mediaPath ===" && grep -A3 "imagemanager" /var/www/jadal.aqssat.co/common/config/main.php',
    
    # Check if the imagemanager dir is writable
    'echo "=== Is imagemanager writable? ===" && ls -la /var/www/jadal.aqssat.co/backend/web/images/ && touch /var/www/jadal.aqssat.co/backend/web/images/imagemanager/_test_write 2>&1 && echo "WRITABLE" && rm /var/www/jadal.aqssat.co/backend/web/images/imagemanager/_test_write || echo "NOT WRITABLE"',
    
    # Check customer_documents table
    'echo "=== JADAL: os_customer_documents ===" && mysql -u root -e "SELECT COUNT(*) as total FROM os_customer_documents;" namaa_jadal 2>/dev/null || echo "Table not found"',
    'mysql -u root -e "DESCRIBE os_customer_documents;" namaa_jadal 2>/dev/null | head -15',
    'mysql -u root -e "SELECT id, customer_id, LEFT(file_path,60) as path, document_type, created_at FROM os_customer_documents ORDER BY id DESC LIMIT 10;" namaa_jadal 2>/dev/null',
    
    # Check os_customer_photos table
    'echo "=== JADAL: os_customer_photos ===" && mysql -u root -e "SELECT COUNT(*) as total FROM os_customer_photos;" namaa_jadal 2>/dev/null || echo "Table not found"',
    
    # PHP error logs - look for imagemanager errors
    'echo "=== Recent PHP errors ===" && tail -30 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | grep -i "image" | tail -10',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out:
        print(out)
    if err and 'Warning' not in err and 'NOTE' not in err:
        print(f'ERR: {err}')
    print()

ssh.close()
print("DONE")
