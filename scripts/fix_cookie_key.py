import paramiko
import secrets
import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=30)
print('Connected!')

def run(cmd):
    si, so, se = ssh.exec_command(cmd, timeout=30)
    out = so.read().decode('utf-8', 'replace').strip()
    err = se.read().decode('utf-8', 'replace').strip()
    code = so.channel.recv_exit_status()
    if code != 0 and err:
        print(f'  ERROR: {err}')
    return code, out, err

jadal_key = secrets.token_hex(32)
namaa_key = secrets.token_hex(32)

print(f'Generated jadal key: {jadal_key[:16]}...')
print(f'Generated namaa key: {namaa_key[:16]}...')

for site, key in [('jadal.aqssat.co', jadal_key), ('namaa.aqssat.co', namaa_key)]:
    path = f'/var/www/{site}'
    print(f'\n=== Fixing {site} ===')
    
    config_content = f"""<?php
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => '{key}',
        ],
    ],
];
"""
    
    # Write the backend main-local.php
    escaped = config_content.replace("'", "'\\''")
    run(f"cat > {path}/backend/config/main-local.php << 'PHPEOF'\n{config_content}PHPEOF")
    
    # Also update the frontend main-local.php with a different key
    frontend_key = secrets.token_hex(32)
    frontend_config = f"""<?php
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => '{frontend_key}',
        ],
    ],
];
"""
    run(f"cat > {path}/frontend/config/main-local.php << 'PHPEOF'\n{frontend_config}PHPEOF")
    
    # Verify
    code, out, _ = run(f'grep cookieValidationKey {path}/backend/config/main-local.php')
    print(f'  backend: {out}')
    code, out, _ = run(f'grep cookieValidationKey {path}/frontend/config/main-local.php')
    print(f'  frontend: {out}')
    
    # Set permissions
    run(f'chown www-data:www-data {path}/backend/config/main-local.php {path}/frontend/config/main-local.php')

# Also update the environments templates in the repo so future deploys don't break this
for site, env_dir, key in [
    ('jadal.aqssat.co', 'environments/prod_jadal', jadal_key),
    ('namaa.aqssat.co', 'environments/prod_namaa', namaa_key)
]:
    path = f'/var/www/{site}'
    config_content = f"""<?php
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => '{key}',
        ],
    ],
];
"""
    run(f"cat > {path}/{env_dir}/backend/config/main-local.php << 'PHPEOF'\n{config_content}PHPEOF")
    print(f'  Updated {env_dir}/backend/config/main-local.php on {site}')

# Restart Apache to load fresh config
print('\n=== Restarting Apache ===')
code, _, _ = run('systemctl restart apache2')
print(f'Apache restart: {"OK" if code == 0 else "FAILED"}')

# Test the site
print('\n=== Testing sites ===')
code, out, _ = run("curl -sk -o /dev/null -w '%{http_code}' https://jadal.aqssat.co/")
print(f'jadal HTTP status: {out}')
code, out, _ = run("curl -sk -o /dev/null -w '%{http_code}' https://namaa.aqssat.co/")
print(f'namaa HTTP status: {out}')

ssh.close()
print('\nDone!')
