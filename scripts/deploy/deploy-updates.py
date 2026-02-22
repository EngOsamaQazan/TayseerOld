"""
═══════════════════════════════════════════════════════════
  نشر التحديثات فقط - رفع الملفات الجديدة/المحدثة
  Incremental deployment - upload only new/changed files
═══════════════════════════════════════════════════════════
Steps:
  1. Compare local vs remote by MD5 (أدق من الحجم - أي تغيير يغيّر الـ hash)
  2. Upload only new or changed files
  3. Skip server configs (main-local, params-local, etc.)
  4. Run composer dump-autoload
  5. Set permissions & clear cache
"""
import paramiko, sys, os, time, io, hashlib
sys.stdout.reconfigure(encoding='utf-8')

BATCH_SIZE = 60  # عدد الملفات لكل استعلام md5sum على السيرفر

PROJECT_DIR = r'C:\Users\PC\Desktop\Tayseer'

# ─── Server-specific configs: NEVER overwrite these ───
SKIP_PATHS = [
    'common/config/main-local.php',
    'backend/config/main-local.php',
    'frontend/config/main-local.php',
    'common/config/params-local.php',
    'common/config/params.php',
    'backend/config/main.php',
    'api/config/main.php',
]

# ─── Files/dirs to EXCLUDE from sync ───
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
    p = path.replace('\\', '/')
    for pattern in EXCLUDE_PATTERNS:
        if pattern.startswith('.') and '/' not in pattern:
            if p.endswith(pattern):
                return True
        else:
            if f'/{pattern}/' in f'/{p}/' or f'/{pattern}' == f'/{p}' or p.startswith(f'{pattern}/') or p == pattern:
                return True
    return False

def should_skip_config(path):
    """Check if path is a server config we must never overwrite."""
    p = path.replace('\\', '/')
    return p in SKIP_PATHS

def file_md5(path):
    """Calculate MD5 hash of local file (أي تغيير ولو حرف واحد يغيّر الناتج)."""
    h = hashlib.md5()
    with open(path, 'rb') as f:
        for chunk in iter(lambda: f.read(65536), b''):
            h.update(chunk)
    return h.hexdigest()

def get_remote_hashes_batch(ssh, server_path, paths, run_fn):
    """Get MD5 hashes of multiple files on server in one command (أسرع من استعلام لكل ملف)."""
    result = {}
    for i in range(0, len(paths), BATCH_SIZE):
        batch = paths[i:i + BATCH_SIZE]
        full_paths = [f"{server_path}/{p}" for p in batch]
        cmd = "md5sum " + " ".join(f'"{p}"' for p in full_paths) + " 2>/dev/null"
        out, _ = run_fn(f"cd / && {cmd}")
        for line in out.splitlines():
            parts = line.split(None, 1)
            if len(parts) >= 2:
                h, p = parts[0], parts[1].strip().lstrip('*')
                rel = p.replace(server_path + '/', '').replace(server_path, '').strip('/')
                result[rel] = h
    return result

