#!/usr/bin/env bash
set -euo pipefail

command -v php >/dev/null || { echo "PHP is required" >&2; exit 1; }
command -v composer >/dev/null || { echo "Composer is required" >&2; exit 1; }
command -v npm >/dev/null || { echo "npm is required" >&2; exit 1; }

[ -f .env ] || cp .env.example .env
composer install
php artisan key:generate --force
php artisan env:sync --yes
npm install
npm run build
php artisan app:init --force
php artisan app:doctor

echo
echo "Setup complete. Start with: php artisan serve"
echo "Foundation dashboard: http://127.0.0.1:8000/__foundation"
