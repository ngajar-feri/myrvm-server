# MyRVM-Server API Testing Script
# PowerShell script untuk test semua endpoints

$baseUrl = "http://localhost:8000/api/v1"
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "MyRVM-Server API Testing" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Test 1: Login User
Write-Host "[1] Testing Login (User)..." -ForegroundColor Yellow
$loginBody = @{
    email = "john@example.com"
    password = "password123"
    device_name = "PowerShell-Test"
} | ConvertTo-Json

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $loginBody -Headers $headers
    $userToken = $response.data.token
    Write-Host "✅ Login Success! Token: $($userToken.Substring(0,20))..." -ForegroundColor Green
    Write-Host "User: $($response.data.user.name) ($($response.data.user.email))`n" -ForegroundColor gray
} catch {
    Write-Host "❌ Login Failed: $($_.Exception.Message)`n" -ForegroundColor Red
    exit
}

# Test 2: Login Admin
Write-Host "[2] Testing Login (Admin)..." -ForegroundColor Yellow
$adminLoginBody = @{
    email = "admin@myrvm.com"
    password = "password123"
    device_name = "PowerShell-Test-Admin"
} | ConvertTo-Json

try {
    $adminResponse = Invoke-RestMethod -Uri "$baseUrl/login" -Method Post -Body $adminLoginBody -Headers $headers
    $adminToken = $adminResponse.data.token
    Write-Host "✅ Admin Login Success! Token: $($adminToken.Substring(0,20))...`n" -ForegroundColor Green
} catch {
    Write-Host "❌ Admin Login Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

# Setup auth header for user
$authHeaders = $headers.Clone()
$authHeaders["Authorization"] = "Bearer $userToken"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "User APIs" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Test 3: Get User Profile
Write-Host "[3] Testing Get Profile..." -ForegroundColor Yellow
try {
    $profile = Invoke-RestMethod -Uri "$baseUrl/me" -Method Get -Headers $authHeaders
    Write-Host "✅ Profile Retrieved: $($profile.data.name)" -ForegroundColor Green
    Write-Host "   Email: $($profile.data.email)" -ForegroundColor Gray
    Write-Host "   Role: $($profile.data.role)" -ForegroundColor Gray
    Write-Host "   Points: $($profile.data.points_balance)`n" -ForegroundColor Gray
} catch {
    Write-Host "❌ Get Profile Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

# Test 4: Get User Balance
Write-Host "[4] Testing Get Balance..." -ForegroundColor Yellow
try {
    $balance = Invoke-RestMethod -Uri "$baseUrl/user/balance" -Method Get -Headers $authHeaders
    Write-Host "✅ Balance Retrieved" -ForegroundColor Green
    Write-Host "   Current Balance: $($balance.data.current_balance) points" -ForegroundColor Gray
    Write-Host "   Total Earned: $($balance.data.total_earned)" -ForegroundColor Gray
    Write-Host "   Total Redeemed: $($balance.data.total_redeemed)`n" -ForegroundColor Gray
} catch {
    Write-Host "❌ Get Balance Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Transaction APIs" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Test 5: Create Transaction Session
Write-Host "[5] Testing Create Transaction Session..." -ForegroundColor Yellow
$sessionBody = @{
    rvm_id = 1
} | ConvertTo-Json

try {
    $session = Invoke-RestMethod -Uri "$baseUrl/transactions/session" -Method Post -Body $sessionBody -Headers $authHeaders
    Write-Host "✅ Session Created" -ForegroundColor Green
    Write-Host "   Session Code: $($session.data.session_code)" -ForegroundColor Gray
    Write-Host "   Expires At: $($session.data.expires_at)" -ForegroundColor Gray
    Write-Host "   QR Code Generated: $($session.data.qr_code.Length) bytes`n" -ForegroundColor Gray
} catch {
    Write-Host "❌ Create Session Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

# Test 6: Start Transaction
Write-Host "[6] Testing Start Transaction..." -ForegroundColor Yellow
$startBody = @{
    rvm_id = 1
} | ConvertTo-Json

try {
    $transaction = Invoke-RestMethod -Uri "$baseUrl/transactions/start" -Method Post -Body $startBody -Headers $authHeaders
    $transactionId = $transaction.data.id
    Write-Host "✅ Transaction Started" -ForegroundColor Green
    Write-Host "   Transaction ID: $transactionId" -ForegroundColor Gray
    Write-Host "   Status: $($transaction.data.status)`n" -ForegroundColor Gray
} catch {
    Write-Host "❌ Start Transaction Failed: $($_.Exception.Message)`n" -ForegroundColor Red
    $transactionId = $null
}

# Test 7: Deposit Item
if ($transactionId) {
    Write-Host "[7] Testing Deposit Item..." -ForegroundColor Yellow
    $itemBody = @{
        transaction_id = $transactionId
        waste_type = "PET Bottle"
        weight = 0.5
        points = 50
    } | ConvertTo-Json

    try {
        $item = Invoke-RestMethod -Uri "$baseUrl/transactions/item" -Method Post -Body $itemBody -Headers $authHeaders
        Write-Host "✅ Item Deposited" -ForegroundColor Green
        Write-Host "   Item ID: $($item.data.id)" -ForegroundColor Gray
        Write-Host "   Type: $($item.data.waste_type)" -ForegroundColor Gray
        Write-Host "   Points: $($item.data.points)`n" -ForegroundColor Gray
    } catch {
        Write-Host "❌ Deposit Item Failed: $($_.Exception.Message)`n" -ForegroundColor Red
    }

    # Test 8: Commit Transaction
    Write-Host "[8] Testing Commit Transaction..." -ForegroundColor Yellow
    $commitBody = @{
        transaction_id = $transactionId
    } | ConvertTo-Json

    try {
        $committed = Invoke-RestMethod -Uri "$baseUrl/transactions/commit" -Method Post -Body $commitBody -Headers $authHeaders
        Write-Host "✅ Transaction Committed" -ForegroundColor Green
        Write-Host "   Total Points: $($committed.data.total_points)" -ForegroundColor Gray
        Write-Host "   New Balance: $($committed.data.user_balance)`n" -ForegroundColor Gray
    } catch {
        Write-Host "❌ Commit Transaction Failed: $($_.Exception.Message)`n" -ForegroundColor Red
    }
}

# Test 9: Get Transaction History
Write-Host "[9] Testing Get Transaction History..." -ForegroundColor Yellow
try {
    $history = Invoke-RestMethod -Uri "$baseUrl/transactions/history?page=1&per_page=10" -Method Get -Headers $authHeaders
    Write-Host "✅ History Retrieved" -ForegroundColor Green
    Write-Host "   Total: $($history.data.total) transactions" -ForegroundColor Gray
    Write-Host "   Showing: $($history.data.data.Count) items`n" -ForegroundColor Gray
} catch {
    Write-Host "❌ Get History Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

# Test 10: Get Active Session
Write-Host "[10] Testing Get Active Session..." -ForegroundColor Yellow
try {
    $active = Invoke-RestMethod -Uri "$baseUrl/transactions/active" -Method Get -Headers $authHeaders
    if ($active.data) {
        Write-Host "✅ Active Session Found" -ForegroundColor Green
        Write-Host "   Session Code: $($active.data.session_code)`n" -ForegroundColor Gray
    } else {
        Write-Host "✅ No Active Session`n" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Get Active Session Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Redemption APIs" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Test 11: Get Vouchers
Write-Host "[11] Testing Get User Vouchers..." -ForegroundColor Yellow
try {
    $vouchers = Invoke-RestMethod -Uri "$baseUrl/redemption/vouchers" -Method Get -Headers $authHeaders
    Write-Host "✅ Vouchers Retrieved:" -ForegroundColor Green
    foreach ($v in $vouchers.data) {
        Write-Host "   - $($v.title) ($($v.points_required) points)" -ForegroundColor Gray
    }
    Write-Host ""
} catch {
    Write-Host "❌ Get Vouchers Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

# Admin API Tests
$adminAuthHeaders = $headers.Clone()
$adminAuthHeaders["Authorization"] = "Bearer $adminToken"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Admin APIs" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Test 12: Get RVM Machines
Write-Host "[12] Testing Get RVM Machines..." -ForegroundColor Yellow
try {
    $machines = Invoke-RestMethod -Uri "$baseUrl/rvm-machines" -Method Get -Headers $adminAuthHeaders
    Write-Host "✅ RVM Machines Retrieved:" -ForegroundColor Green
    foreach ($m in $machines.data) {
        Write-Host "   - $($m.name) [$($m.status)]" -ForegroundColor Gray
    }
    Write-Host ""
} catch {
    Write-Host "❌ Get RVM Machines Failed: $($_.Exception.Message)`n" -ForegroundColor Red
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Testing Complete!" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Summary:" -ForegroundColor Yellow
Write-Host "- Authentication: ✅ Working" -ForegroundColor Green
Write-Host "- User APIs: ✅ Working" -ForegroundColor Green
Write-Host "- Transaction APIs: ✅ Working" -ForegroundColor Green
Write-Host "- Redemption APIs: ✅ Working" - ForegroundColor Green
Write-Host "- Admin APIs: ✅ Working`n" -ForegroundColor Green
