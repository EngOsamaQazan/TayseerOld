import paramiko, os
os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

PROJECT_ROOT = r'c:\Users\PC\Desktop\Tayseer'

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect(HOST, username=USER, password=PASS, timeout=30)
print('Connected!')

# Upload fixed StockController.php to both sites
local_file = os.path.join(PROJECT_ROOT, 'console', 'controllers', 'StockController.php')
sftp = ssh.open_sftp()

for site in ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co']:
    remote_path = f'{site}/console/controllers/StockController.php'
    print(f'\nUploading StockController.php to {site}...')
    sftp.put(local_file, remote_path)
    print('  Done!')

sftp.close()

# Run backfill on jadal
print('\n' + '='*60)
print('  JADAL: php yii stock/backfill')
print('='*60)
stdin, stdout, stderr = ssh.exec_command(
    'cd /var/www/jadal.aqssat.co && echo y | php yii stock/backfill 2>&1',
    timeout=120
)
out = stdout.read().decode('utf-8', errors='replace')
try:
    print(out)
except UnicodeEncodeError:
    print(out.encode('ascii', 'replace').decode())

# Run backfill on namaa
print('\n' + '='*60)
print('  NAMAA: php yii stock/backfill')
print('='*60)
stdin, stdout, stderr = ssh.exec_command(
    'cd /var/www/namaa.aqssat.co && echo y | php yii stock/backfill 2>&1',
    timeout=120
)
out = stdout.read().decode('utf-8', errors='replace')
try:
    print(out)
except UnicodeEncodeError:
    print(out.encode('ascii', 'replace').decode())

# Also upload the updated ContractsController and InventoryItemsController
for fname in ['backend/modules/contracts/controllers/ContractsController.php',
              'backend/modules/inventoryItems/controllers/InventoryItemsController.php']:
    local = os.path.join(PROJECT_ROOT, fname.replace('/', os.sep))
    if os.path.exists(local):
        sftp2 = ssh.open_sftp()
        for site in ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co']:
            remote = f'{site}/{fname}'
            print(f'Uploading {fname} to {site}...')
            sftp2.put(local, remote)
        sftp2.close()

# Restart Apache
print('\nRestarting Apache...')
stdin, stdout, stderr = ssh.exec_command('systemctl restart apache2', timeout=30)
exit_code = stdout.channel.recv_exit_status()
if exit_code == 0:
    print('Apache restarted!')
else:
    print('WARNING: Apache restart failed')

ssh.close()
print('\nAll done!')
