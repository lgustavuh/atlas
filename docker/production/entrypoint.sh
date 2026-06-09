#!/bin/sh
# ===================================================================
#   Atlas - Entrypoint do container de producao
#   Ordem: migrations -> seed -> caches -> supervisor
# ===================================================================

set -e

PORT="${PORT:-8080}"
echo "==> Atlas iniciando na porta $PORT"

# 1. Injeta a porta no nginx
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf

# 2. APP_KEY obrigatoria
if [ -z "${APP_KEY:-}" ]; then
    echo "==> AVISO: APP_KEY nao definida. Gerando uma temporaria..."
    echo "==> IMPORTANTE: defina APP_KEY fixa em producao."
    cd /var/www
    APP_KEY=$(php artisan key:generate --show)
    export APP_KEY
fi

cd /var/www

# 3. MIGRATIONS PRIMEIRO (cria tabelas cache/sessions/jobs antes de qualquer artisan
#    que dependa delas)
if [ "${DB_RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Rodando migrations..."
    php artisan migrate --force --no-interaction
fi

# 4. Seed inicial (somente se SEED_DATABASE=true)
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    echo "==> Rodando seeders (admin + perfis + geografia)..."
    php artisan db:seed --force --no-interaction || echo "==> AVISO: seed parcial (alguns dados ja existem)"
fi

# 5. Caches Laravel - so AGORA que as tabelas existem
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

# 6. Tudo pronto - sobe supervisord (que sobe nginx + php-fpm)
echo "==> Tudo pronto. Iniciando nginx + php-fpm..."
