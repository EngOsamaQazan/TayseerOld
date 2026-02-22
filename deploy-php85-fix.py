import paramiko
import os

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
    print("  STEP 1: Fix expired GPG key for sury.org repo")
    print("="*60)
    run_cmd(ssh, "rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*.list 2>/dev/null; true")
    run_cmd(ssh, "curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/deb.sury.org-php.gpg 2>&1")
    run_cmd(ssh, "echo 'deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ bullseye main' > /etc/apt/sources.list.d/sury-php.list")
    run_cmd(ssh, "apt-get update -y 2>&1 | tail -10", timeout=120)

    print("\n" + "="*60)
    print("  STEP 2: Check PHP 8.5 availability")
    print("="*60)
    run_cmd(ssh, "apt-cache policy php8.5 | head -5")

    print("\n" + "="*60)
    print("  STEP 3: Install PHP 8.5 + extensions")
    print("="*60)
    pkgs = "php8.5 php8.5-cli php8.5-mysql php8.5-xml php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl php8.5-bcmath php8.5-soap php8.5-readline php8.5-common php8.5-bz2 libapache2-mod-php8.5"
    run_cmd(ssh, f"apt-get install -y {pkgs} 2>&1 | tail -30", timeout=600)

    print("\n" + "="*60)
    print("  STEP 4: Verify PHP 8.5 installed")
    print("="*60)
    run_cmd(ssh, "php8.5 -v 2>&1 | head -1")
    run_cmd(ssh, "php8.5 -m | sort")

    print("\n" + "="*60)
    print("  STEP 5: Switch Apache to PHP 8.5")
    print("="*60)
    run_cmd(ssh, "a2dismod php8.3 2>&1; true")
    run_cmd(ssh, "a2enmod php8.5 2>&1")
    run_cmd(ssh, "systemctl restart apache2 2>&1")
    run_cmd(ssh, "systemctl status apache2 2>&1 | head -5")

    print("\n" + "="*60)
    print("  STEP 6: Set PHP 8.5 as default CLI")
    print("="*60)
    run_cmd(ssh, "update-alternatives --set php /usr/bin/php8.5 2>&1")
    run_cmd(ssh, "php -v | head -1")

    print("\n" + "="*60)
    print("  STEP 7: Test sites")
    print("="*60)
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")
    run_cmd(ssh, "curl -sL https://jadal.aqssat.co 2>/dev/null | head -5")

    print("\n" + "="*60)
    print("  DONE!")
    print("="*60)

    ssh.close()

if __name__ == '__main__':
    main()
