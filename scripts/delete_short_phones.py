import paramiko
import os
os.environ['PYTHONIOENCODING'] = 'utf-8'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

for site in ['jadal', 'namaa']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')

    out, _ = run(f"grep -oP \"'username' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbuser = out.strip()
    out, _ = run(f"grep -oP \"'password' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbpass = out.strip()
    out, _ = run(f"grep -oP 'dbname=\\K\\w+' {path}/common/config/main-local.php")
    dbname = out.strip()

    # 1) Delete short phone numbers (mark as deleted)
    q1 = "UPDATE os_phone_numbers SET is_deleted=1 WHERE CHAR_LENGTH(REPLACE(REPLACE(phone_number,'+',''),'-','')) < 5 AND phone_number IS NOT NULL AND phone_number != '' AND is_deleted=0"
    out1, err1 = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q1}\"")
    # Count affected
    q1c = "SELECT ROW_COUNT()"
    out1c, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q1}; SELECT ROW_COUNT() as affected;\"")
    print(f"  phone_numbers marked deleted: {out1c.strip()}")

    # 2) Clear short primary_phone_number on customers  
    q2 = "UPDATE os_customers SET primary_phone_number=NULL WHERE CHAR_LENGTH(REPLACE(REPLACE(primary_phone_number,'+',''),'-','')) < 5 AND primary_phone_number IS NOT NULL AND primary_phone_number != ''"
    out2, err2 = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q2}\"")
    print(f"  customers primary cleared")

    # Verify
    q3 = "SELECT COUNT(*) as remaining FROM os_phone_numbers WHERE CHAR_LENGTH(REPLACE(REPLACE(phone_number,'+',''),'-','')) < 5 AND phone_number IS NOT NULL AND phone_number != '' AND is_deleted=0"
    out3, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q3}\"")
    print(f"  remaining short phone_numbers: {out3.strip()}")

    q4 = "SELECT COUNT(*) as remaining FROM os_customers WHERE CHAR_LENGTH(REPLACE(REPLACE(primary_phone_number,'+',''),'-','')) < 5 AND primary_phone_number IS NOT NULL AND primary_phone_number != ''"
    out4, _ = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q4}\"")
    print(f"  remaining short customer phones: {out4.strip()}")

ssh.close()
print("\nDone!")
