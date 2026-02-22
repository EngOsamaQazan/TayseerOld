import paramiko
import os
import time

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

SITES = ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co']

def run_cmd(ssh, cmd, timeout=120):
    print(f"  $ {cmd}", flush=True)
    chan = ssh.get_transport().open_session()
    chan.settimeout(timeout)
    chan.exec_command(cmd)
    out_data = b''
    err_data = b''
    while True:
        if chan.recv_ready():
            out_data += chan.recv(65536)
            continue
        if chan.recv_stderr_ready():
            err_data += chan.recv_stderr(65536)
            continue
        if chan.exit_status_ready():
            while chan.recv_ready():
                out_data += chan.recv(65536)
            while chan.recv_stderr_ready():
                err_data += chan.recv_stderr(65536)
            break
        time.sleep(0.1)
    exit_code = chan.recv_exit_status()
    out = out_data.decode('utf-8', errors='replace')
    err = err_data.decode('utf-8', errors='replace')
    if out.strip():
        for line in out.strip().split('\n')[:30]:
            try:
                print(f"    {line}", flush=True)
            except UnicodeEncodeError:
                pass
    if err.strip():
        for line in err.strip().split('\n')[:10]:
            try:
                print(f"    [err] {line}", flush=True)
            except UnicodeEncodeError:
                pass
    print(f"  -> exit: {exit_code}", flush=True)
    chan.close()
    return exit_code, out, err

def main():
    print(f"Connecting to {HOST}...", flush=True)
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASS, timeout=30)
    ssh.get_transport().set_keepalive(15)
    print("Connected!\n", flush=True)

    for site in SITES:
        name = site.split('/')[-1]
        print(f"{'='*50}", flush=True)
        print(f"  Deploying: {name}", flush=True)
        print(f"{'='*50}", flush=True)

        run_cmd(ssh, f"cd {site} && git fetch origin main")
        run_cmd(ssh, f"cd {site} && git reset --hard origin/main")
        run_cmd(ssh, f"cd {site} && git log -1 --oneline")
        run_cmd(ssh, f"cd {site} && php yii cache/flush-all 2>&1")
        print(f"  {name} DONE!\n", flush=True)

    run_cmd(ssh, "systemctl restart apache2 2>&1")

    print("\nTesting sites...", flush=True)
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://jadal.aqssat.co")
    run_cmd(ssh, "curl -sL -o /dev/null -w '%{http_code}' https://namaa.aqssat.co")

    print("\nALL DONE!", flush=True)
    ssh.close()

if __name__ == '__main__':
    main()
