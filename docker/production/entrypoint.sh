#!/bin/sh
# ===================================================================
#   Atlas - Entrypoint do container de producao
#
#   Roda na ordem:
#     1. Substitui __PORT__ no nginx.conf pela var $PORT (Railway injeta)
#     2. Garante APP_KEY (gera se nao existir)
#     3. Roda migrations (se DB_RUN_MIGRATIONS=true)
#     4. Cacheia config/route/view/event
#     5. Starta supervisord (que sobe nginx + php-fpm)
# ===================================================================

set -e

PORT="${PORT:-8080}"
echo "==> Atlas iniciando na porta $PORT"

# 1. Injeta a porta no nginx
sed -i "s/__PORT__/$PORT/g" /etc/nginx/nginx.conf

# 2. APP_KEY obrigatoria. Se nao existir, gera (e avisa - melhor setar fixa via env var)
if [ -z "${APP_KEY:-}" ]; then
    echo "==> AVISO: APP_KEY nao definida. Gerando uma temporaria..."
    echo "==> IMPORTANTE: Em producao, defina APP_KEY como variavel de ambiente fixa."
    echo "==>             Se reiniciar o container, sessoes existentes sao invalidadas."
    cd /var/www
    php artisan key:generate --show > /tmp/app_key.txt
    APP_KEY=$(cat /tmp/app_key.txt | tr -d '\n')
    export APP_KEY
fi

cd /var/www

# 3. Caches Laravel — limpa antes de gerar (caso o codigo tenha mudado)
echo "==> Limpando caches..."
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true
php artisan route:clear || true

echo "==> Gerando caches otimizados..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Migrations (controlado por variavel pra evitar rodar duas vezes em multi-replicas)
if [ "${DB_RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Rodando migrations..."
    php artisan migrate --force --no-interaction
fi

# 5. Seed inicial (somente se SEED_DATABASE=true; default false em producao)
if [ "${SEED_DATABASE:-false}" = "true" ]; then
    echo "==> Rodando seeders (admin + perfis + geografia)..."
    php artisan db:seed --force --no-interaction || echo "==> AVISO: seed falhou (talvez ja exista)"
fi

# 6. Aquece OPcache fazendo um GET interno enquanto sobe (idempotente)
echo "==> Tudo pronto. Iniciando nginx + php-fpm..."

# 7. Starta supervisord como PID 1 (recebe sinais do Railway)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
