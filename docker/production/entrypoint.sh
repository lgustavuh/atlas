#!/bin/sh
# ===================================================================
#   Atlas - Entrypoint do container de producao
# ===================================================================

set -e

PORT="${PORT:-8080}"
echo "==> Atlas iniciando na porta $PORT"

# 1. Injeta a porta no nginx
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf

# 2. APP_KEY obrigatoria
if [ -z "${APP_KEY:-}" ]; then
    echo "==> AVISO: APP_KEY nao definida. Gerando uma temporaria..."
    cd /var/www
    APP_KEY=$(php artisan key:generate --show)
    export APP_KEY
fi

cd /var/www

# 3. Migrations PRIMEIRO
if [ "${DB_RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Rodando migrations..."
    php artisan migrate --force --no-interaction
fi

# 4. Seed inicial
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    echo "==> Rodando seeders..."
    php artisan db:seed --force --no-interaction || echo "==> AVISO: seed parcial"
fi

# 5. Caches Laravel
echo "==> Limpando caches antigos..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

echo "==> Gerando caches otimizados..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 6. Testar nginx config antes de subir
echo "==> Validando configuracao do nginx..."
nginx -t

# 7. Subir php-fpm em background
echo "==> Iniciando php-fpm (background)..."
php-fpm --daemonize

# Aguarda php-fpm aceitar conexoes (max 10s)
for i in 1 2 3 4 5 6 7 8 9 10; do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        echo "==> php-fpm pronto (porta 9000)"
        break
    fi
    sleep 1
done

# 8. Nginx em foreground como PID 1 (recebe sinais do Railway)
echo "==> Iniciando nginx (foreground) - container PID 1"
echo "==> Atlas online em :$PORT"
exec nginx -g 'daemon off;'
