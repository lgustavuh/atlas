#!/usr/bin/env bash
# ===================================================================
#   Atlas - Deploy de produção (Linux)
#   Versão: 3.0 (Maio/2026)
# ===================================================================
#
#   Use este script DEPOIS de:
#     - Provisionar o servidor (Docker instalado)
#     - Clonar o projeto em /opt/atlas (ou outro caminho)
#     - Configurar .env de produção (APP_ENV=production, APP_DEBUG=false)
#     - Configurar HTTPS no Nginx do host (frente ao container)
#
#   O que este script faz:
#     1. Verifica que .env está pronto para produção
#     2. Faz backup do banco
#     3. Atualiza dependências (composer --no-dev)
#     4. Roda migrations
#     5. Compila assets
#     6. Gera cache de produção (config, route, view, event)
#     7. Restart de workers, scheduler
#     8. Health check final
#
#   Idempotente — pode rodar várias vezes seguidas.
# ===================================================================

set -e
set -u

# Cores
VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

ok()    { echo -e "  ${VERDE}✓${RESET} $1"; }
aviso() { echo -e "  ${AMARELO}!${RESET} $1"; }
erro()  { echo -e "  ${VERMELHO}✗${RESET} $1" >&2; }
secao() { echo -e "\n${AZUL}[$1/$2]${RESET} $3\n"; }

# ----------------------------------------------------------
# Sanity checks iniciais
# ----------------------------------------------------------

[ -f .env ] || { erro ".env não encontrado. Crie a partir de .env.example primeiro."; exit 1; }
[ -f docker-compose.yml ] || { erro "docker-compose.yml não encontrado."; exit 1; }

# Confirma que .env está em modo produção
if ! grep -q "^APP_ENV=production" .env; then
    erro "APP_ENV não está em 'production'. Edite o .env antes de fazer deploy."
    aviso "Para deploy de staging/dev, use 'make update' em vez deste script."
    exit 1
fi

if grep -q "^APP_DEBUG=true" .env; then
    erro "APP_DEBUG=true em produção! Mude para false antes de continuar."
    exit 1
fi

# Senha default ainda no .env?
if grep -q "DB_PASSWORD=dev_password_change_me" .env; then
    erro "DB_PASSWORD ainda é a default de desenvolvimento. Troque antes de continuar."
    exit 1
fi

# APP_KEY presente?
if ! grep -q "^APP_KEY=base64:" .env; then
    erro "APP_KEY não está definida. Rode 'php artisan key:generate' primeiro."
    exit 1
