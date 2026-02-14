"""
Full sync: Copy ALL images from old.* to new * directories
for both Jadal and Namaa - not just today's files
"""
import paramiko, sys, time
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

def run(cmd, timeout=120):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

print("=" * 60)
print("  FULL IMAGE SYNC: old → new (ALL files)")
print("=" * 60)

# ── Before counts ──
print("\n── Before Sync ──")
pairs = [
    ('JADAL old→new', '/var/www/old.jadal.aqssat.co', '/var/www/jadal.aqssat.co'),
    ('NAMAA old→new', '/var/www/old.namaa.aqssat.co', '/var/www/namaa.aqssat.co'),
]

for label, old_root, new_root in pairs:
    # imagemanager
    old_count, _ = run(f'ls {old_root}/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
    new_count, _ = run(f'ls {new_root}/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
    print(f"  {label} imagemanager: old={old_count}, new={new_count}, diff={int(old_count or 0) - int(new_count or 0)}")
    
    # uploads/customers
    old_up, _ = run(f'find {old_root}/backend/web/uploads/ -type f 2>/dev/null | wc -l')
    new_up, _ = run(f'find {new_root}/backend/web/uploads/ -type f 2>/dev/null | wc -l')
    print(f"  {label} uploads: old={old_up}, new={new_up}, diff={int(old_up or 0) - int(new_up or 0)}")
    
    # other image dirs
    old_emp, _ = run(f'ls {old_root}/backend/web/images/employeeImage/ 2>/dev/null | wc -l')
    new_emp, _ = run(f'ls {new_root}/backend/web/images/employeeImage/ 2>/dev/null | wc -l')
    print(f"  {label} employeeImage: old={old_emp}, new={new_emp}")
    
    old_law, _ = run(f'ls {old_root}/backend/web/images/lawar_images/ 2>/dev/null | wc -l')
    new_law, _ = run(f'ls {new_root}/backend/web/images/lawar_images/ 2>/dev/null | wc -l')
    print(f"  {label} lawar_images: old={old_law}, new={new_law}")

# ── Sync EVERYTHING ──
print("\n── Syncing ALL images ──")

syncs = [
    # JADAL: imagemanager
    ('JADAL imagemanager old→new',
     'rsync -a --ignore-existing /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ /var/www/jadal.aqssat.co/backend/web/images/imagemanager/'),
    ('JADAL imagemanager new→old',
     'rsync -a --ignore-existing /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/'),
    
    # JADAL: all images folder
    ('JADAL images/* old→new',
     'rsync -a --ignore-existing /var/www/old.jadal.aqssat.co/backend/web/images/ /var/www/jadal.aqssat.co/backend/web/images/'),
    ('JADAL images/* new→old',
     'rsync -a --ignore-existing /var/www/jadal.aqssat.co/backend/web/images/ /var/www/old.jadal.aqssat.co/backend/web/images/'),
    
    # JADAL: uploads
    ('JADAL uploads old→new',
     'rsync -a --ignore-existing /var/www/old.jadal.aqssat.co/backend/web/uploads/ /var/www/jadal.aqssat.co/backend/web/uploads/ 2>/dev/null; true'),
    ('JADAL uploads new→old',
     'rsync -a --ignore-existing /var/www/jadal.aqssat.co/backend/web/uploads/ /var/www/old.jadal.aqssat.co/backend/web/uploads/ 2>/dev/null; true'),
    
    # NAMAA: imagemanager
    ('NAMAA imagemanager old→new',
     'rsync -a --ignore-existing /var/www/old.namaa.aqssat.co/backend/web/images/imagemanager/ /var/www/namaa.aqssat.co/backend/web/images/imagemanager/'),
    ('NAMAA imagemanager new→old',
     'rsync -a --ignore-existing /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ /var/www/old.namaa.aqssat.co/backend/web/images/imagemanager/'),
    
    # NAMAA: all images folder
    ('NAMAA images/* old→new',
     'rsync -a --ignore-existing /var/www/old.namaa.aqssat.co/backend/web/images/ /var/www/namaa.aqssat.co/backend/web/images/'),
    ('NAMAA images/* new→old',
     'rsync -a --ignore-existing /var/www/namaa.aqssat.co/backend/web/images/ /var/www/old.namaa.aqssat.co/backend/web/images/'),
    
    # NAMAA: uploads
    ('NAMAA uploads old→new',
     'rsync -a --ignore-existing /var/www/old.namaa.aqssat.co/backend/web/uploads/ /var/www/namaa.aqssat.co/backend/web/uploads/ 2>/dev/null; true'),
    ('NAMAA uploads new→old',
     'rsync -a --ignore-existing /var/www/namaa.aqssat.co/backend/web/uploads/ /var/www/old.namaa.aqssat.co/backend/web/uploads/ 2>/dev/null; true'),
]

for label, cmd in syncs:
    t0 = time.time()
    out, err = run(cmd, timeout=300)
    elapsed = time.time() - t0
    print(f"  {label} ... done ({elapsed:.1f}s)")

# ── Fix permissions ──
print("\n── Fixing permissions ──")
perms = [
    'chown -R www-data:www-data /var/www/jadal.aqssat.co/backend/web/images/',
    'chown -R www-data:www-data /var/www/jadal.aqssat.co/backend/web/uploads/',
    'chown -R www-data:www-data /var/www/namaa.aqssat.co/backend/web/images/',
    'chown -R www-data:www-data /var/www/namaa.aqssat.co/backend/web/uploads/',
    'chown -R www-data:www-data /var/www/old.jadal.aqssat.co/backend/web/images/',
    'chown -R www-data:www-data /var/www/old.namaa.aqssat.co/backend/web/images/',
]
for cmd in perms:
    run(cmd)
print("  Done")

# ── After counts ──
print("\n── After Sync ──")
for label, old_root, new_root in pairs:
    old_count, _ = run(f'ls {old_root}/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
    new_count, _ = run(f'ls {new_root}/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
    print(f"  {label} imagemanager: old={old_count}, new={new_count}, match={'YES' if old_count == new_count else 'NO'}")
    
    old_up, _ = run(f'find {old_root}/backend/web/uploads/ -type f 2>/dev/null | wc -l')
    new_up, _ = run(f'find {new_root}/backend/web/uploads/ -type f 2>/dev/null | wc -l')
    print(f"  {label} uploads: old={old_up}, new={new_up}, match={'YES' if old_up == new_up else 'NO'}")

# ── Update cron to sync uploads too ──
print("\n── Updating cron sync script ──")
sync_script = """#!/bin/bash
# Full bidirectional sync of ALL image directories
# Runs every 5 minutes

# === JADAL ===
rsync -a --ignore-existing /var/www/old.jadal.aqssat.co/backend/web/images/ /var/www/jadal.aqssat.co/backend/web/images/ 2>/dev/null
rsync -a --ignore-existing /var/www/jadal.aqssat.co/backend/web/images/ /var/www/old.jadal.aqssat.co/backend/web/images/ 2>/dev/null
rsync -a --ignore-existing /var/www/old.jadal.aqssat.co/backend/web/uploads/ /var/www/jadal.aqssat.co/backend/web/uploads/ 2>/dev/null
rsync -a --ignore-existing /var/www/jadal.aqssat.co/backend/web/uploads/ /var/www/old.jadal.aqssat.co/backend/web/uploads/ 2>/dev/null

# === NAMAA ===
rsync -a --ignore-existing /var/www/old.namaa.aqssat.co/backend/web/images/ /var/www/namaa.aqssat.co/backend/web/images/ 2>/dev/null
rsync -a --ignore-existing /var/www/namaa.aqssat.co/backend/web/images/ /var/www/old.namaa.aqssat.co/backend/web/images/ 2>/dev/null
rsync -a --ignore-existing /var/www/old.namaa.aqssat.co/backend/web/uploads/ /var/www/namaa.aqssat.co/backend/web/uploads/ 2>/dev/null
rsync -a --ignore-existing /var/www/namaa.aqssat.co/backend/web/uploads/ /var/www/old.namaa.aqssat.co/backend/web/uploads/ 2>/dev/null

# Fix permissions
chown -R www-data:www-data /var/www/jadal.aqssat.co/backend/web/images/ /var/www/jadal.aqssat.co/backend/web/uploads/ 2>/dev/null
chown -R www-data:www-data /var/www/namaa.aqssat.co/backend/web/images/ /var/www/namaa.aqssat.co/backend/web/uploads/ 2>/dev/null
"""
import io
sftp = ssh.open_sftp()
sftp.putfo(io.BytesIO(sync_script.encode('utf-8')), '/usr/local/bin/sync-images.sh')
run('chmod +x /usr/local/bin/sync-images.sh')
sftp.close()
print("  Updated /usr/local/bin/sync-images.sh")

ssh.close()
print("\n" + "=" * 60)
print("  COMPLETE - All images synced bidirectionally")
print("=" * 60)