def sync_to_server(ssh, sftp, server_name, server_path):
    """Sync only new/changed files to a single server."""
    print(f"\n{'='*60}")
    print(f"  SYNCING UPDATES TO: {server_name} ({server_path})")
    print(f"{'='*60}")

    def run(cmd, timeout=120):
        stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
        out = stdout.read().decode('utf-8', errors='replace').strip()
        err = stderr.read().decode('utf-8', errors='replace').strip()
        return out, err

    # Collect local files
    local_files = []
    for root, dirs, files in os.walk(PROJECT_DIR):
        dirs[:] = [d for d in dirs if not d.startswith('.') and not should_exclude(os.path.relpath(os.path.join(root, d), PROJECT_DIR))]
        for f in files:
            if f.startswith('.') and f not in ['.htaccess', '.gitignore', '.bowerrc']:
                continue
            full_path = os.path.join(root, f)
            rel_path = os.path.relpath(full_path, PROJECT_DIR).replace('\\', '/')
            if should_exclude(rel_path) or should_skip_config(rel_path):
                continue
            try:
                local_files.append((rel_path, full_path))
            except Exception as e:
                print(f"  ⚠ Skip: {rel_path}: {e}")

    print(f"\n[1/5] Scanning & comparing by MD5...")
    print("  Computing local MD5 hashes...")
    local_hashes = {rel: file_md5(full) for rel, full in local_files}

    print("  Getting remote MD5 hashes...")
    remote_hashes = get_remote_hashes_batch(ssh, server_path, [r for r, _ in local_files], run)

    # Compare by MD5 and upload (أي فرق في المحتوى = hash مختلف)
    uploaded = 0
    skipped = 0
    for rel_path, full_path in local_files:
        local_md5 = local_hashes[rel_path]
        remote_md5 = remote_hashes.get(rel_path)
        if remote_md5 == local_md5:
            skipped += 1
            continue

        remote_path = f"{server_path}/{rel_path}"
        remote_dir = os.path.dirname(remote_path)
        try:
            sftp.stat(remote_dir)
        except (IOError, FileNotFoundError):
            parts = remote_dir.replace(server_path, '').strip('/').split('/')
            current = server_path
            for part in parts:
                current = f"{current}/{part}"
                try:
                    sftp.stat(current)
                except (IOError, FileNotFoundError):
                    sftp.mkdir(current)

        try:
            sftp.put(full_path, remote_path)
            uploaded += 1
            print(f"  ↑ {rel_path}")
        except Exception as e:
            print(f"  ✗ {rel_path}: {e}")

    print(f"\n  ✓ Uploaded: {uploaded} files | Skipped (identical MD5): {skipped}")

    # ── Verify by MD5: تأكيد أن كل الملفات مطابقة محتوىً (ليس مجرد الحجم) ──
    print("\n  Verifying by MD5 (checksum)...")
    remote_hashes_after = get_remote_hashes_batch(ssh, server_path, [r for r, _ in local_files], run)
    verified = 0
    failed = []
    for rel_path, full_path in local_files:
        local_md5 = local_hashes[rel_path]
        remote_md5 = remote_hashes_after.get(rel_path)
        if remote_md5 == local_md5:
            verified += 1
        elif remote_md5 is None:
            failed.append((rel_path, "missing on server"))
        else:
            failed.append((rel_path, f"hash mismatch: local={local_md5[:8]}.. remote={remote_md5[:8]}.."))
    if failed:
        for path, reason in failed:
            print(f"  ✗ {path}: {reason}")
    print(f"  ✓ Verified: {verified}/{len(local_files)} files match (MD5)")

    # Step 2: Composer
    print("\n[2/5] Running composer dump-autoload...")
    out, _ = run(f'cd {server_path} && COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --no-interaction 2>&1', timeout=120)
    print(f"  ✓ Done")

    # Step 3: Permissions
    print("\n[3/5] Setting permissions...")
    run(f'chown -R www-data:www-data {server_path}')
    run(f'chmod -R 775 {server_path}/backend/runtime {server_path}/frontend/runtime {server_path}/console/runtime {server_path}/api/runtime 2>/dev/null')
    run(f'chmod -R 775 {server_path}/backend/web/assets {server_path}/frontend/web/assets 2>/dev/null')
    run(f'chmod -R 775 {server_path}/backend/web/images {server_path}/backend/web/uploads 2>/dev/null')
    run(f'chmod 755 {server_path}/yii 2>/dev/null')
    print(f"  ✓ Done")

    # Step 4: Clear cache
    print("\n[4/5] Clearing cache...")
    run(f'rm -rf {server_path}/backend/runtime/cache/*')
    run(f'rm -rf {server_path}/frontend/runtime/cache/*')
    print(f"  ✓ Done")

    # Step 5: Key files check
    print("\n[5/5] Key files check...")
    checks = [
        'backend/controllers/SiteController.php',
        'common/config/main-local.php',
        'vendor/autoload.php',
    ]
    all_ok = len(failed) == 0
    for check in checks:
        try:
            sftp.stat(f'{server_path}/{check}')
            print(f"  ✓ {check}")
        except:
            print(f"  ✗ {check} MISSING")
            all_ok = False

    return all_ok, len(local_files), verified, len(failed)

# ═══════════════════════════════════════════════════════
#  MAIN
# ═══════════════════════════════════════════════════════

print("╔══════════════════════════════════════════════╗")
print("║   Incremental Update - Jadal & Namaa         ║")
print("║   رفع التحديثات فقط (بدون استبدال كامل)      ║")
print("╚══════════════════════════════════════════════╝")

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
print("\nConnecting to server...")
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=60, banner_timeout=60, auth_timeout=60)
sftp = ssh.open_sftp()
print("  ✓ Connected")

jadal_result = sync_to_server(ssh, sftp, 'jadal', '/var/www/jadal.aqssat.co')
namaa_result = sync_to_server(ssh, sftp, 'namaa', '/var/www/namaa.aqssat.co')

# Image sync
print("\n" + "="*60)
print("  Running image sync...")
print("="*60)
stdin, stdout, stderr = ssh.exec_command('/usr/local/bin/sync-images.sh 2>&1', timeout=60)
stdout.read()
print(f"  ✓ Image sync complete")

sftp.close()
ssh.close()

# Final confirmation
jadal_ok, j_total, j_verified, _ = jadal_result
namaa_ok, n_total, n_verified, _ = namaa_result

print("\n" + "═"*60)
print("  تأكيد الرفع / UPLOAD CONFIRMATION")
print("═"*60)
print(f"  Jadal: {'✓ الكود كله مرتفع ومطابق' if jadal_ok else '✗ يوجد مشاكل'} ({j_verified}/{j_total} ملفات مؤكدة)")
print(f"  Namaa: {'✓ الكود كله مرتفع ومطابق' if namaa_ok else '✗ يوجد مشاكل'} ({n_verified}/{n_total} ملفات مؤكدة)")
print("═"*60)
