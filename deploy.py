"""
═══════════════════════════════════════════════════════════
  نشر شامل - رفع المشروع الكامل على جدل ونماء
  Full deployment - upload entire project to Jadal & Namaa
═══════════════════════════════════════════════════════════
Steps:
  1. Create tar.gz of local project (excluding vendor, runtime, .git, user uploads, .py scripts, .sql)
  2. Backup server-specific configs on remote
  3. Upload tar.gz
  4. Extract to temp dir, then swap
  5. Restore server configs (main-local.php, params-local.php, cookie keys)
  6. Restore images/uploads from backup
  7. Run composer install
  8. Set permissions
  9. Clear cache
  10. Verify
"""
import paramiko, sys, os, tarfile, io, time, stat
sys.stdout.reconfigure(encoding='utf-8')

PROJECT_DIR = r'C:\Users\PC\Desktop\Tayseer'

# ─── Server-specific configs that must be preserved ───
JADAL_CONFIGS = {
    'common/config/main-local.php': None,   # Will be read from server
    'backend/config/main-local.php': None,
    'frontend/config/main-local.php': None,
    'common/config/params-local.php': None,
}

NAMAA_CONFIGS = {
    'common/config/main-local.php': None,
    'backend/config/main-local.php': None,
    'frontend/config/main-local.php': None,
    'common/config/params-local.php': None,
}

# ─── Files/dirs to EXCLUDE from the tar ───
EXCLUDE_PATTERNS = [
    '.git',
    'vendor',
    'node_modules',
    'backend/runtime',
    'frontend/runtime',
    'console/runtime',
    'api/runtime',
    'backend/web/images',
    'backend/web/uploads',
    'backend/web/assets',
    'frontend/web/assets',
    '_scripts',
    '_backups',
    'database',
    'DockerFilesOld',
    '.sql',
    '.py',
    '.ps1',
]

def should_exclude(path):
    """Check if a file path should be excluded."""
    # Normalize path
    p = path.replace('\\', '/')
    for pattern in EXCLUDE_PATTERNS:
        if pattern.startswith('.') and not '/' in pattern:
            # Extension match
            if p.endswith(pattern):
                return True
        else:
            # Directory/path match
            if f'/{pattern}/' in f'/{p}/' or f'/{pattern}' == f'/{p}' or p.startswith(f'{pattern}/') or p == pattern:
                return True
    return False

def create_tar():
    """Create tar.gz of the project."""
    print("Creating project archive...")
    buf = io.BytesIO()
    count = 0
    with tarfile.open(fileobj=buf, mode='w:gz') as tar:
        for root, dirs, files in os.walk(PROJECT_DIR):
            # Skip hidden dirs and excluded dirs
            dirs[:] = [d for d in dirs if not d.startswith('.') and not should_exclude(os.path.relpath(os.path.join(root, d), PROJECT_DIR))]
            
            for f in files:
                if f.startswith('.') and f not in ['.htaccess', '.gitignore', '.bowerrc']:
                    continue
                
                full_path = os.path.join(root, f)
                rel_path = os.path.relpath(full_path, PROJECT_DIR)
                
                if should_exclude(rel_path):
                    continue
                
                # Add to tar with 'project/' prefix
                arcname = 'project/' + rel_path.replace('\\', '/')
                try:
                    tar.add(full_path, arcname=arcname)
                    count += 1
                except Exception as e:
                    print(f"  ⚠ Skip: {rel_path}: {e}")
    
    print(f"  ✓ Archive created: {count} files, {buf.tell() / 1024 / 1024:.1f} MB")
    buf.seek(0)
    return buf