fi

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}   Atlas - Deploy de PRODUÇÃO${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo
echo "  Repositório: $(pwd)"
echo "  Hostname:    $(hostname)"
echo "  Data:        $(date)"
echo

read -p "Confirma deploy em PRODUÇÃO? (s/N): " confirma
[ "$confirma" != "s" ] && { echo "Cancelado."; exit 0; }

# ----------------------------------------------------------
# 1. Containers rodando?
# ----------------------------------------------------------
secao 1 8 "Verificando containers..."

if ! docker compose ps --services --status=running | grep -q "app"; then
    aviso "Container 'app' não está rodando. Subindo..."
    docker compose up -d
    sleep 5
fi
ok "Containers ativos"

# ----------------------------------------------------------
# 2. Backup automático
# ----------------------------------------------------------
secao 2 8 "Backup do banco antes do deploy..."

mkdir -p backups
BACKUP_FILE="backups/pre-deploy_$(date +%Y-%m-%d_%H%M%S).sql"
docker compose exec -T postgres pg_dump -U etc_user atlas > "$BACKUP_FILE"
ok "Backup salvo: $BACKUP_FILE ($(du -h "$BACKUP_FILE" | cut -f1))"

# Limpa backups antigos (mantém últimos 30 dias)
find backups -name "*.sql" -mtime +30 -delete 2>/dev/null || true

# ----------------------------------------------------------
# 3. Modo manutenção (opcional, configurável)
# ----------------------------------------------------------
secao 3 8 "Entrando em modo de manutenção..."

docker compose exec -T app php artisan down \
    --render="errors::503" \
    --secret="$(openssl rand -hex 16)" \
    --refresh=10 2>/dev/null || aviso "down já estava ativo"
ok "Sistema em manutenção"

# Trap pra garantir que sai do modo manutenção mesmo em erro
trap 'docker compose exec -T app php artisan up 2>/dev/null || true' EXIT

# ----------------------------------------------------------
# 4. Dependências (sem dev em produção)
# ----------------------------------------------------------
secao 4 8 "Atualizando dependências..."

echo "  Composer (--no-dev)..."
docker compose exec -T app composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev
ok "Composer"

echo "  npm..."
docker compose exec -T app npm ci --silent --no-audit --no-fund
ok "npm"

# ----------------------------------------------------------
# 5. Migrations
# ----------------------------------------------------------
secao 5 8 "Aplicando migrations..."

docker compose exec -T app php artisan migrate --force
ok "Migrations aplicadas"

# ----------------------------------------------------------
# 6. Assets
# ----------------------------------------------------------
secao 6 8 "Compilando assets de produção..."

docker compose exec -T app npm run build
ok "Assets compilados"

# ----------------------------------------------------------
# 7. Cache otimizado
# ----------------------------------------------------------
secao 7 8 "Gerando caches de produção..."

# Limpa primeiro pra garantir
docker compose exec -T app php artisan config:clear >/dev/null
docker compose exec -T app php artisan cache:clear >/dev/null
docker compose exec -T app php artisan view:clear >/dev/null
docker compose exec -T app php artisan route:clear >/dev/null

# Regenera otimizado
docker compose exec -T app php artisan config:cache >/dev/null
docker compose exec -T app php artisan route:cache >/dev/null
docker compose exec -T app php artisan view:cache >/dev/null
docker compose exec -T app php artisan event:cache >/dev/null
ok "Caches otimizados"

# Storage link (idempotente)
docker compose exec -T app php artisan storage:link >/dev/null 2>&1 || true
ok "Storage link"

# Recicla workers (carregam o novo código)
docker compose exec -T app php artisan queue:restart >/dev/null
ok "Workers reiniciados"

# Sai do modo manutenção
docker compose exec -T app php artisan up >/dev/null
ok "Sistema fora do modo manutenção"

# Remove o trap — chegamos no fim sem erro
trap - EXIT

# ----------------------------------------------------------
# 8. Health check final
# ----------------------------------------------------------
secao 8 8 "Verificando saúde do sistema..."

# Esperar app responder
TENTATIVAS=0
SUCESSO=0
while [ $TENTATIVAS -lt 10 ]; do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/up --max-time 3 || echo "000")
    if [ "$CODE" = "200" ]; then
        SUCESSO=1
        break
    fi
    TENTATIVAS=$((TENTATIVAS+1))
    sleep 2
done

if [ $SUCESSO -eq 1 ]; then
    ok "Health endpoint respondeu 200 OK"
else
    erro "Health endpoint não respondeu corretamente após 20s"
    aviso "Verifique: docker compose logs app"
    exit 1
fi

# PostgreSQL OK?
if docker compose exec -T postgres pg_isready -U etc_user >/dev/null 2>&1; then
    ok "PostgreSQL respondendo"
else
    erro "PostgreSQL não está respondendo!"
    exit 1
fi

# Workers de fila estão configurados?
if docker compose exec -T app pgrep -f "queue:work" >/dev/null 2>&1; then
    ok "Worker de fila ativo"
else
    aviso "Nenhum worker de fila rodando. Inicie com:"
    aviso "  docker compose exec -d app php artisan queue:work"
fi

# Scheduler?
if docker compose exec -T app pgrep -f "schedule:work" >/dev/null 2>&1; then
    ok "Scheduler ativo"
else
    aviso "Scheduler não está rodando. Para notificações de vencimento, inicie:"
    aviso "  docker compose exec -d app php artisan schedule:work"
fi

# ----------------------------------------------------------
# Sucesso
# ----------------------------------------------------------
echo
echo -e "${VERDE}================================================================${RESET}"
echo -e "${VERDE}       DEPLOY CONCLUÍDO COM SUCESSO!${RESET}"
echo -e "${VERDE}================================================================${RESET}"
echo
echo "  Backup pré-deploy: $BACKUP_FILE"
echo "  Sistema:           http://localhost:8000 (atrás do Nginx HTTPS do host)"
echo
echo "  Em caso de problema crítico, rollback:"
echo "    docker compose exec -T postgres psql -U etc_user atlas < $BACKUP_FILE"
echo
