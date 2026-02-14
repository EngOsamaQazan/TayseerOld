import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

# Use the correct DB credentials
db_user = 'osama'
db_pass = 'O$amaDaTaBase@123'
db_name = 'namaa_jadal'

def mysql_cmd(sql):
    return f"mysql -u {db_user} -p'{db_pass}' {db_name} -e \"{sql}\" 2>/dev/null"

commands = [
    # Latest ImageManager records
    mysql_cmd("SELECT id, fileName, LEFT(fileHash,12) as hash, contractId, groupName, created FROM os_ImageManager ORDER BY id DESC LIMIT 20"),
    
    # Records with ID > 12377 (missing files)
    mysql_cmd("SELECT id, fileName, LEFT(fileHash,12) as hash, contractId, groupName, created FROM os_ImageManager WHERE id > 12377 ORDER BY id"),
    
    # Records from today
    mysql_cmd("SELECT id, fileName, contractId, created FROM os_ImageManager WHERE DATE(created) >= '2026-02-13' ORDER BY created"),
    
    # Check customer_documents table
    mysql_cmd("SELECT COUNT(*) as total FROM os_customer_documents"),
    mysql_cmd("SELECT id, customer_id, document_type, LEFT(file_path,80) as path, created_at FROM os_customer_documents ORDER BY id DESC LIMIT 10"),
    
    # Check customer_photos
    mysql_cmd("SELECT COUNT(*) as total FROM os_customer_photos"),
    
    # Count total ImageManager records
    mysql_cmd("SELECT COUNT(*) as total, MIN(id) as min_id, MAX(id) as max_id FROM os_ImageManager"),
    
    # Check the Smart form controller on server
    'echo "=== SmartMediaController ===" && grep -n "function action" /var/www/jadal.aqssat.co/backend/modules/customers/controllers/SmartMediaController.php 2>/dev/null',
    
    # Check if SmartMedia creates ImageManager records
    'echo "=== SmartMedia -> ImageManager? ===" && grep -n "ImageManager" /var/www/jadal.aqssat.co/backend/modules/customers/controllers/SmartMediaController.php 2>/dev/null || echo "No ImageManager reference in SmartMedia"',
    
    # Check the customer creation controller for recent changes
    'echo "=== CustomersController modified ===" && ls -la /var/www/jadal.aqssat.co/backend/modules/customers/controllers/CustomersController.php',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and 'Warning' not in err and 'NOTE' not in err and 'password' not in err.lower():
        print(f'ERR: {err}')
    print()

ssh.close()
print("DONE")
