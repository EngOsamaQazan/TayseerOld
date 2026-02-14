import paramiko, os, sys, stat
sys.stdout.reconfigure(encoding='utf-8')

LOCAL_ROOT = r'C:\Users\PC\Desktop\Tayseer'
SERVER = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'
SITES = ['jadal', 'namaa']

# All files to deploy (relative to project root)
FILES = [
    'backend/modules/contracts/controllers/ContractsController.php',
    'backend/modules/contracts/models/Contracts.php',
    'backend/modules/contracts/views/contracts/index-legal-department.php',
    'backend/modules/contracts/views/contracts/_legal_search_v2.php',
    'backend/modules/customers/views/customers/partial/customer_documents.php',
    'backend/modules/followUp/views/follow-up/panel.php',
    'backend/modules/followUpReport/controllers/FollowUpReportController.php',
    'backend/modules/followUpReport/models/FollowUpReport.php',
    'backend/modules/followUpReport/models/FollowUpReportSearch.php',
    'backend/modules/followUpReport/models/FollowUpNoContact.php',
    'backend/modules/followUpReport/views/follow-up-report/index.php',
    'backend/modules/followUpReport/views/follow-up-report/no-contact.php',
    'backend/modules/judiciary/views/judiciary/create.php',
    'backend/views/layouts/_menu_items.php',
    'backend/views/site/image-manager.php',
    'backend/views/site/system-settings.php',
    'backend/web/css/contracts-v2.css',
    'backend/web/css/image-manager-admin.css',
    'backend/web/css/ocp.css',
]

def ensure_remote_dir(sftp, remote_dir):
    """Create remote directory tree if it doesn't exist."""
    dirs_to_create = []
    d = remote_dir
    while d and d != '/':
        try:
            sftp.stat(d)
            break
        except FileNotFoundError:
            dirs_to_create.insert(0, d)
            d = os.path.dirname(d)
    for dd in dirs_to_create:
        try:
            sftp.mkdir(dd)
        except Exception:
            pass

print('='*60)
print('  FULL DEPLOYMENT — Jadal & Namaa')
print('='*60)

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print(f'\nConnecting to {SERVER}...')
ssh.connect(SERVER, username=USER, password=PASS, timeout=30)
sftp = ssh.open_sftp()
print('Connected!\n')

for site in SITES:
    base = f'/var/www/{site}.aqssat.co'
    print(f'--- Deploying to {site.upper()} ({base}) ---')
    ok = 0
    fail = 0
    for rel in FILES:
        local_path = os.path.join(LOCAL_ROOT, rel.replace('/', os.sep))
        remote_path = f'{base}/{rel}'
        if not os.path.exists(local_path):
            print(f'  SKIP (not found locally): {rel}')
            fail += 1
            continue
        try:
            remote_dir = os.path.dirname(remote_path).replace('\\', '/')
            ensure_remote_dir(sftp, remote_dir)
            sftp.put(local_path, remote_path)
            ok += 1
        except Exception as e:
            print(f'  FAIL: {rel} -> {e}')
            fail += 1

    # Clear cache
    cache_path = f'{base}/backend/runtime/cache'
    ssh.exec_command(f'rm -rf {cache_path}/*')

    # Set permissions
    ssh.exec_command(f'chown -R www-data:www-data {base}/backend/')

    print(f'  Uploaded: {ok}/{len(FILES)} files, Failed: {fail}')
    print(f'  Cache cleared, permissions set.\n')

sftp.close()
ssh.close()
print('='*60)
print('  DEPLOYMENT COMPLETE')
print('='*60)
