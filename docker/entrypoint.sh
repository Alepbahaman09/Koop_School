#!/bin/sh

# Set default port to 80 if not provided by Render
PORT="${PORT:-80}"

echo "Configuring Apache port to listen on ${PORT}..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Run database migrations if configured
if [ "${SKIP_MIGRATIONS:-false}" != "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

# Cache Laravel configuration, routes, and views for speed optimization
echo "Optimizing Laravel configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the main process (usually apache2-foreground)
exec "$@"
