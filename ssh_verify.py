import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    ('1. Jadal Main Page', 'curl -sI https://jadal.aqssat.co/ 2>/dev/null | head -5'),
    ('2. Jadal Customers Create', 'curl -sI https://jadal.aqssat.co/customers/create 2>/dev/null | head -5'),
    ('3. Jadal Image Manager', 'curl -sI https://jadal.aqssat.co/site/image-manager 2>/dev/null | head -5'),
    ('4. Jadal System Settings', 'curl -sI https://jadal.aqssat.co/site/system-settings 2>/dev/null | head -5'),
    ('5. Jadal Font', 'curl -sI https://jadal.aqssat.co/css/font/OpenSans-Regular.ttf 2>/dev/null | head -5'),
    ('6. Namaa Main Page', 'curl -sI https://namaa.aqssat.co/ 2>/dev/null | head -5'),
    ('7. Namaa Customers Create', 'curl -sI https://namaa.aqssat.co/customers/create 2>/dev/null | head -5'),
    ('8. Namaa Image Manager', 'curl -sI https://namaa.aqssat.co/site/image-manager 2>/dev/null | head -5'),
    ('9. Namaa System Settings', 'curl -sI https://namaa.aqssat.co/site/system-settings 2>/dev/null | head -5'),
    ('10. Jadal PHP Error Logs', 'tail -5 /var/www/jadal.aqssat.co/backend/runtime/logs/app.log 2>/dev/null; echo DONE'),
    ('11. Namaa PHP Error Logs', 'tail -5 /var/www/namaa.aqssat.co/backend/runtime/logs/app.log 2>/dev/null; echo DONE'),
    ('12. Jadal API Endpoint', 'curl -sI https://jadal.aqssat.co/api/v1/customer-images 2>/dev/null | head -5'),
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
print('All checks complete.')