def deploy_to_server(ssh, sftp, server_name, server_path, configs, tar_buf):
    """Deploy to a single server."""
    print(f"\n{'='*60}")
    print(f"  DEPLOYING TO: {server_name} ({server_path})")
    print(f"{'='*60}")
    
    def run(cmd, timeout=120):
        stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
        out = stdout.read().decode('utf-8', errors='replace').strip()
        err = stderr.read().decode('utf-8', errors='replace').strip()
        return out, err
    
    # ── Step 1: Backup server configs ──
    print("\n[1/8] Backing up server configs...")
    for config_path in configs:
        full = f"{server_path}/{config_path}"
        out, _ = run(f'cat {full} 2>/dev/null')
        if out:
            configs[config_path] = out
            print(f"  ✓ Saved: {config_path} ({len(out)} bytes)")
        else:
            print(f"  — Missing: {config_path}")
    
    # ── Step 2: Backup backend main.php (has server-specific module configs) ──
    print("\n[2/8] Backing up backend/config/main.php...")
    out, _ = run(f'cat {server_path}/backend/config/main.php 2>/dev/null')
    server_main_php = out if out else None
    if server_main_php:
        print(f"  ✓ Saved: backend/config/main.php ({len(server_main_php)} bytes)")
    
    # Also backup api/config/main.php
    out, _ = run(f'cat {server_path}/api/config/main.php 2>/dev/null')
    server_api_main = out if out else None
    if server_api_main:
        print(f"  ✓ Saved: api/config/main.php ({len(server_api_main)} bytes)")
    
    # Also backup common/config/params.php (has SMS keys etc)
    out, _ = run(f'cat {server_path}/common/config/params.php 2>/dev/null')
    server_params = out if out else None
    if server_params:
        print(f"  ✓ Saved: common/config/params.php ({len(server_params)} bytes)")
    
    # ── Step 3: Upload tar ──
    print("\n[3/8] Uploading project archive...")
    tar_buf.seek(0)
    remote_tar = f'/tmp/project_{server_name}.tar.gz'
    sftp.putfo(tar_buf, remote_tar)
    out, _ = run(f'stat -c %s {remote_tar}')
    print(f"  ✓ Uploaded: {int(out or 0) / 1024 / 1024:.1f} MB")
    
    # ── Step 4: Extract and swap ──
    print("\n[4/8] Extracting and replacing code...")
    tmp_dir = f'/tmp/deploy_{server_name}_{int(time.time())}'
    backup_dir = f'{server_path}_backup_{int(time.time())}'
    
    run(f'mkdir -p {tmp_dir}')
    out, err = run(f'cd {tmp_dir} && tar xzf {remote_tar}', timeout=120)
    if err and 'error' in err.lower():
        print(f"  ✗ Extract error: {err}")
        return False
    
    out, _ = run(f'ls {tmp_dir}/project/ | head -5')
    print(f"  ✓ Extracted: {out}")
    
    # Move images and uploads OUT before swap
    print("  Moving user data out...")
    run(f'mkdir -p /tmp/preserve_{server_name}')
    run(f'mv {server_path}/backend/web/images /tmp/preserve_{server_name}/images 2>/dev/null')
    run(f'mv {server_path}/backend/web/uploads /tmp/preserve_{server_name}/uploads 2>/dev/null')
    # Also preserve vendor
    run(f'mv {server_path}/vendor /tmp/preserve_{server_name}/vendor 2>/dev/null')
    # Preserve runtime dirs (for logs)
    run(f'mv {server_path}/backend/runtime /tmp/preserve_{server_name}/backend_runtime 2>/dev/null')
    run(f'mv {server_path}/frontend/runtime /tmp/preserve_{server_name}/frontend_runtime 2>/dev/null')
    run(f'mv {server_path}/console/runtime /tmp/preserve_{server_name}/console_runtime 2>/dev/null')
    run(f'mv {server_path}/api/runtime /tmp/preserve_{server_name}/api_runtime 2>/dev/null')
    # Preserve backend/web/assets
    run(f'mv {server_path}/backend/web/assets /tmp/preserve_{server_name}/backend_assets 2>/dev/null')
    run(f'mv {server_path}/frontend/web/assets /tmp/preserve_{server_name}/frontend_assets 2>/dev/null')
    
    # Delete old code and replace with new
    run(f'rm -rf {server_path}/*')
    run(f'cp -a {tmp_dir}/project/* {server_path}/')
    run(f'cp -a {tmp_dir}/project/.htaccess {server_path}/ 2>/dev/null')
    run(f'cp -a {tmp_dir}/project/.bowerrc {server_path}/ 2>/dev/null')
    
    out, _ = run(f'ls {server_path}/ | wc -l')
    print(f"  ✓ New code deployed: {out} items")
    
    # ── Step 5: Restore preserved data ──
    print("\n[5/8] Restoring user data & vendor...")
    run(f'mv /tmp/preserve_{server_name}/images {server_path}/backend/web/images 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/uploads {server_path}/backend/web/uploads 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/vendor {server_path}/vendor 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/backend_runtime {server_path}/backend/runtime 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/frontend_runtime {server_path}/frontend/runtime 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/console_runtime {server_path}/console/runtime 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/api_runtime {server_path}/api/runtime 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/backend_assets {server_path}/backend/web/assets 2>/dev/null')
    run(f'mv /tmp/preserve_{server_name}/frontend_assets {server_path}/frontend/web/assets 2>/dev/null')
    
    # Ensure dirs exist
    run(f'mkdir -p {server_path}/backend/runtime/cache {server_path}/backend/runtime/logs')
    run(f'mkdir -p {server_path}/frontend/runtime {server_path}/console/runtime {server_path}/api/runtime')
    run(f'mkdir -p {server_path}/backend/web/images/imagemanager')
    run(f'mkdir -p {server_path}/backend/web/uploads/customers/documents')
    run(f'mkdir -p {server_path}/backend/web/uploads/customers/photos')
    run(f'mkdir -p {server_path}/backend/web/assets {server_path}/frontend/web/assets')
    
    print("  ✓ User data restored")
    
    # ── Step 6: Restore server configs ──
    print("\n[6/8] Restoring server-specific configs...")
    for config_path, content in configs.items():
        if content:
            full = f"{server_path}/{config_path}"
            run(f'mkdir -p $(dirname {full})')
            sftp.putfo(io.BytesIO(content.encode('utf-8')), full)
            print(f"  ✓ Restored: {config_path}")
    
    # Restore server's main.php configs (they have SMS creds, module configs etc.)
    if server_main_php:
        sftp.putfo(io.BytesIO(server_main_php.encode('utf-8')), f'{server_path}/backend/config/main.php')
        print(f"  ✓ Restored: backend/config/main.php (server version)")
    
    if server_api_main:
        run(f'mkdir -p {server_path}/api/config')
        sftp.putfo(io.BytesIO(server_api_main.encode('utf-8')), f'{server_path}/api/config/main.php')
        print(f"  ✓ Restored: api/config/main.php (server version)")
    
    if server_params:
        sftp.putfo(io.BytesIO(server_params.encode('utf-8')), f'{server_path}/common/config/params.php')
        print(f"  ✓ Restored: common/config/params.php (server version with SMS keys)")
    
    # ── Step 7: Composer & permissions ──
    print("\n[7/8] Running composer & fixing permissions...")
    out, err = run(f'cd {server_path} && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-interaction 2>&1', timeout=120)
    print(f"  Composer: {out[-200:] if len(out) > 200 else out}")
    
    # Set permissions
    run(f'chown -R www-data:www-data {server_path}')
    run(f'chmod -R 775 {server_path}/backend/runtime {server_path}/frontend/runtime {server_path}/console/runtime')
    run(f'chmod -R 775 {server_path}/backend/web/assets {server_path}/frontend/web/assets')
    run(f'chmod -R 775 {server_path}/backend/web/images {server_path}/backend/web/uploads')
    run(f'chmod 755 {server_path}/yii')
    print("  ✓ Permissions set")
    
    # ── Step 8: Clear cache & verify ──
    print("\n[8/8] Clearing cache & verifying...")
    # Fix designation module filename for Linux (Windows tar may use lowercase)
    run(f'if [ -f {server_path}/backend/modules/designation/designation.php ]; then mv {server_path}/backend/modules/designation/designation.php {server_path}/backend/modules/designation/Designation.php; echo "  ✓ designation module filename fixed"; fi')
    run(f'rm -rf {server_path}/backend/runtime/cache/*')
    run(f'rm -rf {server_path}/frontend/runtime/cache/*')
    
    # Upload fonts (they're not in git)
    print("  Re-uploading fonts...")
    font_dir = f'{server_path}/backend/web/css/font'
    members_font_dir = f'{server_path}/backend/web/css/members/font'
    run(f'mkdir -p {font_dir} {members_font_dir}')
    
    # Copy fonts from old server if available
    old_path = server_path.replace(f'{server_name}.aqssat.co', f'old.{server_name}.aqssat.co')
    run(f'cp -a {old_path}/backend/web/css/font/*.ttf {font_dir}/ 2>/dev/null')
    run(f'cp -a {old_path}/backend/web/css/members/font/*.ttf {members_font_dir}/ 2>/dev/null')
    
    # Verify font sizes
    out, _ = run(f'ls -la {font_dir}/OpenSans-Regular.ttf 2>/dev/null')
    if '217276' in out:
        print(f"  ✓ Fonts OK")
    else:
        print(f"  ⚠ Font check: {out}")
    
    # Verify key files
    checks = [
        'backend/modules/designation/Designation.php',
        'backend/controllers/SiteController.php',
        'backend/views/site/image-manager.php',
        'backend/views/site/system-settings.php',
        'backend/web/css/image-manager-admin.css',
        'backend/web/js/smart-media.js',
        'backend/web/js/smart-onboarding.js',
        'backend/modules/customers/controllers/CustomersController.php',
        'backend/modules/customers/views/customers/_smart_form.php',
        'common/config/main-local.php',
        'vendor/autoload.php',
    ]
    
    print("\n  Verification:")
    all_ok = True
    for check in checks:
        out, _ = run(f'test -f {server_path}/{check} && wc -l < {server_path}/{check} || echo "MISSING"')
        status = '✓' if out != 'MISSING' else '✗'
        if out == 'MISSING': all_ok = False
        print(f"    {status} {check}: {out} lines")
    
    # Cleanup
    run(f'rm -rf {tmp_dir} {remote_tar} /tmp/preserve_{server_name}')
    
    return all_ok

