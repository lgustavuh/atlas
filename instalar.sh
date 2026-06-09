#!/usr/bin/env bash
# ===================================================================
#   Atlas - Instalador automatizado (Linux/Mac)
#   Versão: 3.0 (Maio/2026)
# ===================================================================
#
#   Este script instala o sistema do zero em uma máquina com:
#     - Docker + Docker Compose v2
#     - bash 4+
#
#   Pode ser re-executado com segurança (idempotente).
#
#   Mudanças v3.0:
#     - Cria diretórios de storage privado
#     - Importa cidades IBGE automaticamente
#     - Valida APP_KEY e senhas padrão ao final
#     - Mostra instruções de fila e scheduler para produção
# ===================================================================

set -e  # Para em qualquer erro

# Cores
VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

ok()      { echo -e "  ${VERDE}✓${RESET} $1"; }
aviso()   { echo -e "  ${AMARELO}!${RESET} $1"; }
erro()    { echo -e "  ${VERMELHO}✗${RESET} $1"; }
secao()   { echo -e "\n${AZUL}[$1/$2]${RESET} $3\n"; }

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}   Atlas - Sistema de Gestão - Instalador v3.0${RESET}"
echo -e "${AZUL}================================================================${RESET}"

# ---------- 1. Pré-requisitos ----------
secao 1 9 "Verificando pré-requisitos..."

if ! command -v docker &>/dev/null; then
    erro "Docker não encontrado. Instale: https://docs.docker.com/get-docker/"
    exit 1
fi
ok "$(docker --version)"

if ! docker compose version &>/dev/null; then
    erro "Docker Compose v2 não disponível. Atualize o Docker."
    exit 1
fi
ok "$(docker compose version)"

if ! docker info &>/dev/null; then
    erro "Docker daemon não está rodando. Inicie o Docker e tente novamente."
    exit 1
fi
ok "Docker daemon ativo"

# Espaço em disco (mínimo 3GB)
ESPACO=$(df -BG . | awk 'NR==2 {gsub("G","",$4); print $4}')
if [ "$ESPACO" -lt 3 ]; then
    aviso "Pouco espaço em disco: ${ESPACO}GB livres (recomendado: 3GB+)"
    read -p "  Continuar mesmo assim? (s/N): " continuar
    [ "$continuar" != "s" ] && exit 1
else
    ok "Espaço em disco: ${ESPACO}GB livres"
fi

# ---------- 2. .env ----------
secao 2 9 "Configurando arquivo de ambiente (.env)..."

if [ -f ".env" ]; then
    aviso "Arquivo .env já existe - mantendo o atual"
else
    [ ! -f ".env.example" ] && { erro ".env.example não encontrado"; exit 1; }
    cp .env.example .env

    # Senhas aleatórias seguras (32 chars alfanuméricos)
    DB_PWD=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)
    REDIS_PWD=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32)

    # macOS sed precisa de '' após -i, Linux não
    if [[ "$OSTYPE" == "darwin"* ]]; then
        SED_INPLACE=(-i '')
    else
        SED_INPLACE=(-i)
    fi

    sed "${SED_INPLACE[@]}" "s|DB_PASSWORD=dev_password_change_me|DB_PASSWORD=$DB_PWD|" .env
    sed "${SED_INPLACE[@]}" "s|REDIS_PASSWORD=dev_redis_change_me|REDIS_PASSWORD=$REDIS_PWD|" .env

    # UID/GID corretos (evita problemas de permissão em Linux)
    sed "${SED_INPLACE[@]}" "s|^UID=.*|UID=$(id -u)|" .env
    sed "${SED_INPLACE[@]}" "s|^GID=.*|GID=$(id -g)|" .env

    ok ".env criado com senhas aleatórias"
fi

# ---------- 3. Porta 8000 ----------
secao 3 9 "Verificando porta 8000..."

if command -v lsof &>/dev/null && lsof -i :8000 &>/dev/null; then
    aviso "Porta 8000 em uso"
    read -p "  Continuar? (s/N): " continuar
    [ "$continuar" != "s" ] && exit 1
else
    ok "Porta 8000 disponível"
fi

# ---------- 4. Diretórios de storage ----------
secao 4 9 "Preparando estrutura de storage..."

mkdir -p storage/app/private/{atestados,advertencias,curriculos,biblioteca,fotos}
mkdir -p storage/app/public
mkdir -p storage/framework/{cache/data,sessions,views}
mkdir -p storage/logs

# Permissões para o container poder gravar
chmod -R 775 storage 2>/dev/null || true

ok "Diretórios de storage prontos"

# ---------- 5. Build ----------
secao 5 9 "Construindo imagens Docker (primeira vez demora ~5min)..."

docker compose build
ok "Imagens construídas"

# ---------- 6. Up ----------
secao 6 9 "Iniciando serviços..."

docker compose up -d

echo "  Aguardando serviços ficarem prontos..."
TENTATIVAS=0
until docker compose exec -T postgres pg_isready -U etc_user &>/dev/null; do
    TENTATIVAS=$((TENTATIVAS+1))
    if [ $TENTATIVAS -ge 30 ]; then
        erro "Timeout aguardando PostgreSQL. Veja: docker compose logs postgres"
        exit 1
    fi
    sleep 2
done
ok "PostgreSQL pronto"

if docker compose exec -T redis redis-cli ping &>/dev/null; then
    ok "Redis pronto"
else
    aviso "Redis ainda não respondeu - continuando assim mesmo"
fi

# ---------- 7. Dependências ----------
secao 7 9 "Instalando dependências..."

echo "  Composer..."
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader
ok "Dependências PHP"

echo "  npm..."
docker compose exec -T app npm install --silent
ok "Dependências JS"

