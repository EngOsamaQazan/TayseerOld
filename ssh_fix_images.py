"""
Fix: Images are being saved to old.jadal.aqssat.co instead of jadal.aqssat.co
Solution: 
1. Check which directory Apache actually serves from
2. Copy missing files
3. Create sync mechanism
"""
import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

# 1. Check Apache VirtualHost for jadal.aqssat.co
print("=== Apache VirtualHost config for jadal ===")
out, _ = run('grep -A10 "jadal.aqssat.co" /etc/apache2/sites-enabled/*.conf 2>/dev/null | head -30')
print(out)
print()

# 2. Check if there's a symlink
print("=== Check symlinks ===")
out, _ = run('ls -la /var/www/jadal.aqssat.co 2>/dev/null')
print(out)
out, _ = run('ls -la /var/www/old.jadal.aqssat.co 2>/dev/null')
print(out)
print()

# 3. Which directory does the actual Apache process serve?
# Create a temporary test file in both dirs
print("=== Testing which dir Apache serves from ===")
run('echo "NEW_JADAL" > /var/www/jadal.aqssat.co/backend/web/test_which.txt')
run('echo "OLD_JADAL" > /var/www/old.jadal.aqssat.co/backend/web/test_which.txt')

out, _ = run('curl -s "https://jadal.aqssat.co/test_which.txt" --insecure')
print(f"  jadal.aqssat.co serves: {out}")

# Clean up test files
run('rm -f /var/www/jadal.aqssat.co/backend/web/test_which.txt')
run('rm -f /var/www/old.jadal.aqssat.co/backend/web/test_which.txt')
print()

# 4. Count files in each imagemanager directory
print("=== File counts ===")
out, _ = run('ls /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ | wc -l')
print(f"  jadal.aqssat.co: {out} files")
out, _ = run('ls /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ | wc -l')
print(f"  old.jadal.aqssat.co: {out} files")
print()

# 5. Find files in old.jadal that are NOT in jadal (the missing ones)
print("=== Finding files in old.jadal missing from jadal ===")
out, _ = run(
    'diff <(ls /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ | sort) '
    '<(ls /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ | sort) '
    '| grep "^< " | wc -l'
)
print(f"  Files in old but not in new: {out}")
print()

# 6. Copy missing files from old.jadal to jadal
print("=== Copying missing files from old.jadal to jadal ===")
out, err = run(
    'rsync -av --ignore-existing '
    '/var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ '
    '/var/www/jadal.aqssat.co/backend/web/images/imagemanager/'
)
# Count transferred
lines = out.split('\n') if out else []
transferred = [l for l in lines if not l.startswith('sending') and not l.startswith('total') and not l.startswith('sent ') and not l.startswith('building') and l.strip() and '/' not in l[:3]]
print(f"  Transferred: {len(transferred)} files")
if len(transferred) <= 20:
    for t in transferred:
        print(f"    {t}")
else:
    for t in transferred[:5]:
        print(f"    {t}")
    print(f"    ... and {len(transferred) - 5} more")
print()

# 7. Set correct permissions
print("=== Setting permissions ===")
run('chown -R www-data:www-data /var/www/jadal.aqssat.co/backend/web/images/imagemanager/')
print("  Done")
print()

# 8. Verify after fix
print("=== Verification after fix ===")
for fid in ['12378', '12390', '12394']:
    out, _ = run(f'ls /var/www/jadal.aqssat.co/backend/web/images/imagemanager/{fid}_* 2>/dev/null')
    status = "FOUND" if out else "STILL MISSING"
    print(f"  ID {fid}: {status}")

out, _ = run('ls /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ | wc -l')
print(f"\n  Total files in jadal.aqssat.co now: {out}")
print()

# 9. Same check for Namaa
print("=== Checking Namaa ===")
out_old, _ = run('ls /var/www/old.namaa.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
out_new, _ = run('ls /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
print(f"  old.namaa: {out_old} files")
print(f"  namaa: {out_new} files")

if out_old and out_new and int(out_old) > int(out_new):
    print("  Syncing old.namaa → namaa ...")
    out, _ = run(
        'rsync -av --ignore-existing '
        '/var/www/old.namaa.aqssat.co/backend/web/images/imagemanager/ '
        '/var/www/namaa.aqssat.co/backend/web/images/imagemanager/'
    )
    run('chown -R www-data:www-data /var/www/namaa.aqssat.co/backend/web/images/imagemanager/')
    print(f"  Namaa sync done")
    out, _ = run('ls /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ | wc -l')
    print(f"  Total files in namaa now: {out}")
print()

ssh.close()
print("COMPLETE!")
