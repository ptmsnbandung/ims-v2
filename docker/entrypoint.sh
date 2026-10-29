#!/bin/sh
set -e

# Setup storage & cache directory structure
mkdir -p /var/www/ims2/storage/framework/cache/data \
         /var/www/ims2/storage/framework/sessions \
         /var/www/ims2/storage/framework/views \
         /var/www/ims2/storage/logs \
         /var/www/ims2/bootstrap/cache

chown -R www-data:www-data /var/www/ims2/storage /var/www/ims2/bootstrap/cache
chmod -R 775 /var/www/ims2/storage /var/www/ims2/bootstrap/cache

# Ensure upload directory exists and is writable
mkdir -p /var/www/ims2/public/uploads/registrasi
chmod -R 777 /var/www/ims2/public/uploads || true

# Ensure storage symlink exists
php artisan storage:link || true

# Setup Laravel crontab if not already set
if ! grep -q "schedule:run" /etc/crontabs/root 2>/dev/null; then
    echo "* * * * * cd /var/www/ims2 && php artisan schedule:run >> /dev/null 2>&1" >> /etc/crontabs/root
fi

# Ensure olt-collector is executable
if [ -f /var/www/ims2/services/olt-collector/olt-collector ]; then
    chmod +x /var/www/ims2/services/olt-collector/olt-collector
fi

# Run database migrations
php artisan migrate --force || true

# Cache config & route if APP_KEY exists
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "[IMS Docker] Starting Supervisord (Nginx + PHP-FPM + Cron + OLT Collector)..."
exec "$@"
