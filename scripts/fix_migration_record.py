import paramiko

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

DB_USER = 'osama'
DB_PASS = 'O$amaDaTaBase@123'
DATABASES = ['namaa_erp', 'namaa_jadal']
MIGRATION = 'm260223_120000_add_postal_pluscode_to_jobs'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, username=USER, password=PASS, timeout=60)
transport = ssh.get_transport()
transport.set_keepalive(5)

cmd_parts = []
for db in DATABASES:
    cmd_parts.append(
        f"mysql -u {DB_USER} -p'{DB_PASS}' {db} -e "
        f"\"INSERT IGNORE INTO os_migration (version, apply_time) VALUES ('{MIGRATION}', UNIX_TIMESTAMP());\""
    )
    cmd_parts.append(
        f"mysql -u {DB_USER} -p'{DB_PASS}' {db} -e "
        f"\"SELECT version, FROM_UNIXTIME(apply_time) as applied FROM os_migration WHERE version LIKE '%postal%';\""
    )

full_cmd = " && ".join(cmd_parts) + " && echo ALL_DONE"
stdin, stdout, stderr = ssh.exec_command(full_cmd, timeout=60)
print(stdout.read().decode('utf-8', errors='replace'))
err = stderr.read().decode('utf-8', errors='replace').strip()
if err:
    for line in err.split('\n'):
        if 'Warning' not in line:
            print(f'[err] {line}')

ssh.close()
print('Done!')
