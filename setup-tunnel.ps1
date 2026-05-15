# =====================================================
# Cloudflare Tunnel Setup Script for Vape Story
# Run this ONCE in PowerShell as Administrator
# =====================================================

Write-Host "=== Cloudflare Tunnel Setup for vapestory.serverfaqih.my.id ===" -ForegroundColor Cyan

# --- Step 1: Download cloudflared ---
Write-Host "`n[1/6] Downloading cloudflared..." -ForegroundColor Yellow
$cloudflaredUrl = "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
$cloudflaredPath = "C:\Users\Lenovo\.cloudflared\cloudflared.exe"

# Create directory
New-Item -ItemType Directory -Force -Path "C:\Users\Lenovo\.cloudflared" | Out-Null

# Download
Invoke-WebRequest -Uri $cloudflaredUrl -OutFile $cloudflaredPath -UseBasicParsing

if (Test-Path $cloudflaredPath) {
    Write-Host "  ✓ cloudflared downloaded to $cloudflaredPath" -ForegroundColor Green
} else {
    Write-Host "  ✗ Download failed!" -ForegroundColor Red
    exit 1
}

# --- Step 2: Add to PATH ---
Write-Host "`n[2/6] Adding cloudflared to PATH..." -ForegroundColor Yellow
$currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($currentPath -notlike "*cloudflared*") {
    [Environment]::SetEnvironmentVariable("Path", "$currentPath;C:\Users\Lenovo\.cloudflared", "User")
    Write-Host "  ✓ Added to user PATH (restart terminal after setup)" -ForegroundColor Green
} else {
    Write-Host "  ✓ Already in PATH" -ForegroundColor Green
}

# --- Step 3: Authenticate ---
Write-Host "`n[3/6] Authenticating with Cloudflare..." -ForegroundColor Yellow
Write-Host "  A browser window will open — log in to your Cloudflare account." -ForegroundColor White
Write-Host "  Make sure the account manages the domain: serverfaqih.my.id" -ForegroundColor White
& $cloudflaredPath tunnel login

# --- Step 4: Create Tunnel ---
Write-Host "`n[4/6] Creating tunnel..." -ForegroundColor Yellow
$tunnelOutput = & $cloudflaredPath tunnel create vapestory-tunnel 2>&1
Write-Host $tunnelOutput

# Extract tunnel ID
$tunnelId = ($tunnelOutput | Select-String "Tunnel ID:\s*([a-f0-9-]+)").Matches.Groups[1].Value
Write-Host "  ✓ Tunnel created with ID: $tunnelId" -ForegroundColor Green

# --- Step 5: Configure DNS ---
Write-Host "`n[5/6] Configuring DNS route..." -ForegroundColor Yellow
$dnsOutput = & $cloudflaredPath tunnel route dns vapestory-tunnel vapestory.serverfaqih.my.id 2>&1
Write-Host $dnsOutput
Write-Host "  ✓ DNS record created: vapestory.serverfaqih.my.id → tunnel" -ForegroundColor Green

# --- Step 6: Copy config file ---
Write-Host "`n[6/6] Installing config file..." -ForegroundColor Yellow
$configContent = @"
tunnel: vapestory-tunnel
credentials-file: $env:USERPROFILE\.cloudflared\*.json

ingress:
  - hostname: vapestory.serverfaqih.my.id
    service: http://127.0.0.1:8000
  - service: http_status:404
"@

# Find the actual credentials file
$credFile = Get-ChildItem "$env:USERPROFILE\.cloudflared\*.json" | Select-Object -First 1
if ($credFile) {
    $configContent = $configContent -replace '\*\.json', $credFile.Name
    $configContent | Out-File -FilePath "C:\Users\Lenovo\.cloudflared\config.yml" -Encoding UTF8 -Force
    Write-Host "  ✓ Config saved to C:\Users\Lenovo\.cloudflared\config.yml" -ForegroundColor Green
} else {
    Write-Host "  ⚠ No credentials file found. Manual config needed." -ForegroundColor Yellow
}

Write-Host "`n=== Setup Complete! ===" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "  1. Start your Laravel server:  php artisan serve --host=127.0.0.1 --port=8000" -ForegroundColor White
Write-Host "  2. Start the tunnel:           cloudflared tunnel run vapestory-tunnel" -ForegroundColor White
Write-Host "  3. Open: https://vapestory.serverfaqih.my.id/" -ForegroundColor White
Read-Host "`nPress Enter to exit"