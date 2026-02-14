import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    # Check the specific orphan images (ID 12393, 12394) on jadal
    'echo "=== JADAL: Check if files 12393/12394 exist ===" && ls -la /var/www/jadal.aqssat.co/backend/web/images/imagemanager/12393_* 2>/dev/null || echo "12393: NOT FOUND"',
    'ls -la /var/www/jadal.aqssat.co/backend/web/images/imagemanager/12394_* 2>/dev/null || echo "12394: NOT FOUND"',
    
    # What are the NEWEST files in imagemanager?
    'echo "=== JADAL: Last 10 newest imagemanager files ===" && ls -lt /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ | head -15',
    
    # What about uploads/customers - any new files there?
    'echo "=== JADAL: Last 10 newest uploads/customers/documents ===" && ls -lt /var/www/jadal.aqssat.co/backend/web/uploads/customers/documents/ 2>/dev/null | head -15',
    
    # Check uploads/customers/photos
    'echo "=== JADAL: uploads/customers/photos ===" && ls -lt /var/www/jadal.aqssat.co/backend/web/uploads/customers/photos/ 2>/dev/null | head -10',
    
    # Check today's files specifically
    'echo "=== JADAL: Files modified today in imagemanager ===" && find /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ -mtime 0 -type f 2>/dev/null | wc -l',
    'echo "=== JADAL: Files modified today in uploads ===" && find /var/www/jadal.aqssat.co/backend/web/uploads/ -mtime 0 -type f 2>/dev/null',
    
    # Check the highest ID file in imagemanager
    'echo "=== JADAL: Highest ID files ===" && ls /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ | sort -t_ -k1 -n | tail -10',
    
    # Check DB: what are the latest ImageManager records?
    'echo "=== JADAL DB: Latest 10 ImageManager records ===" && mysql -u root namaa_jadal -e "SELECT id, fileName, fileHash, contractId, groupName, created FROM os_ImageManager ORDER BY id DESC LIMIT 10" 2>/dev/null',
    
    # Check DB: records from today
    'echo "=== JADAL DB: Today records ===" && mysql -u root namaa_jadal -e "SELECT id, fileName, fileHash, contractId, groupName, created FROM os_ImageManager WHERE DATE(created) = CURDATE() ORDER BY id" 2>/dev/null',
    
    # Check os_customer_documents table
    'echo "=== JADAL DB: customer_documents latest ===" && mysql -u root namaa_jadal -e "SELECT * FROM os_customer_documents ORDER BY id DESC LIMIT 5" 2>/dev/null',
    
    # Check the uploads subdirectory structure  
    'echo "=== JADAL: Full uploads tree ===" && find /var/www/jadal.aqssat.co/backend/web/uploads/ -type d 2>/dev/null',
    
    # ---- NAMAA too ----
    'echo "=== NAMAA: Highest ID files ===" && ls /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null | sort -t_ -k1 -n | tail -5',
    'echo "=== NAMAA DB: Latest ImageManager ===" && mysql -u root namaa_erp -e "SELECT id, fileName, fileHash, contractId, groupName, created FROM os_ImageManager ORDER BY id DESC LIMIT 5" 2>/dev/null',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode().strip()
    err = stderr.read().decode().strip()
    if out:
        print(out)
    if err and 'Warning' not in err:
        print(f'ERR: {err}')
    print()

ssh.close()
print("DONE")
