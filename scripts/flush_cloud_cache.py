import paramiko
import time

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

all_cmds = (
    "rm -rf /var/www/jadal.aqssat.co/backend/runtime/cache/* 2>/dev/null; "
    "rm -rf /var/www/jadal.aqssat.co/common/runtime/cache/* 2>/dev/null; "
    "rm -rf /var/www/namaa.aqssat.co/backend/runtime/cache/* 2>/dev/null; "
    "rm -rf /var/www/namaa.aqssat.co/common/runtime/cache/* 2>/dev/null; "
    "systemctl restart apache2; "
    "echo DONE"
)

print(f'Connecting to {HOST}...')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())

for attempt in range(3):
    try:
        ssh.connect(HOST, username=USER, password=PASS, timeout=120, banner_timeout=120)
        print('Connected!')
        break
    except Exception as e:
        print(f'Attempt {attempt+1} failed: {e}')
        time.sleep(5)
else:
    print('Could not connect after 3 attempts')
    exit(1)

transport = ssh.get_transport()
transport.set_keepalive(5)

print(f'Running: {all_cmds[:80]}...')
stdin, stdout, stderr = ssh.exec_command(all_cmds, timeout=120)
out = stdout.read().decode('utf-8', errors='replace').strip()
err = stderr.read().decode('utf-8', errors='replace').strip()
print(f'Output: {out}')
if err:
    print(f'Stderr: {err}')
print(f'Exit: {stdout.channel.recv_exit_status()}')

ssh.close()
