"""Quick verify that key features are present on live servers"""
import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

def run(cmd):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=15)
    return stdout.read().decode('utf-8', errors='replace').strip()

for server in ['jadal', 'namaa']:
    path = f'/var/www/{server}.aqssat.co/backend/controllers/SiteController.php'
    print(f"\n=== {server} ===")
    
    # Check new methods
    for m in ['actionImageSearchCustomers', 'actionImageUpdateDocType', 'actionImageReassign', 'actionImageManagerData']:
        c = run(f'grep -c "function {m}" {path}')
        print(f"  {m}: {'OK' if c == '1' else f'ISSUE ({c})'}")
    
    # Check behaviors
    c = run(f'grep -c "image-search-customers" {path}')
    print(f"  behaviors includes search-customers: {'OK' if c == '1' else 'MISSING'}")
    
    # Check orphan filter (IN clause)
    c = run(f"grep -c \"personal_photo\" {path}")
    print(f"  orphan filter includes doc types: {'OK' if int(c or 0) >= 1 else 'MISSING'}")
    
    # Check view has doc-type-select
    vpath = f'/var/www/{server}.aqssat.co/backend/views/site/image-manager.php'
    c = run(f'grep -c "doc-type-select" {vpath}')
    print(f"  view doc-type-select: {'OK' if int(c or 0) >= 1 else 'MISSING'}")
    
    c = run(f'grep -c "liveSearchCustomer" {vpath}')
    print(f"  view liveSearchCustomer: {'OK' if int(c or 0) >= 1 else 'MISSING'}")
    
    c = run(f'grep -c "docTypeSelect" {vpath}')
    print(f"  view docTypeSelect: {'OK' if int(c or 0) >= 1 else 'MISSING'}")

    # Check CSS
    css_path = f'/var/www/{server}.aqssat.co/backend/web/css/image-manager-admin.css'
    c = run(f'grep -c "customer-search-results" {css_path}')
    print(f"  CSS customer-search-results: {'OK' if int(c or 0) >= 1 else 'MISSING'}")

ssh.close()
print("\n✓ DONE")