# ---------- 8. App ----------
secao 8 9 "Configurando a aplicação..."

if ! grep -q "^APP_KEY=base64:" .env; then
    docker compose exec -T app php artisan key:generate --force >/dev/null
    ok "APP_KEY gerada"
else
    ok "APP_KEY já existe"
fi

docker compose exec -T app php artisan config:clear >/dev/null 2>&1
docker compose exec -T app php artisan cache:clear >/dev/null 2>&1
docker compose exec -T app php artisan view:clear >/dev/null 2>&1
docker compose exec -T app php artisan route:clear >/dev/null 2>&1
ok "Caches limpos"

docker compose exec -T app php artisan migrate --force
ok "Migrations aplicadas"

docker compose exec -T app php artisan db:seed --force || aviso "Seeders podem já ter sido executados"

docker compose exec -T app php artisan storage:link >/dev/null 2>&1 || true
ok "Storage configurado"

# Importa cidades do IBGE (opcional, demora ~30s)
echo "  Importando cidades do IBGE (opcional, demora ~30s)..."
if docker compose exec -T app php artisan etc:importar-cidades-ibge >/dev/null 2>&1; then
    ok "Cidades IBGE importadas"
else
    aviso "Importação de cidades pulada (rode manual depois se quiser)"
fi

# ---------- 9. Assets + validações ----------
secao 9 9 "Compilando assets, gerando caches e validando..."

echo "  Rodando npm run build (pode demorar 1-2min)..."
if docker compose exec -T app npm run build; then
    ok "npm run build executado"
else
    erro "Falha ao compilar assets - LOGIN VAI QUEBRAR sem isso!"
    aviso "Tente manualmente: docker compose exec app npm run build"
fi

# Caches de performance — view e route caching aceleram MUITO o primeiro request
# (em dev, view:cache continua revalidando o blade ao editar; é seguro)
echo "  Gerando cache de views (acelera renderizacao)..."
docker compose exec -T app php artisan view:cache >/dev/null 2>&1 && ok "View cache gerado"

echo "  Gerando cache de rotas..."
docker compose exec -T app php artisan route:cache >/dev/null 2>&1 && ok "Route cache gerado"

echo "  Gerando cache de eventos..."
docker compose exec -T app php artisan event:cache >/dev/null 2>&1 && ok "Event cache gerado"

# Aquecer opcache com primeiro hit
echo "  Aquecendo OPcache (primeiro hit pra preencher cache de bytecode)..."
APP_PORT=$(grep -E "^APP_PORT=" .env | cut -d= -f2)
APP_PORT=${APP_PORT:-8000}
curl -s -o /dev/null "http://localhost:${APP_PORT}/" --max-time 10 || true
curl -s -o /dev/null "http://localhost:${APP_PORT}/" --max-time 10 || true
ok "OPcache aquecido"

# Validação CRÍTICA: sem manifest.json, o Livewire não carrega e o login fica quebrado
if [ -f public/build/manifest.json ]; then
    ok "Manifest do Vite presente (assets compilados)"
else
    erro "ATENCAO: public/build/manifest.json NAO existe!"
    erro "Sem ele, o Livewire/Alpine nao carrega e o LOGIN FICA QUEBRADO."
    aviso "Solucao: docker compose exec app npm run build"
    INSTALACAO_OK=0
fi

# Validações finais
INSTALACAO_OK=${INSTALACAO_OK:-1}
if ! grep -q "^APP_KEY=base64:" .env; then
    erro "APP_KEY não foi gerada corretamente"
    INSTALACAO_OK=0
fi

if grep -q "DB_PASSWORD=dev_password_change_me" .env; then
    aviso "DB_PASSWORD ainda é a default - troque antes de ir pra produção"
fi

if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 --max-time 5 | grep -qE "^(200|302)$"; then
    ok "Site respondendo"
else
    aviso "Site ainda não respondeu (pode levar alguns segundos a mais)"
fi

# ---------- Fim ----------
echo
if [ "$INSTALACAO_OK" -eq 1 ]; then
    echo -e "${VERDE}================================================================${RESET}"
    echo -e "${VERDE}       INSTALAÇÃO CONCLUÍDA COM SUCESSO!${RESET}"
    echo -e "${VERDE}================================================================${RESET}"
else
    echo -e "${AMARELO}================================================================${RESET}"
    echo -e "${AMARELO}   INSTALAÇÃO CONCLUÍDA COM AVISOS - revise os pontos acima${RESET}"
    echo -e "${AMARELO}================================================================${RESET}"
fi
echo
echo -e "  Sistema:  ${AZUL}http://localhost:8000${RESET}"
echo -e "  Mailpit:  ${AZUL}http://localhost:8025${RESET}"
echo
echo "  Credenciais de admin:"
echo "    Email: admin@atlas.local"
echo "    Senha: Admin@123456"
echo
echo -e "  ${AMARELO}IMPORTANTE:${RESET} Troque a senha do admin no primeiro acesso."
echo
echo "----------------------------------------------------------------"
echo "  make up      - Sobe os serviços"
echo "  make down    - Para os serviços"
echo "  make logs    - Logs em tempo real"
echo "  make test    - Roda os testes"
echo "----------------------------------------------------------------"
echo
echo "  Para produção:"
echo "    - Configure HTTPS (SESSION_SECURE_COOKIE=true)"
echo "    - Defina QUEUE_CONNECTION=redis no .env"
echo "    - Habilite worker da fila:"
echo "        docker compose exec -d app php artisan queue:work"
echo "    - Habilite o scheduler (notificações de vencimento):"
echo "        docker compose exec -d app php artisan schedule:work"
echo "    - Rode: php artisan config:cache route:cache view:cache"
echo "----------------------------------------------------------------"
