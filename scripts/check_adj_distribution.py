import paramiko

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986')

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=60)
    return stdout.read().decode('utf-8', errors='replace').strip()

db = 'namaa_jadal'
u = 'osama'
p = "O$amaDaTaBase@123"

def sql(q):
    return run(f"mysql -u {u} -p'{p}' {db} -N -e \"{q}\"")

total_adj = sql('SELECT COUNT(DISTINCT contract_id) FROM os_contract_adjustments WHERE type="free_discount" AND is_deleted=0')
print(f'Contracts that got free_discount: {total_adj}')

status_breakdown = sql("""
    SELECT c.status, COUNT(*) as cnt 
    FROM os_contracts c 
    INNER JOIN os_contract_adjustments ca ON ca.contract_id = c.id AND ca.type='free_discount' AND ca.is_deleted=0
    GROUP BY c.status ORDER BY cnt DESC
""")
print(f'\nCurrent status of those contracts:')
print(status_breakdown)

jud_paid = sql("""
    SELECT COUNT(DISTINCT ca.contract_id) 
    FROM os_contract_adjustments ca 
    INNER JOIN os_contracts c ON c.id = ca.contract_id AND c.status='judiciary'
    WHERE ca.type='free_discount' AND ca.is_deleted=0
""")
print(f'\nOf those, now judiciary (were finished but have judiciary case): {jud_paid}')
print('  -> These show as "judiciary + paid badge" since balance=0')

legal_count = sql("""
    SELECT COUNT(DISTINCT ca.contract_id) 
    FROM os_contract_adjustments ca 
    INNER JOIN os_contracts c ON c.id = ca.contract_id AND c.status='legal_department'
    WHERE ca.type='free_discount' AND ca.is_deleted=0
""")
print(f'  Now legal_department: {legal_count}')

sett_count = sql("""
    SELECT COUNT(DISTINCT ca.contract_id) 
    FROM os_contract_adjustments ca 
    INNER JOIN os_contracts c ON c.id = ca.contract_id AND c.status='settlement'
    WHERE ca.type='free_discount' AND ca.is_deleted=0
""")
print(f'  Now settlement: {sett_count}')

fin_count = sql("""
    SELECT COUNT(DISTINCT ca.contract_id) 
    FROM os_contract_adjustments ca 
    INNER JOIN os_contracts c ON c.id = ca.contract_id AND c.status='finished'
    WHERE ca.type='free_discount' AND ca.is_deleted=0
""")
print(f'  Still finished: {fin_count}')

active_count = sql("""
    SELECT COUNT(DISTINCT ca.contract_id) 
    FROM os_contract_adjustments ca 
    INNER JOIN os_contracts c ON c.id = ca.contract_id AND c.status='active'
    WHERE ca.type='free_discount' AND ca.is_deleted=0
""")
print(f'  Now active: {active_count}')

print(f'\n  SUM check: {jud_paid} + {legal_count} + {sett_count} + {fin_count} + {active_count} = ?')

print(f'\n--- Overall jadal status ---')
overall = sql("SELECT status, COUNT(*) FROM os_contracts WHERE is_deleted=0 OR is_deleted IS NULL GROUP BY status ORDER BY COUNT(*) DESC")
print(overall)

ssh.close()
