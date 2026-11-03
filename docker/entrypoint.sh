#!/bin/sh
# Prepara Laravel al iniciar el contenedor: espera a MySQL, crea las tablas y los datos de prueba.
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi
if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
fi

echo "Esperando a MySQL en $DB_HOST:$DB_PORT ..."
until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done

# Solo la primera vez (tabla users vacía) se cargan los datos de demostración.
php artisan migrate --force
if [ "$(php artisan tinker --execute='echo \App\Models\User::count();')" = "0" ]; then
    php artisan db:seed --force
fi
php artisan storage:link 2>/dev/null || true

exec "$@"
