"""
Deploy automatic contract status changes:
1. Git pull latest code on both sites
2. Run database migrations (create table, add column, migrate data)
3. Restart Apache and flush cache
"""
import paramiko

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, username=USER, password=PASS, timeout=30)

def run(cmd, timeout=300):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n{"="*50}')
    print(f'  {site.upper()}')
    print(f'{"="*50}')

    print('\n[1/4] Git pull...')
    out, err = run(f'cd {path} && git fetch origin && git reset --hard origin/main')
    print(f'  {out.strip()[-200:]}')
    if err.strip():
        print(f'  Git stderr: {err.strip()[-200:]}')

    print('\n[2/4] Running migrations (may take a while for data migration)...')
    out, err = run(f'cd {path} && php yii migrate --interactive=0 2>&1', timeout=600)
    lines = out.strip().split('\n')
    for line in lines[-20:]:
        print(f'  {line}')

    print('\n[3/4] Verifying tables...')
    db_name = 'namaa_erp' if site == 'namaa' else 'namaa_jadal'
    db_user = 'osama'
    db_pass = "O$amaDaTaBase@123"
    checks = [
        f"SELECT COUNT(*) AS adj_count FROM {db_name}.os_contract_adjustments",
        f"SELECT COUNT(*) AS legal_count FROM {db_name}.os_contracts WHERE is_legal_department = 1",
        f"SELECT status, COUNT(*) AS cnt FROM {db_name}.os_contracts WHERE is_deleted=0 OR is_deleted IS NULL GROUP BY status ORDER BY cnt DESC",
    ]
    for sql in checks:
        out, err = run(f"mysql -u {db_user} -p'{db_pass}' -e \"{sql}\"")
        print(f'  {out.strip()}')

    print('\n[4/4] Flushing cache...')
    out, err = run(f'cd {path} && php yii cache/flush-all 2>&1')
    print(f'  {out.strip()}')

print('\n\n=== Restarting Apache ===')
out, err = run('systemctl restart apache2')
result = err.strip() or 'restarted successfully'
print(f'  Apache: {result}')

ssh.close()
print('\nDone!')
