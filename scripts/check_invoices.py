import paramiko
import sys, io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=30)
print('Connected!')

def run(cmd):
    si, so, se = ssh.exec_command(cmd, timeout=30)
    return so.read().decode('utf-8', 'replace').strip()

for db in ['namaa_jadal', 'namaa_erp']:
    print(f'\n=== {db} ===')
    
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total_invoices FROM os_inventory_invoices;\" 2>/dev/null")
    print(f'Invoices: {out}')
    
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT status, COUNT(*) as cnt FROM os_inventory_invoices GROUP BY status;\" 2>/dev/null")
    print(f'By status: {out}')
    
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total_items FROM os_items_inventory_invoices WHERE is_deleted=0;\" 2>/dev/null")
    print(f'Invoice items: {out}')
    
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as cnt FROM os_stock_movements;\" 2>/dev/null")
    print(f'Stock movements: {out}')

ssh.close()
print('\nDone!')
