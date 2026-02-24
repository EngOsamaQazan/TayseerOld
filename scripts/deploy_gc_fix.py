import paramiko
import os

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    return out, err

for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== Deploying to {site} ===')

    # Pull latest code
    out, err = run(f'cd {path} && git fetch origin && git reset --hard origin/main')
    print(f'Git: {out.strip()}')

    # Get DB credentials
    out, _ = run(f"grep -oP \"'username' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbuser = out.strip()
    out, _ = run(f"grep -oP \"'password' => '\\K[^']+\" {path}/common/config/main-local.php")
    dbpass = out.strip()
    out, _ = run(f"grep -oP 'dbname=\\K\\w+' {path}/common/config/main-local.php")
    dbname = out.strip()

    # Fix enabled=1 for google_cloud if credentials exist
    q = """UPDATE os_system_settings SET setting_value='1', updated_at=NOW()
           WHERE setting_group='google_cloud' AND setting_key='enabled'
           AND EXISTS (SELECT 1 FROM (SELECT id FROM os_system_settings
                       WHERE setting_group='google_cloud' AND setting_key='private_key'
                       AND setting_value IS NOT NULL AND setting_value != '') t)"""
    out, err = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q}\"")
    print(f'DB fix: {out.strip() or "done"}')
    if err.strip():
        print(f'  DB err: {err.strip()[:200]}')

    # Verify
    q2 = "SELECT setting_key, setting_value FROM os_system_settings WHERE setting_group='google_cloud' AND setting_key IN ('enabled','client_email','project_id')"
    out, err = run(f"mysql -u {dbuser} -p'{dbpass}' {dbname} -e \"{q2}\"")
    print(f'Verify: {out.strip()}')

# Restart Apache
print('\n=== Restarting Apache ===')
out, err = run('systemctl restart apache2')
print(f'Apache: {err.strip() or "restarted"}')

# Flush cache
for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    out, err = run(f'cd {path} && php yii cache/flush-all 2>&1')
    print(f'{site} cache: {out.strip()}')

ssh.close()
print('\nDone!')
