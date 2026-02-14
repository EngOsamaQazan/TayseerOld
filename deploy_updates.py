"""
Deploy updated files to both Jadal and Namaa servers:
1. SiteController.php (merged with production code)
2. image-manager.php view
3. image-manager-admin.css
4. ImageManagerController.php (upload fix)
"""
import paramiko, sys, os, time
sys.stdout.reconfigure(encoding='utf-8')

# ── Files to deploy ──
LOCAL_BASE = r'C:\Users\PC\Desktop\Tayseer'
DEPLOY_FILES = {
    'backend/views/site/image-manager.php': 'backend/views/site/image-manager.php',
    'backend/web/css/image-manager-admin.css': 'backend/web/css/image-manager-admin.css',
    'backend/modules/imagemanager/controllers/ImageManagerController.php': 'backend/modules/imagemanager/controllers/ImageManagerController.php',
}

SERVERS = {
    'jadal': '/var/www/jadal.aqssat.co',
    'namaa': '/var/www/namaa.aqssat.co',
}

def get_ssh():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)
    return ssh

def read_file(path):
    with open(path, 'r', encoding='utf-8') as f:
        return f.read()

def write_remote(sftp, remote_path, content):
    """Write content to remote file"""
    import io
    f = io.BytesIO(content.encode('utf-8'))
    sftp.putfo(f, remote_path)
    print(f"  ✓ Uploaded: {remote_path}")

def deploy_site_controller(ssh, sftp, server_name, server_root):
    """
    Merge SiteController: download production version, add/update our actions
    """
    print(f"\n{'='*60}")
    print(f"  Deploying SiteController to {server_name}")
    print(f"{'='*60}")
    
    remote_path = f"{server_root}/backend/controllers/SiteController.php"
    
    # Download current production version
    stdin, stdout, stderr = ssh.exec_command(f'cat {remote_path}')
    prod_content = stdout.read().decode('utf-8', errors='replace')
    
    if not prod_content.strip():
        print(f"  ✗ Could not read production SiteController from {server_name}")
        return False

    # Read local version for the new methods
    local_content = read_file(os.path.join(LOCAL_BASE, 'backend', 'controllers', 'SiteController.php'))
    
    # Extract all our action methods from local
    import re
    
    methods_to_inject = [
        'actionSystemSettings',
        'actionTestGoogleConnection', 
        'getApiUsageStats',
        'actionImageManager',
        'actionImageManagerData',
        'actionImageReassign',
        'actionImageManagerStats',
    ]
    
    extracted_methods = []
    for method_name in methods_to_inject:
        # Find method in local content
        pattern = rf'(    (?:public |private |protected )?function {method_name}\(.*?\n    \}})'
        match = re.search(pattern, local_content, re.DOTALL)
        if match:
            extracted_methods.append((method_name, match.group(1)))
            print(f"  ✓ Extracted method: {method_name}")
        else:
            print(f"  ✗ Could not extract method: {method_name}")
    
    # Check if production already has these methods
    for method_name, method_code in extracted_methods:
        if f'function {method_name}(' in prod_content:
            # Replace existing method using string find instead of regex (avoids escape issues)
            func_sig = f'function {method_name}('
            start = prod_content.find(func_sig)
            if start == -1:
                continue
            # Walk back to find the method's indentation/visibility
            line_start = prod_content.rfind('\n', 0, start) + 1
            # Find the closing brace at the right indentation (4 spaces + })
            search_from = start
            brace_depth = 0
            end = -1
            for ci in range(search_from, len(prod_content)):
                if prod_content[ci] == '{':
                    brace_depth += 1
                elif prod_content[ci] == '}':
                    brace_depth -= 1
                    if brace_depth == 0:
                        end = ci + 1
                        break
            if end > 0:
                prod_content = prod_content[:line_start] + method_code + prod_content[end:]
                print(f"  ↻ Updated existing method: {method_name}")
            else:
                print(f"  ✗ Could not find end of method: {method_name}")
        else:
            # Insert before the closing brace of the class
            insert_point = prod_content.rfind('}')
            prod_content = prod_content[:insert_point] + '\n' + method_code + '\n\n' + prod_content[insert_point:]
            print(f"  + Added new method: {method_name}")
    
    # Ensure 'use yii\web\Response;' is present
    if 'use yii\\web\\Response;' not in prod_content:
        # Add after the last 'use' statement
        last_use = prod_content.rfind('\nuse ')
        if last_use != -1:
            end_of_line = prod_content.index('\n', last_use + 1)
            prod_content = prod_content[:end_of_line + 1] + 'use yii\\web\\Response;\n' + prod_content[end_of_line + 1:]
            print(f"  + Added 'use yii\\web\\Response;'")

    # Ensure 'use common\\models\\SystemSettings;' is present
    if 'use common\\models\\SystemSettings;' not in prod_content and 'use common\\models\\SystemSettings' not in prod_content:
        last_use = prod_content.rfind('\nuse ')
        if last_use != -1:
            end_of_line = prod_content.index('\n', last_use + 1)
            prod_content = prod_content[:end_of_line + 1] + 'use common\\models\\SystemSettings;\n' + prod_content[end_of_line + 1:]
            print(f"  + Added 'use common\\models\\SystemSettings;'")

    # Check behaviors() for allowed actions
    behaviors_actions = [
        'system-settings', 'test-google-connection',
        'image-manager', 'image-manager-data', 'image-reassign', 'image-manager-stats'
    ]
    for action in behaviors_actions:
        if f"'{action}'" not in prod_content:
            print(f"  ⚠ Action '{action}' not found in behaviors() — may need manual check")
    
    # Backup and upload
    backup_path = f"{remote_path}.bak.{int(time.time())}"
    ssh.exec_command(f'cp {remote_path} {backup_path}')
    print(f"  ✓ Backup created: {backup_path}")
    
    write_remote(sftp, remote_path, prod_content)
    
    # Set permissions
    ssh.exec_command(f'chown www-data:www-data {remote_path}')
    return True

