import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=120)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')
    out, err = run(f'cd {path} && git fetch origin && git reset --hard origin/main')
    print(f'Git: {out.strip()[-200:]}')
    out, err = run(f'cd {path} && php yii cache/flush-all 2>&1')
    print(f'Cache: {out.strip()}')

print('\n=== Restarting Apache ===')
out, err = run('systemctl restart apache2')
print(f'Apache: {err.strip() or "restarted"}')

ssh.close()
print('Done!')
