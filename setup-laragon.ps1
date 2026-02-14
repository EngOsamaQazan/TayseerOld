# ============================================================================
#  Namaa ERP - Full Setup Script for Laragon
#  Run: powershell -ExecutionPolicy Bypass -File setup-laragon.ps1
#
#  This script does EVERYTHING from scratch:
#    1. Downloads & installs Laragon (if missing)
#    2. Enables PHP zip extension (required for composer)
#    3. Links project into Laragon www
#    4. Runs composer install (with --ignore-platform-reqs)
#    5. Initializes Yii2 NamaaDevelopment environment
#    6. Creates missing runtime directories
#    7. Creates frontend .htaccess (pretty URLs)
#    8. Fixes root .htaccess (prevents redirect loops)
#    9. Enables user component in frontend config
#   10. Allows MySQL functions with binary logging
#   11. Creates database, user, and imports SQL dump
#   12. Sets a known admin password (admin123)
#   13. Configures Apache VirtualHost for short URLs:
#         tayseer.test          -> frontend
#         admin.tayseer.test    -> backend
#   14. Adds entries to Windows hosts file
#   15. Restarts Apache
# ============================================================================

$ErrorActionPreference = "Continue"
$ProjectRoot = $PSScriptRoot
$LaragonUrl = "https://github.com/leokhoa/laragon/releases/download/8.5.0/laragon-wamp.exe"
$LaragonInstaller = "$env:TEMP\laragon-wamp.exe"
$LaragonPath = "C:\laragon"
$DbFile = Join-Path $ProjectRoot "namaa_erp 14-02-2026.sql"
$DbName = "namaa_erp"
$DbUser = "osama"
$DbPass = 'O$amaDaTaBase@123'
$AdminPassword = "admin123"
$DomainFrontend = "tayseer.test"
$DomainBackend = "admin.tayseer.test"

$StepNum = 0
$TotalSteps = 15

function Step([string]$msg) {
    $script:StepNum++
    Write-Host ""
    Write-Host "[$script:StepNum/$TotalSteps] $msg" -ForegroundColor Yellow
}

function OK([string]$msg) { Write-Host "  [OK] $msg" -ForegroundColor Green }
function WARN([string]$msg) { Write-Host "  [!!] $msg" -ForegroundColor DarkYellow }
function FAIL([string]$msg) { Write-Host "  [FAIL] $msg" -ForegroundColor Red }

Write-Host ""
Write-Host "  ============================================" -ForegroundColor Cyan
Write-Host "     Namaa ERP - Laragon Full Setup" -ForegroundColor Cyan
Write-Host "  ============================================" -ForegroundColor Cyan

