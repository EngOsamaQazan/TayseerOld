import paramiko, sys
sys.stdout.reconfigure(encoding='utf-8')

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
ssh.connect('54.38.236.112', username='root', password='Hussain@1986', timeout=15)

commands = [
    # Get actionCreate method from server CustomersController
    'sed -n "/function actionCreate/,/function action/p" /var/www/jadal.aqssat.co/backend/modules/customers/controllers/CustomersController.php | head -120',
    
    # Get the _smart_form.php upload section
    'grep -n -B2 -A8 "smart-media\|SmartMedia\|drop-zone\|imagemanagerFiles\|uploadUrl\|customer_documents" /var/www/jadal.aqssat.co/backend/modules/customers/views/customers/_smart_form.php 2>/dev/null | head -80',
    
    # Check the ImageManager module init on server (might have path logic)
    'cat /var/www/jadal.aqssat.co/backend/modules/imagemanager/ImageManagerModule.php',
    
    # Check the ImageManagerGetPath component
    'cat /var/www/jadal.aqssat.co/vendor/noam148/yii2-image-manager/components/ImageManagerGetPath.php',
]

for cmd in commands:
    stdin, stdout, stderr = ssh.exec_command(cmd, timeout=30)
    out = stdout.read().decode('utf-8', errors='replace').strip()
    if out:
        print(out)
    print("---END---")
    print()

ssh.close()
print("DONE")
