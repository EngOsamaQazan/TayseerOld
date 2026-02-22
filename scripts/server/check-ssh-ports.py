import socket
import os

os.environ['PYTHONIOENCODING'] = 'utf-8'

HOST = '54.38.236.112'
COMMON_SSH_PORTS = [22, 2222, 2022, 8022, 222, 2200, 22222]

print(f"Scanning common SSH ports on {HOST}...", flush=True)
for port in COMMON_SSH_PORTS:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(3)
        result = s.connect_ex((HOST, port))
        if result == 0:
            print(f"  Port {port}: OPEN", flush=True)
        else:
            print(f"  Port {port}: closed", flush=True)
        s.close()
    except Exception as e:
        print(f"  Port {port}: error - {e}", flush=True)

print("\nChecking other common ports...", flush=True)
for port in [80, 443, 3306]:
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(3)
        result = s.connect_ex((HOST, port))
        if result == 0:
            print(f"  Port {port}: OPEN", flush=True)
        else:
            print(f"  Port {port}: closed", flush=True)
        s.close()
    except Exception as e:
        print(f"  Port {port}: error - {e}", flush=True)
