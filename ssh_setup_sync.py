"""
1. Verify the absolute path fix works
2. Set up a cron job to sync images between old and new dirs
"""
import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

# 1. Verify the absolute path fix is deployed
print("=== Verify absolute path fix ===")
out, _ = run('grep -c "absoluteMediaPath\|getAlias.*backend.*imagemanager" /var/www/jadal.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php')
print(f"  Jadal: {out} references to absolute path")
out, _ = run('grep -c "absoluteMediaPath\|getAlias.*backend.*imagemanager" /var/www/namaa.aqssat.co/backend/modules/imagemanager/controllers/ImageManagerController.php')
print(f"  Namaa: {out} references to absolute path")
print()

# 2. Create a sync script for cron
sync_script = """#!/bin/bash
# Sync images between old and new directories (both directions)
# Runs every 5 minutes

# Jadal: old → new  
rsync -a --ignore-existing /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null

# Jadal: new → old (in case uploads now go to new dir after fix)
rsync -a --ignore-existing /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ /var/www/old.jadal.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null

# Namaa: old → new
rsync -a --ignore-existing /var/www/old.namaa.aqssat.co/backend/web/images/imagemanager/ /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null

# Namaa: new → old
rsync -a --ignore-existing /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ /var/www/old.namaa.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null

# Fix permissions
chown -R www-data:www-data /var/www/jadal.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null
chown -R www-data:www-data /var/www/namaa.aqssat.co/backend/web/images/imagemanager/ 2>/dev/null
"""

# Write sync script
import io
sftp = ssh.open_sftp()
f = io.BytesIO(sync_script.encode('utf-8'))
sftp.putfo(f, '/usr/local/bin/sync-images.sh')
run('chmod +x /usr/local/bin/sync-images.sh')
print("  Created /usr/local/bin/sync-images.sh")

# 3. Add to crontab (every 5 minutes)
out, _ = run('crontab -l 2>/dev/null')
if 'sync-images.sh' not in out:
    new_cron = (out + '\n' if out else '') + '*/5 * * * * /usr/local/bin/sync-images.sh\n'
    f = io.BytesIO(new_cron.encode('utf-8'))
    sftp.putfo(f, '/tmp/new_crontab')
    run('crontab /tmp/new_crontab')
    run('rm /tmp/new_crontab')
    print("  Added cron job: sync every 5 minutes")
else:
    print("  Cron job already exists")
print()

# 4. Run the sync once now
print("=== Running initial sync ===")
run('/usr/local/bin/sync-images.sh')
print("  Sync complete")
print()

# 5. Final file counts
print("=== Final state ===")
for name, path in [('jadal', '/var/www/jadal.aqssat.co'), ('old.jadal', '/var/www/old.jadal.aqssat.co'), ('namaa', '/var/www/namaa.aqssat.co'), ('old.namaa', '/var/www/old.namaa.aqssat.co')]:
    out, _ = run(f'ls {path}/backend/web/images/imagemanager/ 2>/dev/null | wc -l')
    print(f"  {name}: {out} files")

sftp.close()
ssh.close()
print("\nDONE!")
