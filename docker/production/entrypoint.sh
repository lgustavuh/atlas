#!/bin/sh
# ===================================================================
#   Atlas - Entrypoint do container de producao
#   Ordem: migrations -> seed -> caches -> chown -> nginx (PID 1)
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

# 3. Migrations (so se DB_RUN_MIGRATIONS=true)
if [ "${DB_RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Rodando migrations..."
    php artisan migrate --force --no-interaction
fi

# 4. Seed inicial (so se SEED_DATABASE=true)
# O '|| true' garante que o script continua mesmo se algum seeder falhar
# (ex: tentar inserir dado ja existente apos um deploy anterior)
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    echo "==> Rodando seeders..."
    php artisan db:seed --force --no-interaction || echo "==> AVISO: seed parcial (dados podem ja existir)"
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

# 6. CRITICO: ajustar permissoes do storage/cache
# O entrypoint roda como root, mas o php-fpm roda como www-data.
# Sem esse chown, php-fpm nao consegue escrever caches novos em runtime
# (sintoma: rota /up retorna 500 com "Permission denied" em storage/framework/views).
echo "==> Ajustando permissoes do storage e bootstrap/cache..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# 7. Validar config do nginx
echo "==> Validando configuracao do nginx..."
nginx -t

# 8. php-fpm em background
echo "==> Iniciando php-fpm em background..."
php-fpm --daemonize

# Aguarda php-fpm aceitar conexoes
for i in 1 2 3 4 5 6 7 8 9 10; do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        echo "==> php-fpm pronto (porta 9000)"
        break
    fi
    sleep 1
done

# 9. Nginx em foreground como PID 1
echo "==> Atlas online em :$PORT - iniciando nginx (PID 1)"
exec nginx -g 'daemon off;'
