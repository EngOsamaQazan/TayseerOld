import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

db_user = 'osama'
db_pass = 'O$amaDaTaBase@123'

def mysql_cmd(db, sql):
    return f"mysql -u {db_user} -p'{db_pass}' {db} -e \"{sql}\" 2>/dev/null"

commands = [
    # Check if today's ImageManager records now have matching files after checking all locations
    'echo "=== Today ImageManager records vs files ===" && for f in 12378 12379 12380 12381 12390 12391 12392 12393 12394; do '
    '  echo -n "$f: "; '
    '  if ls /var/www/jadal.aqssat.co/backend/web/images/imagemanager/${f}_* 2>/dev/null; then echo "FOUND in imagemanager"; '
    '  elif ls /var/www/jadal.aqssat.co/backend/web/uploads/customers/documents/${f}_* 2>/dev/null; then echo "FOUND in uploads"; '
    '  else echo "MISSING everywhere"; fi; '
    'done',
    
    # Check os_customer_documents for the SAME customer IDs
    mysql_cmd('namaa_jadal', 
        "SELECT cd.id, cd.customer_id, cd.document_type, LEFT(cd.file_path,60) as path, cd.created_at "
        "FROM os_customer_documents cd "
        "WHERE cd.created_at >= '2026-02-14' "
        "ORDER BY cd.created_at DESC LIMIT 10"),
    
    # Check the Yii2 error log for any upload errors TODAY
    'echo "=== JADAL: Recent error log (image-related) ===" && grep -i "imagemanager\\|imagine\\|upload.*fail\\|save.*fail" /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null | tail -15',
    
    # Check PHP error log
    'echo "=== PHP error log ===" && tail -20 /var/log/php_errors.log 2>/dev/null || tail -20 /var/log/php*.log 2>/dev/null || echo "no php error log found"',
    
    # Check Apache error log for today
    'echo "=== Apache error log (today) ===" && grep "Feb 14" /var/log/apache2/error.log 2>/dev/null | tail -10',
    
    # What PHP version on the server?
    'echo "=== PHP version ===" && php -v | head -1',
    
    # Is Imagine/GD/Imagick available?
    'echo "=== Image extensions ===" && php -m | grep -i "gd\\|imagick\\|imagine"',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err and 'Warning' not in err and 'password' not in err.lower():
        print(f'ERR: {err}')
    print()

ssh.close()
print("DONE")
