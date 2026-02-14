import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    ('Jadal Full Error Log (last 50 lines)', 'tail -50 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null'),
    ('Namaa Full Error Log (last 50 lines)', 'tail -50 /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null'),
    ('Jadal Frontend Error Log', 'tail -30 /var/www/jadal.aqssat.co/frontend/runtime/logs/app.log 2>/dev/null'),
    ('Namaa Frontend Error Log', 'tail -30 /var/www/namaa.aqssat.co/frontend/runtime/logs/app.log 2>/dev/null'),
    ('Apache Error Log', 'tail -30 /var/log/apache2/error.log 2>/dev/null'),
]

for label, cmd in commands:
    print(f'=== {label} ===')
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=15)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    if err:
        print(f'STDERR: {err}')
    if not out and not err:
        print('(no output)')
    print()

ssh.close()
