#!/bin/bash
set -e

# Install composer dependencies if vendor directory doesn't exist
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Run Yii init if not already initialized (check for main-local.php)
if [ ! -f "/var/www/html/common/config/main-local.php" ]; then
    echo "Initializing Yii2 application (JadalDevelopment)..."
    php init --env=JadalDevelopment --overwrite=All
fi

# Ensure writable directories exist and have correct permissions
mkdir -p /var/www/html/backend/runtime
mkdir -p /var/www/html/backend/web/assets
mkdir -p /var/www/html/console/runtime
mkdir -p /var/www/html/frontend/runtime
mkdir -p /var/www/html/frontend/web/assets

chmod -R 777 /var/www/html/backend/runtime
chmod -R 777 /var/www/html/backend/web/assets
chmod -R 777 /var/www/html/console/runtime
chmod -R 777 /var/www/html/frontend/runtime
chmod -R 777 /var/www/html/frontend/web/assets

# Create/update database views for optimized reports
echo "Creating database views..."
for i in $(seq 1 10); do
    if mysql -h mysql -u root -prootpassword namaa_jadal < /var/www/html/docker/views.sql 2>/dev/null; then
        echo "Database views created successfully."
        break
    fi
    echo "Waiting for MySQL... attempt $i"
    sleep 3
done

echo "Starting Apache..."
exec apache2-foreground
