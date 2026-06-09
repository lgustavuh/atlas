#!/usr/bin/env bash
# ===================================================================
#   Atlas - Atualizar (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

mkdir -p logs-instalacao
TIMESTAMP=$(date +%Y-%m-%d_%H%M%S)
LOG="logs-instalacao/update_${TIMESTAMP}.log"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] === Inicio do update ===" > "$LOG"

ok()    { echo -e "  ${VERDE}✓${RESET} $1"; echo "[$(date '+%H:%M:%S')] [OK] $1" >> "$LOG"; }
aviso() { echo -e "  ${AMARELO}!${RESET} $1"; echo "[$(date '+%H:%M:%S')] [AVISO] $1" >> "$LOG"; }
erro()  { echo -e "  ${VERMELHO}✗${RESET} $1"; echo "[$(date '+%H:%M:%S')] [ERRO] $1" >> "$LOG"; }
secao() { echo; echo -e "${AZUL}[$1]${RESET} $2"; echo; echo "[$(date '+%H:%M:%S')] [SECAO $1] $2" >> "$LOG"; }

trap 'echo "[$(date "+%Y-%m-%d %H:%M:%S")] === Update falhou ===" >> "$LOG"' ERR

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}    Atlas - Atualizar${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo
echo -e "  Log: ${AZUL}${LOG}${RESET}"
echo

# Containers rodando?
if ! docker compose ps --services --status=running 2>/dev/null | grep -q "^app$"; then
    aviso "Containers parados. Subindo primeiro..."
    if ! docker compose up -d >> "$LOG" 2>&1; then
        erro "Falha ao subir containers. Veja $LOG"
        exit 1
    fi
    echo "  Aguardando inicializacao..."
    sleep 5
fi

secao "1/6" "Atualizando dependencias PHP..."
if docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader >> "$LOG" 2>&1; then
    ok "Composer atualizado"
else
    erro "Falha no Composer. Veja $LOG"
    exit 1
fi

secao "2/6" "Atualizando dependencias JS..."
if docker compose exec -T app npm install --silent >> "$LOG" 2>&1; then
    ok "npm atualizado"
else
    erro "Falha no npm. Veja $LOG"
    exit 1
fi

secao "3/6" "Aplicando migrations..."
if docker compose exec -T app php artisan migrate --force >> "$LOG" 2>&1; then
    ok "Migrations aplicadas"
else
    erro "Falha nas migrations. Veja $LOG"
    exit 1
fi

secao "4/6" "Reiniciando workers da fila..."
docker compose exec -T app php artisan queue:restart >> "$LOG" 2>&1 || true
ok "Workers serao reciclados"

secao "5/6" "Limpando e recriando caches..."
docker compose exec -T app php artisan config:clear >> "$LOG" 2>&1 || true
docker compose exec -T app php artisan cache:clear >> "$LOG" 2>&1 || true
docker compose exec -T app php artisan view:clear >> "$LOG" 2>&1 || true
docker compose exec -T app php artisan route:clear >> "$LOG" 2>&1 || true

# IMPORTANTE: reiniciar o container app invalida o OPcache, garantindo que mudancas
# em middleware/services entrem em vigor mesmo com validate_timestamps=60s
echo "  Recarregando PHP-FPM (invalida OPcache, faz mudancas refletirem agora)..."
docker compose restart app >> "$LOG" 2>&1
sleep 3
ok "PHP-FPM recarregado"

if grep -q "^APP_ENV=production" .env 2>/dev/null; then
    echo "  Detectado APP_ENV=production - regenerando caches otimizados..."
    docker compose exec -T app php artisan config:cache >> "$LOG" 2>&1
    docker compose exec -T app php artisan route:cache >> "$LOG" 2>&1
    docker compose exec -T app php artisan view:cache >> "$LOG" 2>&1
    docker compose exec -T app php artisan event:cache >> "$LOG" 2>&1
    ok "Caches de producao gerados"
else
    ok "Caches limpos (dev mode)"
fi

secao "6/6" "Recompilando assets..."
if docker compose exec -T app npm run build >> "$LOG" 2>&1; then
    ok "Assets recompilados"
    if [ -f public/build/manifest.json ]; then
        ok "manifest.json presente"
    else
        erro "manifest.json AUSENTE - login pode falhar"
    fi
else
    erro "Falha ao compilar assets - login pode parar de funcionar"
fi

echo
echo -e "${VERDE}================================================================${RESET}"
echo -e "${VERDE}    ATUALIZACAO CONCLUIDA${RESET}"
echo -e "${VERDE}================================================================${RESET}"
echo
APP_PORT=$(grep -E "^APP_PORT=" .env 2>/dev/null | cut -d= -f2)
echo -e "  Sistema: ${AZUL}http://localhost:${APP_PORT:-8000}${RESET}"
echo -e "  Log:     ${AZUL}${LOG}${RESET}"
echo

echo "[$(date '+%Y-%m-%d %H:%M:%S')] === Update concluido ===" >> "$LOG"
