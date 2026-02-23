"""
Full deployment: tar local codebase → upload → extract on server → composer → migrate → cache flush
Replaces ALL files on both jadal and namaa with the local copy.
"""
import paramiko
import subprocess
import sys
import os
import time

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))

SITES = [
    '/var/www/jadal.aqssat.co',
    '/var/www/namaa.aqssat.co',
]

EXCLUDE_DIRS = {
    '.git',
    'vendor',
    'node_modules',
    'backend/web/assets',
    'backend/runtime',
    'frontend/runtime',
    'console/runtime',
    'frontend/web/assets',
}

EXCLUDE_FILES = {
    'common/config/main-local.php',
    'backend/config/main-local.php',
    'frontend/config/main-local.php',
    'console/config/main-local.php',
    'backend/web/index.php',
    'frontend/web/index.php',
    'yii',
    'cookie_validation_key',
}

TAR_NAME = 'tayseer-deploy.tar.gz'
REMOTE_TAR = f'/tmp/{TAR_NAME}'


def run_cmd(ssh, cmd, timeout=300):
    print(f"  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        for line in out.strip().split('\n')[:50]:
            try:
                print(f"    {line}")
            except UnicodeEncodeError:
                print(f"    {line.encode('ascii', 'replace').decode()}")
    if err.strip():
        for line in err.strip().split('\n')[:20]:
            try:
                print(f"    [err] {line}")
            except UnicodeEncodeError:
                print(f"    [err] {line.encode('ascii', 'replace').decode()}")
    if exit_code != 0:
        print(f"  EXIT CODE: {exit_code}")
    return exit_code, out, err


def create_tar():
    """Create a tar.gz from git-tracked files (respects .gitignore automatically)."""
    local_tar = os.path.join(PROJECT_ROOT, TAR_NAME)
    print(f"\n[1/4] Creating archive from local files...")
    print(f"  Source: {PROJECT_ROOT}")

    result = subprocess.run(
        ['git', 'archive', '--format=tar.gz', '-o', local_tar, 'HEAD'],
        cwd=PROJECT_ROOT,
        capture_output=True, text=True
    )
    if result.returncode != 0:
        print(f"  ERROR: git archive failed: {result.stderr}")
        sys.exit(1)

    size_mb = os.path.getsize(local_tar) / (1024 * 1024)
    print(f"  Archive created: {TAR_NAME} ({size_mb:.1f} MB)")
    return local_tar


def upload_tar(ssh, local_tar):
    """Upload the archive to the server via SFTP."""
    print(f"\n[2/4] Uploading archive to server...")
    sftp = ssh.open_sftp()

    file_size = os.path.getsize(local_tar)
    uploaded = [0]
    last_print = [0]

    def progress(transferred, total):
        uploaded[0] = transferred
        pct = (transferred / total) * 100
        if pct - last_print[0] >= 10 or pct >= 99.9:
            print(f"    {pct:.0f}% ({transferred // (1024*1024)} / {total // (1024*1024)} MB)")
            last_print[0] = pct

    start = time.time()
    sftp.put(local_tar, REMOTE_TAR, callback=progress)
    elapsed = time.time() - start
    print(f"  Upload complete in {elapsed:.1f}s")
    sftp.close()


def deploy_site(ssh, site_path):
    """Extract archive into site, run composer + migrate + cache flush."""
    site_name = site_path.split('/')[-1]
    print(f"\n{'='*60}")
    print(f"  Deploying: {site_name}")
    print(f"{'='*60}")

    bak = f"{site_path}/_deploy_backup"
    run_cmd(ssh, f"mkdir -p {bak}")

    config_files = [
        'common/config/main-local.php',
        'backend/config/main-local.php',
        'frontend/config/main-local.php',
        'console/config/main-local.php',
        'backend/web/index.php',
        'frontend/web/index.php',
        'yii',
    ]
    for f in config_files:
        safe_name = f.replace('/', '__')
        run_cmd(ssh, f"cp -f {site_path}/{f} {bak}/{safe_name} 2>/dev/null; true")

    print(f"\n  Extracting archive into {site_path}...")
    run_cmd(ssh, f"tar -xzf {REMOTE_TAR} -C {site_path} --overwrite")

    for f in config_files:
        safe_name = f.replace('/', '__')
        run_cmd(ssh, f"cp -f {bak}/{safe_name} {site_path}/{f} 2>/dev/null; true")

    run_cmd(ssh, f"rm -rf {bak}")

    env_dir = 'environments/prod_jadal' if 'jadal' in site_path else 'environments/prod_namaa'
    for sub in ['common', 'backend', 'frontend', 'console']:
        src = f"{site_path}/{env_dir}/{sub}/config/main-local.php"
        dst = f"{site_path}/{sub}/config/main-local.php"
        run_cmd(ssh, f"test -f {src} && cp -f {src} {dst} 2>/dev/null; true")

    run_cmd(ssh, f"mkdir -p {site_path}/backend/runtime {site_path}/frontend/runtime {site_path}/console/runtime {site_path}/backend/web/assets {site_path}/frontend/web/assets")
    run_cmd(ssh, f"chmod -R 777 {site_path}/backend/runtime {site_path}/frontend/runtime {site_path}/console/runtime {site_path}/backend/web/assets {site_path}/frontend/web/assets 2>/dev/null; true")

    print(f"\n  Running composer install...")
    code, _, _ = run_cmd(ssh,
        f"cd {site_path} && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction 2>&1",
        timeout=600)
    if code != 0:
        print(f"  WARNING: composer install failed, trying update...")
        run_cmd(ssh,
            f"cd {site_path} && COMPOSER_ALLOW_SUPERUSER=1 composer update --no-dev --optimize-autoloader --no-interaction 2>&1",
            timeout=600)

    print(f"\n  Running migrations...")
    run_cmd(ssh, f"cd {site_path} && php yii migrate --interactive=0 2>&1", timeout=120)

    print(f"\n  Flushing cache...")
    run_cmd(ssh, f"cd {site_path} && php yii cache/flush-all 2>&1")

    run_cmd(ssh, f"chown -R www-data:www-data {site_path} 2>/dev/null; true")

    print(f"\n  {site_name} DONE!")


def main():
    local_tar = create_tar()

    print(f"\n[2/4] Connecting to {HOST}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    print("  Connected!")

    run_cmd(ssh, "php -v | head -1")

    upload_tar(ssh, local_tar)

    print(f"\n[3/4] Deploying to sites...")
    for site in SITES:
        deploy_site(ssh, site)

    print(f"\n[4/4] Restarting Apache to clear PHP opcache...")
    code, _, _ = run_cmd(ssh, "systemctl restart apache2")
    if code == 0:
        print("  Apache restarted — opcache cleared!")
    else:
        print("  WARNING: Apache restart failed, opcache may serve stale files")

    print(f"\n[5/5] Cleanup...")
    run_cmd(ssh, f"rm -f {REMOTE_TAR}")
    os.remove(local_tar)
    print(f"  Removed temp archives")

    print(f"\n{'='*60}")
    print("  ALL DEPLOYMENTS COMPLETE!")
    print(f"  Both jadal and namaa have been fully replaced")
    print(f"  with your local codebase.")
    print(f"{'='*60}")

    ssh.close()


if __name__ == '__main__':
    main()
