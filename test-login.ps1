# Test login and customers/create page
$baseUrl = "http://tayseer.test"

Write-Host "=== Step 1: Fetching login page to get CSRF token ===" -ForegroundColor Cyan
$loginPageUrl = "$baseUrl/"
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

try {
    $loginPage = Invoke-WebRequest -Uri $loginPageUrl -WebSession $session -UseBasicParsing
    
    # Extract CSRF token
    if ($loginPage.Content -match 'name="_csrf-backend"\s+value="([^"]+)"') {
        $csrfToken = $matches[1]
        Write-Host "CSRF Token extracted: $($csrfToken.Substring(0, 20))..." -ForegroundColor Green
    } else {
        Write-Host "Could not extract CSRF token" -ForegroundColor Red
        exit 1
    }

    Write-Host "`n=== Step 2: Submitting login form ===" -ForegroundColor Cyan
    $loginUrl = "$baseUrl/user/login"
    $postData = @{
        '_csrf-backend' = $csrfToken
        'login-form[login]' = 'osamaqazan89@gmail.com'
        'login-form[password]' = 'admin123'
        'login-form[rememberMe]' = '0'
    }
    
    $loginResponse = Invoke-WebRequest -Uri $loginUrl -Method POST -Body $postData -WebSession $session -UseBasicParsing -MaximumRedirection 5 -ErrorAction SilentlyContinue
    
    Write-Host "Login response status: $($loginResponse.StatusCode)" -ForegroundColor Yellow
    
    Write-Host "`n=== Step 3: Accessing customers/create page ===" -ForegroundColor Cyan
    $customerCreateUrl = "$baseUrl/customers/create"
    $customerPage = Invoke-WebRequest -Uri $customerCreateUrl -WebSession $session -UseBasicParsing
    
    Write-Host "Customer create page status: $($customerPage.StatusCode)" -ForegroundColor Yellow
    
    # Save the page content
    $outputFile = "$env:TEMP\customers-create-page.html"
    $customerPage.Content | Out-File -FilePath $outputFile -Encoding UTF8
    
    Write-Host "`n=== Step 4: Analyzing page content ===" -ForegroundColor Cyan
    
    # Check for login redirect
    if ($customerPage.Content -match 'login-form|تسجيل الدخول') {
        Write-Host "WARNING: Redirected to login page - authentication failed" -ForegroundColor Red
    }
    
    # Check for error messages
    if ($customerPage.Content -match '<h1') {
        if ($customerPage.Content -match '<h1[^>]*>([^<]+)</h1>') {
            Write-Host "Page Title: $($matches[1])" -ForegroundColor Green
        }
    }
    
    if ($customerPage.Content -match 'error|Error|Exception') {
        Write-Host "Error detected on page!" -ForegroundColor Red
    } else {
        Write-Host "No obvious errors detected" -ForegroundColor Green
    }
    
    # Check if it's a form page
    if ($customerPage.Content -match '<form') {
        Write-Host "Form detected - likely the create customer page loaded correctly" -ForegroundColor Green
    } else {
        Write-Host "No form detected - might be redirected or error page" -ForegroundColor Yellow
    }
    
    Write-Host "`n=== Page Content Preview ===" -ForegroundColor Cyan
    $lines = $customerPage.Content -split "`n"
    $lines | Select-Object -First 100 | ForEach-Object { $_ }
    
    Write-Host "`n`nFull content saved to: $outputFile" -ForegroundColor Cyan
    
} catch {
    Write-Host "Error occurred: $_" -ForegroundColor Red
    Write-Host "Exception details: $($_.Exception.Message)" -ForegroundColor Red
}
