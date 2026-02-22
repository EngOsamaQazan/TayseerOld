#!/bin/bash
# Deploy script for jadal + namaa
set -e

echo "=== Deploying jadal.aqssat.co ==="
cd /var/www/jadal.aqssat.co
git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
php yii migrate --interactive=0
echo "=== jadal DONE ==="

echo ""
echo "=== Deploying namaa.aqssat.co ==="
cd /var/www/namaa.aqssat.co
git reset --hard origin/main
composer install --no-dev --optimize-autoloader --no-interaction
php yii migrate --interactive=0
echo "=== namaa DONE ==="

echo ""
echo "=== ALL DONE ==="
