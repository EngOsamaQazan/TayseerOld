import paramiko
import os
import time
import urllib.request
import ssl

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
USER = 'root'
PASS = 'Hussain@1986'

SITES = [
    'https://jadal.aqssat.co',
    'https://namaa.aqssat.co',
]

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

print("Checking if sites are reachable via HTTPS...", flush=True)
for url in SITES:
    try:
        req = urllib.request.urlopen(url, timeout=10, context=ctx)
        print(f"  {url} -> HTTP {req.status}", flush=True)
    except Exception as e:
        print(f"  {url} -> ERROR: {e}", flush=True)

print(f"\nTrying SSH connection to {HOST}...", flush=True)
for attempt in range(10):
    try:
        print(f"  Attempt {attempt + 1}/10...", flush=True)
        ssh = paramiko.SSHClient()
        ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        ssh.connect(HOST, username=USER, password=PASS, timeout=15)
        print("  Connected!", flush=True)
        
        stdin, stdout, stderr = ssh.exec_command("uptime")
        print(f"  uptime: {stdout.read().decode().strip()}", flush=True)
        
        stdin, stdout, stderr = ssh.exec_command("cat /etc/os-release | grep PRETTY")
        print(f"  os: {stdout.read().decode().strip()}", flush=True)
        
        ssh.close()
        break
    except Exception as e:
        print(f"  Failed: {e}", flush=True)
        if attempt < 9:
            print(f"  Waiting 15 seconds...", flush=True)
            time.sleep(15)
        else:
            print("  Could not connect after 10 attempts.", flush=True)
