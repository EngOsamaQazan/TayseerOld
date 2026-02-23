import paramiko

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

DB_USER = 'osama'
DB_PASS = 'O$amaDaTaBase@123'
DATABASES = ['namaa_erp', 'namaa_jadal']
TABLE = 'os_jobs'

def run(ssh, cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

def mysql_cmd(db, sql):
    return f"mysql -u {DB_USER} -p'{DB_PASS}' {db} -e \"{sql}\""

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, username=USER, password=PASS, timeout=30)
print('Connected to server!')

for db in DATABASES:
    print(f'\n=== {db} ===')

    check_sql = (
        f"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS "
        f"WHERE TABLE_SCHEMA='{db}' AND TABLE_NAME='{TABLE}' "
        f"AND COLUMN_NAME IN ('postal_code','plus_code');"
    )
    out, err = run(ssh, mysql_cmd(db, check_sql))
    print(f'  Existing columns: {out}')

    if 'postal_code' not in out:
        sql = f"ALTER TABLE {TABLE} ADD COLUMN postal_code VARCHAR(20) NULL AFTER address_building;"
        out, err = run(ssh, mysql_cmd(db, sql))
        if err and 'warning' not in err.lower():
            print(f'  ERROR adding postal_code: {err}')
        else:
            print(f'  Added postal_code column')
    else:
        print(f'  postal_code already exists, skipping')

    if 'plus_code' not in out:
        sql = f"ALTER TABLE {TABLE} ADD COLUMN plus_code VARCHAR(20) NULL AFTER postal_code;"
        out, err = run(ssh, mysql_cmd(db, sql))
        if err and 'warning' not in err.lower():
            print(f'  ERROR adding plus_code: {err}')
        else:
            print(f'  Added plus_code column')
    else:
        print(f'  plus_code already exists, skipping')

    verify_sql = f"SHOW COLUMNS FROM {TABLE} WHERE Field IN ('postal_code','plus_code');"
    out, err = run(ssh, mysql_cmd(db, verify_sql))
    print(f'  Verify: {out}')

ssh.close()
print('\nDone!')
