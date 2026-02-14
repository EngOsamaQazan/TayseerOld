"""
Phase 0: Clean up wrongly migrated data
1. TRUNCATE os_customer_documents on both servers
2. Delete wrongly copied files from uploads/customers/documents/
3. Verify
"""
import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

db_user = 'osama'
db_pass = 'O$amaDaTaBase@123'

def run(cmd, timeout=120):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

def mysql(db, sql):
    return run(f"mysql -u {db_user} -p'{db_pass}' {db} -e \"{sql}\" 2>/dev/null")

print("=" * 60)
print("  PHASE 0: CLEANUP")
print("=" * 60)

# ── Step 1: Check current state ──
print("\n── Before Cleanup ──")

for label, db in [('JADAL', 'namaa_jadal'), ('NAMAA', 'namaa_erp')]:
    out, _ = mysql(db, "SELECT COUNT(*) as total FROM os_customer_documents")
    print(f"  {label} os_customer_documents: {out}")

for label, path in [('JADAL', '/var/www/jadal.aqssat.co'), ('NAMAA', '/var/www/namaa.aqssat.co')]:
    out, _ = run(f'find {path}/backend/web/uploads/customers/documents/ -type f 2>/dev/null | wc -l')
    print(f"  {label} uploads/customers/documents/ files: {out}")
    out, _ = run(f'du -sh {path}/backend/web/uploads/customers/documents/ 2>/dev/null')
    print(f"  {label} uploads/customers/documents/ size: {out}")

# ── Step 2: TRUNCATE os_customer_documents ──
print("\n── Step 1: TRUNCATE os_customer_documents ──")

for label, db in [('JADAL', 'namaa_jadal'), ('NAMAA', 'namaa_erp')]:
    out, err = mysql(db, "TRUNCATE TABLE os_customer_documents")
    out2, _ = mysql(db, "SELECT COUNT(*) as total FROM os_customer_documents")
    print(f"  {label}: TRUNCATED → {out2}")

# ── Step 3: Delete wrongly copied files ──
print("\n── Step 2: Delete wrong files from uploads/customers/documents/ ──")

# These files were copies from ImageManager with {id}_{hash} naming
# We need to be careful: only delete files with ImageManager naming pattern
# NOT any real Smart Media uploads (which would have UUID names)
# Since ALL current files are wrong copies, we can safely delete all

for label, path in [('JADAL', '/var/www/jadal.aqssat.co'), ('NAMAA', '/var/www/namaa.aqssat.co')]:
    # Count before
    out_before, _ = run(f'find {path}/backend/web/uploads/customers/documents/ -type f 2>/dev/null | wc -l')
    
    # Delete all files (keep the directory structure)
    run(f'find {path}/backend/web/uploads/customers/documents/ -type f -delete 2>/dev/null')
    
    # Also clean thumbs
    run(f'find {path}/backend/web/uploads/customers/documents/thumbs/ -type f -delete 2>/dev/null')
    run(f'find {path}/backend/web/uploads/customers/photos/ -type f -delete 2>/dev/null')
    run(f'find {path}/backend/web/uploads/customers/thumbs/ -type f -delete 2>/dev/null')
    
    # Count after
    out_after, _ = run(f'find {path}/backend/web/uploads/customers/ -type f 2>/dev/null | wc -l')
    
    print(f"  {label}: deleted {out_before} files → remaining: {out_after}")

# Also clean old servers
for label, path in [('old.JADAL', '/var/www/old.jadal.aqssat.co'), ('old.NAMAA', '/var/www/old.namaa.aqssat.co')]:
    out_before, _ = run(f'find {path}/backend/web/uploads/customers/documents/ -type f 2>/dev/null | wc -l')
    if int(out_before or 0) > 0:
        run(f'find {path}/backend/web/uploads/customers/documents/ -type f -delete 2>/dev/null')
        run(f'find {path}/backend/web/uploads/customers/photos/ -type f -delete 2>/dev/null')
        run(f'find {path}/backend/web/uploads/customers/thumbs/ -type f -delete 2>/dev/null')
        print(f"  {label}: deleted {out_before} files")
    else:
        print(f"  {label}: already clean")

# ── Step 4: Ensure directory structure exists for future uploads ──
print("\n── Step 3: Ensure upload directories exist ──")

dirs = [
    'uploads/customers/documents',
    'uploads/customers/documents/thumbs',
    'uploads/customers/photos',
    'uploads/customers/photos/thumbs',
    'uploads/customers/thumbs',
]

for path_root in ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co', '/var/www/old.jadal.aqssat.co', '/var/www/old.namaa.aqssat.co']:
    for d in dirs:
        run(f'mkdir -p {path_root}/backend/web/{d}')
        run(f'chown www-data:www-data {path_root}/backend/web/{d}')
        run(f'chmod 775 {path_root}/backend/web/{d}')

print("  All directories created and permissions set")

# ── Step 5: Clear Yii2 cache ──
print("\n── Step 4: Clear caches ──")
for path in ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co']:
    run(f'rm -rf {path}/backend/runtime/cache/*')
print("  Done")

# ── Step 6: Verify ──
print("\n── Verification ──")

for label, db in [('JADAL', 'namaa_jadal'), ('NAMAA', 'namaa_erp')]:
    out, _ = mysql(db, "SELECT COUNT(*) as total FROM os_customer_documents")
    print(f"  {label} os_customer_documents: {out}")
    out, _ = mysql(db, "SELECT COUNT(*) as total FROM os_ImageManager")
    print(f"  {label} os_ImageManager: {out}")

for label, path in [('JADAL', '/var/www/jadal.aqssat.co'), ('NAMAA', '/var/www/namaa.aqssat.co')]:
    out, _ = run(f'ls {path}/backend/web/images/imagemanager/ | wc -l')
    print(f"  {label} imagemanager files: {out}")
    out, _ = run(f'find {path}/backend/web/uploads/customers/ -type f 2>/dev/null | wc -l')
    print(f"  {label} uploads files: {out}")

ssh.close()
print("\n" + "=" * 60)
print("  PHASE 0 COMPLETE")
print("=" * 60)
