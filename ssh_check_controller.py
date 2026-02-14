import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

db_user = 'osama'
db_pass = 'O$amaDaTaBase@123'

commands = [
    # Check if today's UUID files exist in uploads directory
    'echo "=== Check UUID files in uploads ===" && ls /var/www/jadal.aqssat.co/backend/web/uploads/customers/documents/b310adf5* 2>/dev/null || echo "NOT in uploads/documents"',
    'ls /var/www/jadal.aqssat.co/backend/web/uploads/customers/documents/e8bd1892* 2>/dev/null || echo "NOT in uploads/documents"',
    'ls /var/www/jadal.aqssat.co/backend/web/uploads/customers/documents/29dd1038* 2>/dev/null || echo "NOT in uploads/documents"',
    
    # Search EVERYWHERE for these UUID files
    'echo "=== Search EVERYWHERE for UUID files ===" && find /var/www/jadal.aqssat.co -name "b310adf5*" 2>/dev/null || echo "NOT FOUND ANYWHERE"',
    'find /var/www/jadal.aqssat.co -name "29dd1038*" 2>/dev/null || echo "NOT FOUND ANYWHERE"',
    
    # Check the actionCreate method in CustomersController
    'echo "=== CustomersController actionCreate ===" && grep -n -A5 "ImageManager\|imagemanager\|image_manager\|selected_image\|imgRandId\|smart.*media\|customer_documents\|file_path" /var/www/jadal.aqssat.co/backend/modules/customers/controllers/CustomersController.php 2>/dev/null | head -80',
    
    # Check the _smart_form - does it reference ImageManager?
    'echo "=== _smart_form.php -> ImageManager? ===" && grep -n "ImageManager\|imagemanager\|image_manager" /var/www/jadal.aqssat.co/backend/modules/customers/views/customers/_smart_form.php 2>/dev/null | head -20',
    
    # Check _form.php for UUID
    'echo "=== _form.php -> UUID? ===" && grep -n "uuid\|Uuid\|UUID" /var/www/jadal.aqssat.co/backend/modules/customers/views/customers/_form.php 2>/dev/null || echo "No UUID in _form"',
    
    # Check recent files modified in uploads
    'echo "=== Recent files in uploads/documents ===" && find /var/www/jadal.aqssat.co/backend/web/uploads/customers/documents/ -mtime -3 2>/dev/null | head -10',
    
    # Check SmartMediaController upload path
    'echo "=== SmartMedia upload dest ===" && grep -n "savePath\|save_path\|documents\|uploads" /var/www/jadal.aqssat.co/backend/modules/customers/controllers/SmartMediaController.php 2>/dev/null | head -10',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    print()

ssh.close()
print("DONE")
