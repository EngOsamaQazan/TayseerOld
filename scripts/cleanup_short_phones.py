import paramiko
import os
os.environ['PYTHONIOENCODING'] = 'utf-8'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

for site in ['jadal', 'namaa']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')

    # Get DB credentials
    out, err = run(f"grep -oP \"'username' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbuser = out.strip()
    out, err = run(f"grep -oP \"'password' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbpass = out.strip()
    out, err = run(f"grep -oP 'dbname=\\K\\w+' {path}/common/config/main-local.php")
    dbname = out.strip()
    print(f"  DB: {dbname} user: {dbuser}")

    # Query short phone numbers
    q1 = "SELECT id, phone_number, customers_id, owner_name FROM os_phone_numbers WHERE CHAR_LENGTH(REPLACE(REPLACE(phone_number,'+',''),'-','')) < 5 AND phone_number IS NOT NULL AND phone_number != '' AND is_deleted=0"
    out1, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q1}\"")
    print("phone_numbers < 5 digits:")
    try:
        print(out1.strip() if out1.strip() else "  (none)")
    except:
        print(out1.encode('ascii', 'replace').decode())

    q2 = "SELECT id, primary_phone_number, name FROM os_customers WHERE CHAR_LENGTH(REPLACE(REPLACE(primary_phone_number,'+',''),'-','')) < 5 AND primary_phone_number IS NOT NULL AND primary_phone_number != ''"
    out2, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q2}\"")
    print("customers < 5 digit primary:")
    try:
        print(out2.strip() if out2.strip() else "  (none)")
    except:
        print(out2.encode('ascii', 'replace').decode())

ssh.close()
