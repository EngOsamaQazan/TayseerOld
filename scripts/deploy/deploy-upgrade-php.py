import paramiko
import sys
import os
import time

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

def run_cmd(ssh, cmd, timeout=300):
    print(f"\n  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        for line in out.strip().split('\n')[:80]:
            try:
                print(f"    {line}")
            except UnicodeEncodeError:
                print(f"    {line.encode('ascii', 'replace').decode()}")
    if err.strip():
        for line in err.strip().split('\n')[:30]:
            try:
                print(f"    [err] {line}")
            except UnicodeEncodeError:
                print(f"    [err] {line.encode('ascii', 'replace').decode()}")
    print(f"  -> exit: {exit_code}")
    return exit_code, out, err

def main():
    print(f"Connecting to {HOST}...")
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    print("Connected!\n")

    print("="*60)
    print("  STEP 1: Check current PHP version and OS")
    print("="*60)
    run_cmd(ssh, "php -v | head -1")
    run_cmd(ssh, "cat /etc/os-release | head -4")
    run_cmd(ssh, "dpkg -l | grep php8.3 | head -5")

    print("\n" + "="*60)
    print("  STEP 2: Check available PHP versions")
    print("="*60)
    run_cmd(ssh, "apt-cache policy php8.5 2>/dev/null || echo 'php8.5 not in repos'")
    run_cmd(ssh, "add-apt-repository --list 2>/dev/null | grep ondrej || echo 'ondrej PPA not added'")

    print("\n" + "="*60)
    print("  STEP 3: Add Ondrej PPA and install PHP 8.5")
    print("="*60)
    run_cmd(ssh, "LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php 2>&1 || echo 'Trying alternative...'", timeout=120)
    run_cmd(ssh, "apt-get update -y 2>&1 | tail -5", timeout=120)

    run_cmd(ssh, "apt-cache policy php8.5 2>/dev/null | head -5")

    print("\n" + "="*60)
    print("  STEP 4: Install PHP 8.5 with required extensions")
    print("="*60)

    run_cmd(ssh, "php8.3 -m | sort", timeout=30)

    extensions = "php8.5 php8.5-cli php8.5-fpm php8.5-mysql php8.5-xml php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl php8.5-bcmath php8.5-soap php8.5-opcache php8.5-readline php8.5-common"
    run_cmd(ssh, f"apt-get install -y {extensions} 2>&1 | tail -20", timeout=300)

    run_cmd(ssh, "php8.5 -v | head -1")

    print("\n" + "="*60)
    print("  STEP 5: Switch Apache/Nginx to PHP 8.5")
    print("="*60)

    code, out, _ = run_cmd(ssh, "which nginx && echo 'NGINX' || echo 'NO_NGINX'")
    code2, out2, _ = run_cmd(ssh, "which apache2 && echo 'APACHE' || echo 'NO_APACHE'")

    if 'NGINX' in out:
        print("\n  Detected: Nginx + PHP-FPM")
        run_cmd(ssh, "systemctl stop php8.3-fpm 2>/dev/null; true")
        run_cmd(ssh, "systemctl start php8.5-fpm")
        run_cmd(ssh, "systemctl enable php8.5-fpm")

        run_cmd(ssh, r"grep -rl 'php8.3-fpm' /etc/nginx/ 2>/dev/null | head -10")
        run_cmd(ssh, r"sed -i 's/php8.3-fpm/php8.5-fpm/g' /etc/nginx/sites-available/* 2>/dev/null")
        run_cmd(ssh, r"sed -i 's/php8.3-fpm/php8.5-fpm/g' /etc/nginx/sites-enabled/* 2>/dev/null")
        run_cmd(ssh, r"sed -i 's/php8.3-fpm/php8.5-fpm/g' /etc/nginx/conf.d/* 2>/dev/null")
        run_cmd(ssh, "nginx -t 2>&1")
        run_cmd(ssh, "systemctl reload nginx")

    if 'APACHE' in out2:
        print("\n  Detected: Apache")
        run_cmd(ssh, "a2dismod php8.3 2>/dev/null; true")
        run_cmd(ssh, "a2enmod php8.5 2>/dev/null; true")
        run_cmd(ssh, "systemctl restart apache2")

    print("\n" + "="*60)
    print("  STEP 6: Update CLI default")
    print("="*60)
    run_cmd(ssh, "update-alternatives --set php /usr/bin/php8.5 2>/dev/null || true")
    run_cmd(ssh, "php -v | head -1")

    print("\n" + "="*60)
    print("  STEP 7: Verify sites are working")
    print("="*60)
    run_cmd(ssh, "curl -s -o /dev/null -w '%{http_code}' http://jadal.aqssat.co")
    run_cmd(ssh, "curl -s -o /dev/null -w '%{http_code}' http://namaa.aqssat.co")

    print("\n" + "="*60)
    print("  PHP UPGRADE COMPLETE!")
    print("="*60)

    ssh.close()

if __name__ == '__main__':
    main()
