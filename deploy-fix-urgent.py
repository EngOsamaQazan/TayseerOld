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
    print("  URGENT: Re-enable PHP 8.3 for Apache")
    print("="*60)
    run_cmd(ssh, "a2enmod php8.3 2>&1")
    run_cmd(ssh, "systemctl restart apache2 2>&1")

    print("\n  Testing sites...")
    run_cmd(ssh, "curl -sL https://jadal.aqssat.co 2>/dev/null | head -3")

    print("\n" + "="*60)
    print("  Fix GPG key and update repo")
    print("="*60)
    run_cmd(ssh, "rm -f /etc/apt/sources.list.d/sury-php.list 2>/dev/null; true")
    run_cmd(ssh, "rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-*.list 2>/dev/null; true")

    run_cmd(ssh, "wget -qO /tmp/sury-key.gpg https://packages.sury.org/php/apt.gpg 2>&1")
    run_cmd(ssh, "cp /tmp/sury-key.gpg /etc/apt/trusted.gpg.d/sury-php.gpg")
    run_cmd(ssh, "ls -la /etc/apt/trusted.gpg.d/sury-php.gpg")

    run_cmd(ssh, "cat /etc/apt/sources.list.d/php.list 2>/dev/null")
    run_cmd(ssh, "echo 'deb https://packages.sury.org/php/ bullseye main' > /etc/apt/sources.list.d/php.list")
    run_cmd(ssh, "apt-get update 2>&1 | tail -10", timeout=120)

    print("\n" + "="*60)
    print("  Check latest PHP 8.5 version")
    print("="*60)
    run_cmd(ssh, "apt-cache policy php8.5 | head -8")
    run_cmd(ssh, "apt-cache showpkg php8.5-cli 2>/dev/null | head -5")

    print("\n" + "="*60)
    print("  Install PHP 8.5")
    print("="*60)
    pkgs = "php8.5 php8.5-cli php8.5-mysql php8.5-xml php8.5-mbstring php8.5-curl php8.5-zip php8.5-gd php8.5-intl php8.5-bcmath php8.5-soap php8.5-readline php8.5-common php8.5-bz2 libapache2-mod-php8.5"
    run_cmd(ssh, f"apt-get install -y {pkgs} 2>&1 | tail -30", timeout=600)

    print("\n" + "="*60)
    print("  Verify installation")
    print("="*60)
    run_cmd(ssh, "php8.5 -v 2>&1 | head -1")
    run_cmd(ssh, "dpkg -l | grep php8.5 | head -20")

    code, out, _ = run_cmd(ssh, "which php8.5 2>/dev/null && echo 'FOUND' || echo 'NOT_FOUND'")

    if 'FOUND' in out:
        print("\n" + "="*60)
        print("  Switch Apache to PHP 8.5")
        print("="*60)
        run_cmd(ssh, "a2dismod php8.3 2>&1")
        run_cmd(ssh, "a2enmod php8.5 2>&1")
        run_cmd(ssh, "systemctl restart apache2 2>&1")
        run_cmd(ssh, "update-alternatives --set php /usr/bin/php8.5 2>&1")
        run_cmd(ssh, "php -v | head -1")

        print("\n  Testing sites after switch...")
        run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
        run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")
    else:
        print("\n  PHP 8.5 NOT installed. Sites remain on PHP 8.3.")
        run_cmd(ssh, "php -v | head -1")
        print("\n  Sites should still work on PHP 8.3 (re-enabled above)")
        run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
        run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")

    print("\n" + "="*60)
    print("  DONE")
    print("="*60)

    ssh.close()

if __name__ == '__main__':
    main()
