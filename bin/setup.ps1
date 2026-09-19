$ErrorActionPreference = "Stop"

if (-not (Get-Command php -ErrorAction SilentlyContinue)) { throw "PHP is required" }
if (-not (Get-Command composer -ErrorAction SilentlyContinue)) { throw "Composer is required" }
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) { throw "npm is required" }

if (-not (Test-Path ".env")) { Copy-Item ".env.example" ".env" }
composer install
php artisan key:generate --force
php artisan env:sync --yes
npm install
npm run build
php artisan app:init --force
php artisan app:doctor

Write-Host ""
Write-Host "Setup complete. Start with: php artisan serve"
Write-Host "Foundation dashboard: http://127.0.0.1:8000/__foundation"
