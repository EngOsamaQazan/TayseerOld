import paramiko, os, sys
sys.stdout.reconfigure(encoding='utf-8')

LOCAL_ROOT = r'C:\Users\PC\Desktop\Tayseer'
SERVER = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'
SITES = ['jadal', 'namaa']

FILES = [
    'backend/controllers/SiteController.php',
    'backend/views/site/image-manager.php',
    'backend/web/css/image-manager-admin.css',
]

print('='*60)
print('  DEPLOY: Image Manager Updates')
print('='*60)

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print(f'\nConnecting to {SERVER}...')
ssh.connect(SERVER, username=USER, password=PASS, timeout=30)
sftp = ssh.open_sftp()
print('Connected!\n')

for site in SITES:
    base = f'/var/www/{site}.aqssat.co'
    print(f'--- {site.upper()} ---')
    for rel in FILES:
        local_path = os.path.join(LOCAL_ROOT, rel.replace('/', os.sep))
        remote_path = f'{base}/{rel}'
        try:
            sftp.put(local_path, remote_path)
            print(f'  OK: {rel}')
        except Exception as e:
            print(f'  FAIL: {rel} -> {e}')
    ssh.exec_command(f'chown -R www-data:www-data {base}/backend/')
    ssh.exec_command(f'rm -rf {base}/backend/runtime/cache/*')
    print(f'  Cache cleared.\n')

sftp.close()
ssh.close()
print('DONE!')
