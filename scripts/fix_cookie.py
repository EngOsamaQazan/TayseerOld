import paramiko
import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=30)
print('Connected!')

def run(cmd):
    si, so, se = ssh.exec_command(cmd, timeout=30)
    out = so.read().decode('utf-8', 'replace').strip()
    err = se.read().decode('utf-8', 'replace').strip()
    code = so.channel.recv_exit_status()
    return code, out, err

# Check current main-local.php for both sites
for site in ['jadal.aqssat.co', 'namaa.aqssat.co']:
    path = f'/var/www/{site}'
    print(f'\n=== {site} ===')
    
    # Check backend main-local.php
    code, out, _ = run(f'cat {path}/backend/config/main-local.php')
    print(f'backend/config/main-local.php:\n{out}\n')
    
    # Check common main-local.php
    code, out, _ = run(f'cat {path}/common/config/main-local.php')
    print(f'common/config/main-local.php:\n{out}\n')
    
    # Check environments folder
    env_dir = 'environments/prod_jadal' if 'jadal' in site else 'environments/prod_namaa'
    code, out, _ = run(f'cat {path}/{env_dir}/backend/config/main-local.php 2>/dev/null')
    print(f'{env_dir}/backend/config/main-local.php:\n{out}\n')

    # Check cookie_validation_key file
    code, out, _ = run(f'cat {path}/backend/config/cookie_validation_key 2>/dev/null')
    print(f'cookie_validation_key file: {out if out else "NOT FOUND"}')

ssh.close()
print('\nDone!')
