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
    print(f'\n{"="*60}')
    print(f'  {db}')
    print(f'{"="*60}')
    
    # Serial numbers count
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total FROM os_inventory_serial_numbers WHERE is_deleted=0;\" 2>/dev/null")
    print(f'Serial numbers (active): {out}')
    
    # By status
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT status, COUNT(*) as cnt FROM os_inventory_serial_numbers WHERE is_deleted=0 GROUP BY status;\" 2>/dev/null")
    print(f'By status:\n{out}')
    
    # Inventory items count
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total FROM os_inventory_items WHERE is_deleted=0;\" 2>/dev/null")
    print(f'\nInventory items: {out}')
    
    # Inventory invoices
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total FROM os_inventory_invoices;\" 2>/dev/null")
    print(f'Inventory invoices: {out}')
    
    # Contracts (sales) count
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total FROM os_contracts WHERE is_deleted=0;\" 2>/dev/null")
    print(f'Contracts (sales): {out}')
    
    # Stock movements
    out = run(f"mysql -u osama -p'O$amaDaTaBase@123' {db} -e \"SELECT COUNT(*) as total FROM os_stock_movements;\" 2>/dev/null")
    print(f'Stock movements: {out}')

ssh.close()
print('\nDone!')