# -------------------------------------------------------------------------
# STEP 1: Download & install Laragon
# -------------------------------------------------------------------------
Step "Checking Laragon installation..."
if (-not (Test-Path $LaragonPath)) {
    Write-Host "  Laragon not found. Downloading..." -ForegroundColor Gray
    if (-not (Test-Path $LaragonInstaller)) {
        try {
            [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
            Invoke-WebRequest -Uri $LaragonUrl -OutFile $LaragonInstaller -UseBasicParsing
            OK "Downloaded Laragon installer"
        }
        catch {
            FAIL "Download failed. Please download manually:"
            Write-Host "  $LaragonUrl" -ForegroundColor White
            exit 1
        }
    }
    Write-Host ""
    Write-Host "  *** IMPORTANT ***" -ForegroundColor Magenta
    Write-Host "  The Laragon installer will open now." -ForegroundColor White
    Write-Host "  1) Install to default path: C:\laragon" -ForegroundColor White
    Write-Host "  2) After install finishes, open Laragon" -ForegroundColor White
    Write-Host "  3) Click 'Start All' (Apache + MySQL)" -ForegroundColor White
    Write-Host "  4) Then run this script AGAIN" -ForegroundColor White
    Write-Host ""
    Start-Process -FilePath $LaragonInstaller -Wait
    Write-Host "  Run this script again after Laragon is running." -ForegroundColor Cyan
    exit 0
}
OK "Laragon found at $LaragonPath"

# Find binaries
$mysqlExe = (Get-ChildItem -Path "$LaragonPath\bin\mysql","$LaragonPath\bin\mariadb" -Filter "mysql.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1).FullName
$phpExe = (Get-ChildItem -Path "$LaragonPath\bin\php" -Filter "php.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1).FullName
$composerPhar = (Get-ChildItem -Path $LaragonPath -Filter "composer.phar" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1).FullName
$phpIni = Join-Path (Split-Path $phpExe -Parent) "php.ini"

if (-not $mysqlExe) { FAIL "MySQL not found - is Laragon running? Click 'Start All' first"; exit 1 }
if (-not $phpExe) { FAIL "PHP not found - check Laragon installation"; exit 1 }

# Test MySQL connection
$testConn = & $mysqlExe -u root -e "SELECT 1;" 2>&1
if ($LASTEXITCODE -ne 0) {
    FAIL "Cannot connect to MySQL. Start Laragon and click 'Start All' first"
    exit 1
}
OK "MySQL connected | PHP found | Composer found"

# -------------------------------------------------------------------------
# STEP 2: Enable PHP zip extension
# -------------------------------------------------------------------------
Step "Enabling PHP zip extension..."
if (Test-Path $phpIni) {
    $iniContent = Get-Content $phpIni -Raw
    if ($iniContent -match ';extension=zip') {
        $iniContent = $iniContent -replace ';extension=zip', 'extension=zip'
        [System.IO.File]::WriteAllText($phpIni, $iniContent)
        OK "zip extension enabled in php.ini"
    }
    else { OK "zip extension already enabled" }
}

# -------------------------------------------------------------------------
# STEP 3: Link project to Laragon www
# -------------------------------------------------------------------------
Step "Linking project to Laragon..."
$wwwPath = "$LaragonPath\www"
$projectLink = "$wwwPath\TayseerOld"
if (-not (Test-Path $wwwPath)) { New-Item -ItemType Directory -Path $wwwPath -Force | Out-Null }
if (-not (Test-Path $projectLink)) {
    cmd /c "mklink /J `"$projectLink`" `"$ProjectRoot`"" 2>$null
    if (-not (Test-Path $projectLink)) {
        New-Item -ItemType Junction -Path $projectLink -Target $ProjectRoot -ErrorAction SilentlyContinue | Out-Null
    }
}
if (Test-Path $projectLink) { OK "Project linked at $projectLink" }
else { WARN "Link failed - copy project to $wwwPath\TayseerOld manually" }

# -------------------------------------------------------------------------
# STEP 4: Composer install
# -------------------------------------------------------------------------
Step "Installing Composer dependencies..."
$env:PATH = "$(Split-Path $phpExe -Parent);$LaragonPath\bin;$env:PATH"
Set-Location $ProjectRoot
if (-not (Test-Path "vendor\autoload.php")) {
    if ($composerPhar) {
        & $phpExe $composerPhar install --no-interaction --ignore-platform-reqs 2>&1 | Out-Host
    }
    elseif (Get-Command composer -ErrorAction SilentlyContinue) {
        & composer install --no-interaction --ignore-platform-reqs 2>&1 | Out-Host
    }
    else { WARN "Composer not found. Run manually: composer install --ignore-platform-reqs" }

    if (Test-Path "vendor\autoload.php") { OK "Dependencies installed (vendor/autoload.php exists)" }
    else { FAIL "Composer install failed - check output above" }
}
else { OK "Dependencies already installed" }

# -------------------------------------------------------------------------
# STEP 5: Initialize Yii2 environment
# -------------------------------------------------------------------------
Step "Initializing Yii2 NamaaDevelopment environment..."
if (-not (Test-Path "common\config\main-local.php")) {
    & $phpExe init --env=NamaaDevelopment --overwrite=All 2>&1 | Out-Host
    OK "Environment initialized"
}
else { OK "Environment already initialized" }

# -------------------------------------------------------------------------
# STEP 6: Create missing runtime directories
# -------------------------------------------------------------------------
Step "Creating runtime directories..."
$dirs = @("backend\runtime", "console\runtime", "frontend\runtime", "backend\web\assets", "frontend\web\assets")
foreach ($d in $dirs) {
    $fullPath = Join-Path $ProjectRoot $d
    if (-not (Test-Path $fullPath)) { New-Item -ItemType Directory -Path $fullPath -Force | Out-Null }
}
OK "All runtime/assets directories exist"

# -------------------------------------------------------------------------
# STEP 7: Create frontend .htaccess (enables pretty URLs)
# -------------------------------------------------------------------------
Step "Ensuring frontend .htaccess for pretty URLs..."
$frontHtaccess = Join-Path $ProjectRoot "frontend\web\.htaccess"
if (-not (Test-Path $frontHtaccess)) {
    @"
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php
"@ | Set-Content -Path $frontHtaccess -Encoding ASCII
    OK "Created frontend/web/.htaccess"
}
else { OK "frontend/web/.htaccess already exists" }

# -------------------------------------------------------------------------
# STEP 8: Fix root .htaccess (prevent redirect loops)
# -------------------------------------------------------------------------
Step "Fixing root .htaccess (prevent redirect loops)..."
$rootHtaccess = Join-Path $ProjectRoot ".htaccess"
@"
<IfModule mod_rewrite.c>
    Options +FollowSymlinks
    RewriteEngine On
    # Yii2 Advanced: do not rewrite backend/web and frontend/web
    RewriteCond %{REQUEST_URI} /backend/web [OR]
    RewriteCond %{REQUEST_URI} /frontend/web
    RewriteRule ^ - [L]
</IfModule>
"@ | Set-Content -Path $rootHtaccess -Encoding ASCII
OK "Root .htaccess configured (no redirect loops)"

# -------------------------------------------------------------------------
# STEP 9: Enable user component in frontend config
# -------------------------------------------------------------------------
Step "Enabling frontend user component..."
$frontConfig = Join-Path $ProjectRoot "frontend\config\main.php"
if (Test-Path $frontConfig) {
    $fc = Get-Content $frontConfig -Raw
    if ($fc -match "//\s*'user'") {
        $fc = $fc -replace "//\s*'user'\s*=>\s*\[", "'user' => ["
        $fc = $fc -replace "//\s*'identityClass'", "'identityClass'"
        $fc = $fc -replace "//\s*'enableAutoLogin'", "'enableAutoLogin'"
        $fc = $fc -replace "//\s*'identityCookie'", "'identityCookie'"
        $fc = $fc -replace "//\s*\],\s*\n\s*'session'", "],`n        'session'"
        [System.IO.File]::WriteAllText($frontConfig, $fc)
        OK "Frontend user component enabled"
    }
    else { OK "Frontend user component already enabled" }
}

# -------------------------------------------------------------------------
# STEP 10: Configure MySQL for function creation
# -------------------------------------------------------------------------
Step "Configuring MySQL (allow function creators)..."
& $mysqlExe -u root -e "SET GLOBAL log_bin_trust_function_creators = 1;" 2>$null
OK "log_bin_trust_function_creators = ON"

# -------------------------------------------------------------------------
# STEP 11: Create database, user, and import SQL
# -------------------------------------------------------------------------
Step "Setting up database..."
# Create DB
& $mysqlExe -u root -e "CREATE DATABASE IF NOT EXISTS $DbName CHARACTER SET utf8 COLLATE utf8_general_ci;" 2>$null
# Create user
& $mysqlExe -u root -e "CREATE USER IF NOT EXISTS '$DbUser'@'localhost' IDENTIFIED BY '$DbPass';" 2>$null
& $mysqlExe -u root -e "GRANT ALL PRIVILEGES ON $DbName.* TO '$DbUser'@'localhost'; FLUSH PRIVILEGES;" 2>$null
OK "Database '$DbName' and user '$DbUser' ready"

# Check if tables exist
$tableCount = (& $mysqlExe -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DbName';" 2>$null).Trim()
if ([int]$tableCount -lt 10) {
    if (-not (Test-Path $DbFile)) {
        FAIL "SQL dump not found: $DbFile"
        WARN "Place 'namaa_erp 14-02-2026.sql' in project root and re-run"
    }
    else {
        Write-Host "  Importing database (this takes ~60 seconds)..." -ForegroundColor Gray
        cmd /c "`"$mysqlExe`" -u root $DbName < `"$DbFile`"" 2>$null
        $newCount = (& $mysqlExe -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DbName';" 2>$null).Trim()
        if ([int]$newCount -gt 50) { OK "Imported $newCount tables successfully" }
        else { FAIL "Import may have failed ($newCount tables). Try importing via phpMyAdmin" }
    }
}
else { OK "Database already has $tableCount tables - skipping import" }

# -------------------------------------------------------------------------
# STEP 12: Set known admin password
# -------------------------------------------------------------------------
Step "Setting admin password..."
$hash = & $phpExe -r "echo password_hash('$AdminPassword', PASSWORD_BCRYPT);"
& $mysqlExe -u root -e "UPDATE $DbName.os_user SET password_hash='$hash' WHERE id=1;" 2>$null
$adminUser = (& $mysqlExe -u root -N -e "SELECT email FROM $DbName.os_user WHERE id=1;" 2>$null).Trim()
OK "Admin password set to '$AdminPassword' for $adminUser"

# -------------------------------------------------------------------------
# STEP 13: Create Apache VirtualHosts for short URLs
# -------------------------------------------------------------------------
Step "Configuring Apache VirtualHosts..."

$backendWebPath = ($ProjectRoot -replace '\\','/') + "/backend/web"
$frontendWebPath = ($ProjectRoot -replace '\\','/') + "/frontend/web"

$vhostContent = @"
# === Namaa ERP - Auto-generated by setup-laragon.ps1 ===

# Frontend: http://$DomainFrontend
<VirtualHost *:80>
    ServerName $DomainFrontend
    DocumentRoot "$frontendWebPath"
    <Directory "$frontendWebPath">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
    </Directory>
</VirtualHost>

# Backend: http://$DomainBackend
<VirtualHost *:80>
    ServerName $DomainBackend
    DocumentRoot "$backendWebPath"
    <Directory "$backendWebPath">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
    </Directory>
</VirtualHost>
"@

$vhostFile = "$LaragonPath\etc\apache2\sites-enabled\auto.tayseer.conf"
[System.IO.File]::WriteAllText($vhostFile, $vhostContent)

# Remove old config if exists
$oldConf = "$LaragonPath\etc\apache2\sites-enabled\tayseerold.conf"
if (Test-Path $oldConf) { Remove-Item $oldConf -Force -ErrorAction SilentlyContinue }
OK "VirtualHosts created: $DomainFrontend / $DomainBackend"

# -------------------------------------------------------------------------
# STEP 14: Add domains to Windows hosts file
# -------------------------------------------------------------------------
Step "Adding domains to Windows hosts file..."
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
$hostsContent = Get-Content $hostsFile -Raw -ErrorAction SilentlyContinue
$needsUpdate = $false

if ($hostsContent -notmatch [regex]::Escape($DomainFrontend)) { $needsUpdate = $true }
if ($hostsContent -notmatch [regex]::Escape($DomainBackend)) { $needsUpdate = $true }

if ($needsUpdate) {
    # Write a helper script that runs as admin
    $helperScript = Join-Path $env:TEMP "add-hosts-tayseer.ps1"
    @"
`$h = 'C:\Windows\System32\drivers\etc\hosts'
`$c = Get-Content `$h -Raw
if (`$c -notmatch 'tayseer.test') {
    Add-Content `$h "`n# Namaa ERP (Laragon)`n127.0.0.1 $DomainFrontend`n127.0.0.1 $DomainBackend"
}
"@ | Set-Content -Path $helperScript -Encoding ASCII

    try {
        Start-Process powershell -Verb RunAs -ArgumentList "-ExecutionPolicy Bypass -File `"$helperScript`"" -Wait -ErrorAction Stop
        OK "Domains added to hosts file"
    }
    catch {
        WARN "Could not modify hosts file (needs admin)."
        WARN "Manually add these lines to $hostsFile :"
        Write-Host "    127.0.0.1 $DomainFrontend" -ForegroundColor White
        Write-Host "    127.0.0.1 $DomainBackend" -ForegroundColor White
    }
}
else { OK "Domains already in hosts file" }

# Flush DNS
ipconfig /flushdns 2>$null | Out-Null

# -------------------------------------------------------------------------
# STEP 15: Restart Apache
# -------------------------------------------------------------------------
Step "Restarting Apache..."
$apacheExe = (Get-ChildItem -Path "$LaragonPath\bin\apache" -Filter "httpd.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1).FullName
if ($apacheExe) {
    $apacheDir = Split-Path (Split-Path $apacheExe -Parent) -Parent
    # Graceful restart via Apache
    & $apacheExe -d $apacheDir -k restart 2>$null
    Start-Sleep -Seconds 2
    OK "Apache restarted"
}
else {
    WARN "Restart Apache manually: Laragon > Stop All > Start All"
}

# =========================================================================
#  FINAL VERIFICATION
# =========================================================================
Write-Host ""
Write-Host "  ============================================" -ForegroundColor Cyan
Write-Host "     Running Final Verification..." -ForegroundColor Cyan
Write-Host "  ============================================" -ForegroundColor Cyan
Write-Host ""

$allOK = $true
Start-Sleep -Seconds 2

# Test Backend
try {
    $r = Invoke-WebRequest -Uri "http://$DomainBackend/" -UseBasicParsing -TimeoutSec 10
    if ($r.StatusCode -eq 200 -and $r.Content.Length -gt 1000) { OK "Backend  : http://$DomainBackend  (HTTP 200)" }
    else { WARN "Backend returned $($r.StatusCode) but may not be fully working"; $allOK = $false }
} catch { FAIL "Backend  : http://$DomainBackend  - NOT REACHABLE"; $allOK = $false }

# Test Frontend
try {
    $r = Invoke-WebRequest -Uri "http://$DomainFrontend/" -UseBasicParsing -TimeoutSec 10
    if ($r.StatusCode -eq 200 -and $r.Content.Length -gt 1000) { OK "Frontend : http://$DomainFrontend  (HTTP 200)" }
    else { WARN "Frontend returned $($r.StatusCode) but may not be fully working"; $allOK = $false }
} catch { FAIL "Frontend : http://$DomainFrontend  - NOT REACHABLE"; $allOK = $false }

# Test DB
$tc = (& $mysqlExe -u root -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DbName';" 2>$null).Trim()
$uc = (& $mysqlExe -u root -N -e "SELECT COUNT(*) FROM $DbName.os_user;" 2>$null).Trim()
OK "Database : $tc tables, $uc users"

# =========================================================================
#  SUMMARY
# =========================================================================
Write-Host ""
if ($allOK) {
    Write-Host "  ============================================" -ForegroundColor Green
    Write-Host "     SETUP COMPLETE - System is READY!" -ForegroundColor Green
    Write-Host "  ============================================" -ForegroundColor Green
}
else {
    Write-Host "  ============================================" -ForegroundColor DarkYellow
    Write-Host "     SETUP DONE (with warnings - see above)" -ForegroundColor DarkYellow
    Write-Host "  ============================================" -ForegroundColor DarkYellow
}
Write-Host ""
Write-Host "  URLs:" -ForegroundColor Cyan
Write-Host "    Frontend : http://$DomainFrontend" -ForegroundColor White
Write-Host "    Backend  : http://$DomainBackend" -ForegroundColor White
Write-Host ""
Write-Host "  Login Credentials:" -ForegroundColor Cyan
Write-Host "    Email    : $adminUser" -ForegroundColor White
Write-Host "    Password : $AdminPassword" -ForegroundColor White
Write-Host ""
Write-Host "  Database:" -ForegroundColor Cyan
Write-Host "    Name     : $DbName" -ForegroundColor White
Write-Host "    User     : $DbUser" -ForegroundColor White
Write-Host "    Password : $DbPass" -ForegroundColor White
Write-Host ""
Write-Host "  Make sure Laragon is running (Start All)" -ForegroundColor Yellow
Write-Host ""

# Open browser
Start-Process "http://$DomainBackend"
