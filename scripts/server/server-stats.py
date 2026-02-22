#!/usr/bin/env python3
"""Fetch production server stats (disk, RAM, CPU, uptime)."""
import paramiko

HOST = '54.38.236.112'
USER = 'root'
# Password from deploy - consider env var in production
PASSWORD = 'Hussain@1986'

def run(ssh, cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    err = stderr.read().decode('utf-8', errors='replace').strip()
    return out, err

def main():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect(HOST, username=USER, password=PASSWORD, timeout=30, banner_timeout=30)
    try:
        print("=== DISK (df -h) ===")
        out, _ = run(ssh, "df -h")
        print(out)
        print("\n=== MEMORY (free -h) ===")
        out, _ = run(ssh, "free -h")
        print(out)
        print("\n=== UPTIME ===")
        out, _ = run(ssh, "uptime")
        print(out)
        print("\n=== CPU (cores) ===")
        out, _ = run(ssh, "nproc")
        print("Cores:", out.strip())
        out, _ = run(ssh, "grep -m1 'model name' /proc/cpuinfo 2>/dev/null || cat /proc/cpuinfo | head -5")
        print(out)
        print("\n=== OS ===")
        out, _ = run(ssh, "cat /etc/os-release 2>/dev/null | head -5")
        print(out)
        print("\n=== DISK (MB, all mounts) ===")
        out, _ = run(ssh, "df -BM --output=source,fstype,size,used,avail,pcent,target | column -t")
        print(out)
    finally:
        ssh.close()

if __name__ == '__main__':
    main()
