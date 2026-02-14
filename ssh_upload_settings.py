import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)
sftp = ssh.open_sftp()

local = r'C:\Users\PC\Desktop\Tayseer\backend\views\site\system-settings.php'
for server in ['jadal', 'namaa']:
    remote = f'/var/www/{server}.aqssat.co/backend/views/site/system-settings.php'
    sftp.put(local, remote)
    ssh.exec_command(f'chown www-data:www-data {remote}')
    ssh.exec_command(f'rm -rf /var/www/{server}.aqssat.co/backend/runtime/cache/*')
    print(f'  ✓ {server}: system-settings.php uploaded')

sftp.close()
ssh.close()
print('Done')