# ═══════════════════════════════════════════════════════
#  MAIN
# ═══════════════════════════════════════════════════════

print("╔══════════════════════════════════════════════╗")
print("║   Full Project Deployment - Jadal & Namaa    ║")
print("╚══════════════════════════════════════════════╝")

# Create archive
tar_buf = create_tar()

# Connect
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print("\nConnecting to server...")
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)
sftp = ssh.open_sftp()
print("  ✓ Connected")

# Deploy to Jadal
jadal_ok = deploy_to_server(ssh, sftp, 'jadal', '/var/www/jadal.aqssat.co', JADAL_CONFIGS, tar_buf)

# Deploy to Namaa
namaa_ok = deploy_to_server(ssh, sftp, 'namaa', '/var/www/namaa.aqssat.co', NAMAA_CONFIGS, tar_buf)

# Final sync (ensure images are synced)
print("\n" + "="*60)
print("  FINAL: Running image sync...")
print("="*60)
stdin, stdout, stderr = ssh.exec_command('/usr/local/bin/sync-images.sh 2>&1', timeout=60)
out = stdout.read().decode('utf-8', errors='replace').strip()
print(f"  ✓ Image sync complete")

sftp.close()
ssh.close()

print("\n" + "═"*60)
print(f"  Jadal: {'✓ SUCCESS' if jadal_ok else '✗ ISSUES'}")
print(f"  Namaa: {'✓ SUCCESS' if namaa_ok else '✗ ISSUES'}")
print("═"*60)
