import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    return stdout.read().decode('utf-8', errors='replace'), stderr.read().decode('utf-8', errors='replace')

for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    print(f'\n=== {site} ===')
    out, err = run(f'cd {path} && git fetch origin && git reset --hard origin/main')
    print(f'Git: {out.strip()}')

print('\n=== Restarting Apache ===')
out, err = run('systemctl restart apache2')
print(f'Apache: {err.strip() or "restarted"}')

for site in ['namaa', 'jadal']:
    path = f'/var/www/{site}.aqssat.co'
    out, err = run(f'cd {path} && php yii cache/flush-all 2>&1')
    print(f'{site} cache: {out.strip()}')

ssh.close()
print('\nDone!')
