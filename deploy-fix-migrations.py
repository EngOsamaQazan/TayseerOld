import paramiko
import sys
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

SITES = {
    'jadal': {
        'path': '/var/www/jadal.aqssat.co',
        'db': 'jadal_erp',
    },
    'namaa': {
        'path': '/var/www/namaa.aqssat.co',
        'db': 'namaa_erp',
    },
}

def run_cmd(ssh, cmd, timeout=300):
    print(f"  $ {cmd}")
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace')
    err = stderr.read().decode('utf-8', errors='replace')
    exit_code = stdout.channel.recv_exit_status()
    if out.strip():
        for line in out.strip().split('\n')[:50]:
            try:
                print(f"    {line}")
            except UnicodeEncodeError:
                print(f"    {line.encode('ascii', 'replace').decode()}")
    if err.strip():
        for line in err.strip().split('\n')[:20]:
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

    for name, site in SITES.items():
        path = site['path']
        db = site['db']
        print(f"\n{'='*60}")
        print(f"  Fixing migrations for: {name} ({db})")
        print(f"{'='*60}")

        print("\n  -- Checking current migration status --")
        run_cmd(ssh, f"cd {path} && php yii migrate/history 10 2>&1")

        print("\n  -- Marking m260213_120000_expand_jobs_system as applied --")
        run_cmd(ssh, f"cd {path} && php yii migrate/mark m260213_120000_expand_jobs_system --interactive=0 2>&1")

        print("\n  -- Running remaining migrations --")
        run_cmd(ssh, f"cd {path} && php yii migrate --interactive=0 2>&1")

        print(f"\n  {name} migrations DONE!")

    print(f"\n{'='*60}")
    print("  ALL MIGRATIONS FIXED!")
    print(f"{'='*60}")

    ssh.close()

if __name__ == '__main__':
    main()
