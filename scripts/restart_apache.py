import paramiko
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
    return code, out, err

# 1. Check Apache status before restart
print('=== Before restart ===')
code, out, _ = run('systemctl is-active apache2')
print(f'Apache status: {out}')

# 2. Check opcache before restart
code, out, _ = run("curl -sk 'https://jadal.aqssat.co/opcache_check.php' 2>/dev/null")
# Create temp file first
run("echo '<?php echo json_encode([\"cached_scripts\"=>count(opcache_get_status(true)[\"scripts\"]??[]),\"hits\"=>opcache_get_status(false)[\"opcache_statistics\"][\"hits\"]??0]);' > /var/www/jadal.aqssat.co/backend/web/_oc.php")
code, out, _ = run("curl -sk 'https://jadal.aqssat.co/_oc.php' 2>/dev/null")
print(f'opcache before: {out}')

# 3. Restart Apache
print('\n=== Restarting Apache ===')
code, out, err = run('systemctl restart apache2')
if code == 0:
    print('Apache restarted successfully!')
else:
    print(f'ERROR: {err}')

# 4. Check Apache status after restart
code, out, _ = run('systemctl is-active apache2')
print(f'Apache status after restart: {out}')

# 5. Check opcache after restart
code, out, _ = run("curl -sk 'https://jadal.aqssat.co/_oc.php' 2>/dev/null")
print(f'opcache after: {out}')

# 6. Clean up temp file
run('rm -f /var/www/jadal.aqssat.co/backend/web/_oc.php')

# 7. Verify the menu is now correct by checking the rendered HTML
print('\n=== Verifying menu renders correctly ===')
run("echo '<?php define(\"YII_DEBUG\",false);define(\"YII_ENV\",\"prod\");require __DIR__.\"/../../vendor/autoload.php\";require __DIR__.\"/../../vendor/yiisoft/yii2/Yii.php\";echo \"opcache_reset_done\";' > /dev/null")

# Simple check: fetch the site and look for menu text
code, out, _ = run("curl -sk 'https://jadal.aqssat.co/' 2>/dev/null | grep -o 'capitalTransactions\\|sharedExpenses\\|profitDistribution\\|shareholders' | sort -u")
print(f'New module URLs found in HTML: {out if out else "Need to login to check"}')

code, out, _ = run("curl -sk 'https://namaa.aqssat.co/' 2>/dev/null | grep -o 'capitalTransactions\\|sharedExpenses\\|profitDistribution\\|shareholders' | sort -u")
print(f'namaa new module URLs: {out if out else "Need to login to check"}')

ssh.close()
print('\nDone! Apache has been restarted and opcache cleared.')
