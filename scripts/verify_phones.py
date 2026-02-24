import paramiko
import os
os.environ['PYTHONIOENCODING'] = 'utf-8'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['jadal', 'namaa']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')
    out, _ = run(f"grep -oP \"'username' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbuser = out.strip()
    out, _ = run(f"grep -oP \"'password' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbpass = out.strip()
    out, _ = run(f"grep -oP 'dbname=\\K\\w+' {path}/common/config/main-local.php")
    dbname = out.strip()

    # Count short numbers still active
    out1, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -N -e \"SELECT COUNT(*) FROM os_phone_numbers WHERE CHAR_LENGTH(REPLACE(REPLACE(phone_number,'+',''),'-','')) < 5 AND phone_number IS NOT NULL AND phone_number != '' AND is_deleted=0\"")
    print(f"  Active phone_numbers < 5 digits: {out1.strip()}")

    out2, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -N -e \"SELECT COUNT(*) FROM os_customers WHERE CHAR_LENGTH(REPLACE(REPLACE(primary_phone_number,'+',''),'-','')) < 5 AND primary_phone_number IS NOT NULL AND primary_phone_number != ''\"")
    print(f"  Customers with < 5 digit primary: {out2.strip()}")

    # Count deleted short numbers
    out3, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -N -e \"SELECT COUNT(*) FROM os_phone_numbers WHERE CHAR_LENGTH(REPLACE(REPLACE(phone_number,'+',''),'-','')) < 5 AND phone_number IS NOT NULL AND phone_number != '' AND is_deleted=1\"")
    print(f"  Deleted short phone_numbers: {out3.strip()}")

ssh.close()
