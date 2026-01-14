# Push ksf_ofxparser to GitHub
$ErrorActionPreference = "Stop"

Write-Host "Checking git status..." -ForegroundColor Cyan
git status

Write-Host "`nStaging all files..." -ForegroundColor Cyan
git add -A

Write-Host "`nCommitting changes..." -ForegroundColor Cyan
git commit -m "Added PHP 7.3+ type hints and comprehensive tests - Jan 13, 2026"

Write-Host "`nChecking remote..." -ForegroundColor Cyan
git remote -v

Write-Host "`nDone! Please manually push to GitHub with:" -ForegroundColor Green
Write-Host "git push origin main" -ForegroundColor Yellow
