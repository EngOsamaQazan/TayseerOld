"""
Deploy the 2 new actions that the deploy script missed:
- actionImageSearchCustomers
- actionImageUpdateDocType
Also update behaviors() to include new actions
"""
import paramiko, sys, re, io
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

def run(cmd, timeout=30):
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=timeout)
    return stdout.read().decode('utf-8', errors='replace').strip()

# Read local SiteController to extract new methods
with open(r'C:\Users\PC\Desktop\Tayseer\backend\controllers\SiteController.php', 'r', encoding='utf-8') as f:
    local_content = f.read()

# Extract the two new methods
methods_to_add = ['actionImageSearchCustomers', 'actionImageUpdateDocType']

sftp = ssh.open_sftp()

for server in ['/var/www/jadal.aqssat.co', '/var/www/namaa.aqssat.co']:
    server_name = 'jadal' if 'jadal' in server else 'namaa'
    print(f"\n=== {server_name} ===")
    
    remote_path = f'{server}/backend/controllers/SiteController.php'
    
    # Read current server content
    prod_content = run(f'cat {remote_path}')
    
    for method_name in methods_to_add:
        if f'function {method_name}(' in prod_content:
            print(f"  {method_name}: already exists")
            continue
        
        # Extract from local
        pattern = rf'(    (?:public |private |protected )?function {method_name}\(.*?\n    \}})'
        match = re.search(pattern, local_content, re.DOTALL)
        if not match:
            print(f"  {method_name}: NOT FOUND in local!")
            continue
        
        method_code = match.group(1)
        
        # Insert before closing brace
        insert_point = prod_content.rfind('}')
        prod_content = prod_content[:insert_point] + '\n' + method_code + '\n\n' + prod_content[insert_point:]
        print(f"  + Added: {method_name}")
    
    # Update behaviors to include new actions
    if "'image-search-customers'" not in prod_content:
        prod_content = prod_content.replace(
            "'image-manager-stats'",
            "'image-manager-stats', 'image-search-customers', 'image-update-doc-type'"
        )
        print(f"  + Updated behaviors()")
    
    # Upload
    sftp.putfo(io.BytesIO(prod_content.encode('utf-8')), remote_path)
    run(f'chown www-data:www-data {remote_path}')
    run(f'rm -rf {server}/backend/runtime/cache/*')
    print(f"  ✓ Deployed & cache cleared")
    
    # Verify
    for m in methods_to_add:
        check = run(f'grep -c "function {m}" {remote_path}')
        print(f"  Verify {m}: {'OK' if check == '1' else 'MISSING!'}")

sftp.close()
ssh.close()
print("\nDONE")
