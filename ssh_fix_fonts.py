"""
Fix missing/empty OpenSans font files on both Jadal and Namaa servers.
Downloads from Google Fonts CDN and uploads via SFTP.
"""
import paramiko, sys, os, urllib.request, tempfile, time
sys.stdout.reconfigure(encoding='utf-8')

# OpenSans font files needed (from custom.css @font-face declarations)
FONTS = [
    'OpenSans-Bold.ttf',
    'OpenSans-BoldItalic.ttf',
    'OpenSans-ExtraBold.ttf',
    'OpenSans-Italic.ttf',
    'OpenSans-Light.ttf',
    'OpenSans-LightItalic.ttf',
    'OpenSans-Regular.ttf',
    'OpenSans-Semibold.ttf',
    'OpenSans-SemiboldItalic.ttf',
]

# Map font filenames to Google Fonts static download URLs
# Using Google Fonts GitHub raw URLs (opensans v40)
CDN = 'https://cdn.jsdelivr.net/npm/opensans-font@1.0.0'
FONT_MAP = {
    'OpenSans-Bold.ttf': f'{CDN}/OpenSans-Bold.ttf',
    'OpenSans-BoldItalic.ttf': f'{CDN}/OpenSans-BoldItalic.ttf',
    'OpenSans-ExtraBold.ttf': f'{CDN}/OpenSans-ExtraBold.ttf',
    'OpenSans-Italic.ttf': f'{CDN}/OpenSans-Italic.ttf',
    'OpenSans-Light.ttf': f'{CDN}/OpenSans-Light.ttf',
    'OpenSans-LightItalic.ttf': f'{CDN}/OpenSans-LightItalic.ttf',
    'OpenSans-Regular.ttf': f'{CDN}/OpenSans-Regular.ttf',
    'OpenSans-Semibold.ttf': f'{CDN}/OpenSans-SemiBold.ttf',
    'OpenSans-SemiboldItalic.ttf': f'{CDN}/OpenSans-SemiBoldItalic.ttf',
}

# Download fonts locally first
tmp_dir = tempfile.mkdtemp(prefix='opensans_')
print(f"Downloading fonts to {tmp_dir}...")

downloaded = {}
for fname, url in FONT_MAP.items():
    local_path = os.path.join(tmp_dir, fname)
    try:
        urllib.request.urlretrieve(url, local_path)
        size = os.path.getsize(local_path)
        if size < 1000:
            print(f"  WARNING: {fname} downloaded but only {size} bytes - may be redirect page")
        else:
            print(f"  ✓ {fname} ({size:,} bytes)")
            downloaded[fname] = local_path
    except Exception as e:
        print(f"  ✗ {fname}: {e}")

if not downloaded:
    print("No fonts downloaded! Exiting.")
    sys.exit(1)

print(f"\n{len(downloaded)}/{len(FONTS)} fonts downloaded successfully")

# Connect to server
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)
sftp = ssh.open_sftp()

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=15)
    return stdout.read().decode('utf-8', errors='replace').strip()

# Target directories for both companies, both old and new
TARGETS = [
    '/var/www/jadal.aqssat.co/backend/web/css/font',
    '/var/www/old.jadal.aqssat.co/backend/web/css/font',
    '/var/www/jadal.aqssat.co/backend/web/css/members/font',
    '/var/www/old.jadal.aqssat.co/backend/web/css/members/font',
    '/var/www/namaa.aqssat.co/backend/web/css/font',
    '/var/www/old.namaa.aqssat.co/backend/web/css/font',
    '/var/www/namaa.aqssat.co/backend/web/css/members/font',
    '/var/www/old.namaa.aqssat.co/backend/web/css/members/font',
]

for target_dir in TARGETS:
    print(f"\n--- {target_dir} ---")
    run(f'mkdir -p {target_dir}')
    
    for fname, local_path in downloaded.items():
        remote_path = f'{target_dir}/{fname}'
        try:
            sftp.put(local_path, remote_path)
            # Verify
            remote_size = int(run(f'stat -c %s {remote_path} 2>/dev/null') or '0')
            local_size = os.path.getsize(local_path)
            if remote_size == local_size:
                print(f"  ✓ {fname} ({remote_size:,} bytes)")
            else:
                print(f"  ⚠ {fname} size mismatch: local={local_size}, remote={remote_size}")
        except Exception as e:
            print(f"  ✗ {fname}: {e}")
    
    run(f'chown -R www-data:www-data {target_dir}')
    run(f'chmod 644 {target_dir}/*.ttf 2>/dev/null')

sftp.close()
ssh.close()

# Cleanup
for f in downloaded.values():
    os.remove(f)
os.rmdir(tmp_dir)

print("\n✓ All fonts uploaded successfully!")