def main():
    ssh = get_ssh()
    sftp = ssh.open_sftp()
    
    for server_name, server_root in SERVERS.items():
        print(f"\n{'#'*60}")
        print(f"  SERVER: {server_name} ({server_root})")
        print(f"{'#'*60}")
        
        # Deploy SiteController (merge)
        deploy_site_controller(ssh, sftp, server_name, server_root)
        
        # Deploy other files directly
        for local_rel, remote_rel in DEPLOY_FILES.items():
            local_path = os.path.join(LOCAL_BASE, local_rel.replace('/', os.sep))
            remote_path = f"{server_root}/{remote_rel}"
            
            if not os.path.exists(local_path):
                print(f"  ✗ Local file not found: {local_path}")
                continue
            
            # Backup
            ssh.exec_command(f'cp {remote_path} {remote_path}.bak.{int(time.time())} 2>/dev/null')
            
            # Upload
            content = read_file(local_path)
            write_remote(sftp, remote_path, content)
            ssh.exec_command(f'chown www-data:www-data {remote_path}')
        
        # Clear Yii2 cache
        ssh.exec_command(f'rm -rf {server_root}/backend/runtime/cache/*')
        print(f"  ✓ Cache cleared for {server_name}")
    
    # Verify deployment
    print(f"\n{'='*60}")
    print(f"  VERIFICATION")
    print(f"{'='*60}")
    
    for server_name, server_root in SERVERS.items():
        for check_file in ['backend/controllers/SiteController.php', 'backend/views/site/image-manager.php', 'backend/web/css/image-manager-admin.css', 'backend/modules/imagemanager/controllers/ImageManagerController.php']:
            stdin, stdout, stderr = ssh.exec_command(f'wc -l {server_root}/{check_file}')
            result = stdout.read().decode().strip()
            print(f"  {server_name}: {check_file} → {result}")
    
    sftp.close()
    ssh.close()
    print("\n✓ Deployment complete!")

if __name__ == '__main__':
    main()
