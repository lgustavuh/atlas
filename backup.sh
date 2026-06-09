#!/usr/bin/env bash
# ===================================================================
#   Atlas - Backup (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
#
#   Uso:
#     ./backup.sh             - Backup completo (banco + arquivos + .env)
#     ./backup.sh --db        - So o banco PostgreSQL
#     ./backup.sh --arquivos  - So os arquivos do storage
#     ./backup.sh --listar    - Lista backups existentes
#     ./backup.sh --limpar    - Remove backups > 30 dias
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

mkdir -p backups logs-instalacao

# Detecta credenciais do banco
DB_NAME=$(grep -E "^DB_DATABASE=" .env 2>/dev/null | cut -d= -f2)
DB_USER=$(grep -E "^DB_USERNAME=" .env 2>/dev/null | cut -d= -f2)
DB_NAME=${DB_NAME:-atlas}
DB_USER=${DB_USER:-etc_user}

# --- Modo: listar ---
if [ "${1:-}" = "--listar" ]; then
    echo
    echo -e "${AZUL}================================================================${RESET}"
    echo -e "${AZUL}    Backups disponiveis${RESET}"
    echo -e "${AZUL}================================================================${RESET}"
    echo

    arquivos=$(ls -1t backups/*.tar.gz backups/*.zip backups/*.sql 2>/dev/null)
    if [ -z "$arquivos" ]; then
        echo -e "  ${AMARELO}!${RESET} Nenhum backup encontrado."
        echo
        echo "  Crie o primeiro com: ./backup.sh"
        exit 0
    fi

    printf "  %-12s %-10s %-20s %s\n" "TIPO" "TAMANHO" "DATA" "ARQUIVO"
    printf "  %-12s %-10s %-20s %s\n" "------------" "----------" "--------------------" "----------------------"
    for f in $arquivos; do
        nome=$(basename "$f")
        tamanho=$(du -h "$f" | cut -f1)
        data=$(date -r "$f" '+%Y-%m-%d %H:%M:%S' 2>/dev/null || stat -c %y "$f" | cut -d. -f1)
        tipo="Completo"
        case "$nome" in
            db-only_*)        tipo="Banco" ;;
            arquivos_*)       tipo="Arquivos" ;;
            pre-deploy_*)     tipo="Pre-deploy" ;;
            snapshot-pre-*)   tipo="Snapshot" ;;
        esac
        printf "  %-12s %-10s %-20s %s\n" "$tipo" "$tamanho" "$data" "$nome"
    done
    echo
    exit 0
fi

# --- Modo: limpar ---
if [ "${1:-}" = "--limpar" ]; then
    echo
    echo -e "${AZUL}================================================================${RESET}"
    echo -e "${AZUL}    Limpar backups antigos${RESET}"
    echo -e "${AZUL}================================================================${RESET}"
    echo
    read -p "  Manter backups dos ultimos quantos dias? (default 30): " dias
    dias=${dias:-30}

    echo
    echo "  Removendo backups com mais de $dias dias..."
    removidos=0
    while IFS= read -r f; do
        echo "    Removendo $(basename "$f")"
        rm -f "$f"
        removidos=$((removidos+1))
    done < <(find backups -maxdepth 1 \( -name "*.tar.gz" -o -name "*.zip" -o -name "*.sql" \) -mtime "+$dias" 2>/dev/null)

    echo
    if [ $removidos -eq 0 ]; then
        echo -e "  ${VERDE}✓${RESET} Nenhum backup antigo encontrado"
    else
        echo -e "  ${VERDE}✓${RESET} $removidos backup(s) removido(s)"
    fi
    exit 0
fi

# --- Pre-requisito: PostgreSQL rodando ---
if ! docker info &>/dev/null; then
    echo -e "  ${VERMELHO}✗${RESET} Docker nao esta rodando."
    exit 1
fi

if ! docker compose ps --services --status=running 2>/dev/null | grep -q "^postgres$"; then
    echo -e "  ${VERMELHO}✗${RESET} Container PostgreSQL nao esta rodando."
    echo "    Inicie com: ./iniciar.sh"
    exit 1
fi

TIMESTAMP=$(date +%Y-%m-%d_%H%M%S)

# --- Modo: somente banco ---
if [ "${1:-}" = "--db" ]; then
    clear
    echo -e "${AZUL}================================================================${RESET}"
    echo -e "${AZUL}    Backup somente do Banco${RESET}"
    echo -e "${AZUL}================================================================${RESET}"
    echo

    BACKUP_DB="backups/db-only_${TIMESTAMP}.sql"
    echo "  Salvando em: $BACKUP_DB"

    if docker compose exec -T postgres pg_dump -U "$DB_USER" -d "$DB_NAME" \
        --clean --if-exists --no-owner --no-privileges > "$BACKUP_DB"; then
        TAMANHO=$(du -h "$BACKUP_DB" | cut -f1)
        echo
        echo -e "  ${VERDE}✓${RESET} Backup criado: $BACKUP_DB ($TAMANHO)"
    else
        echo -e "  ${VERMELHO}✗${RESET} Falha no pg_dump"
        exit 1
    fi
    exit 0
fi

# --- Modo: somente arquivos ---
if [ "${1:-}" = "--arquivos" ]; then
    clear
    echo -e "${AZUL}================================================================${RESET}"
    echo -e "${AZUL}    Backup somente dos Arquivos${RESET}"
    echo -e "${AZUL}================================================================${RESET}"
    echo

    if [ ! -d storage/app ]; then
        echo -e "  ${VERMELHO}✗${RESET} Pasta storage/app nao existe"
        exit 1
    fi

    BACKUP_FILES="backups/arquivos_${TIMESTAMP}.tar.gz"
    tar -czf "$BACKUP_FILES" -C storage app
    TAMANHO=$(du -h "$BACKUP_FILES" | cut -f1)
    echo -e "  ${VERDE}✓${RESET} Backup criado: $BACKUP_FILES ($TAMANHO)"
    exit 0
fi

# --- Modo: backup completo (padrao) ---
clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}    Atlas - Backup Completo${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo

BACKUP_NAME="atlas-backup_${TIMESTAMP}"
BACKUP_TEMP="backups/_temp_${TIMESTAMP}"
BACKUP_FINAL="backups/${BACKUP_NAME}.tar.gz"
LOG="logs-instalacao/backup_${TIMESTAMP}.log"

echo -e "  Backup: ${AZUL}${BACKUP_FINAL}${RESET}"
echo -e "  Log:    ${AZUL}${LOG}${RESET}"
echo

mkdir -p "$BACKUP_TEMP"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] === Inicio do backup ===" > "$LOG"

# 1. Dump do banco
echo -e "${AZUL}[1/5]${RESET} Dump do PostgreSQL..."
if docker compose exec -T postgres pg_dump -U "$DB_USER" -d "$DB_NAME" \
    --clean --if-exists --no-owner --no-privileges > "$BACKUP_TEMP/database.sql" 2>>"$LOG"; then
    DB_SIZE=$(du -h "$BACKUP_TEMP/database.sql" | cut -f1)
    echo -e "    ${VERDE}✓${RESET} database.sql ($DB_SIZE)"
    echo "[OK] Dump: $DB_SIZE" >> "$LOG"
else
    echo -e "    ${VERMELHO}✗${RESET} Falha no pg_dump"
    rm -rf "$BACKUP_TEMP"
    exit 1
fi

# 2. Arquivos privados
echo -e "${AZUL}[2/5]${RESET} Arquivos privados do storage..."
if [ -d storage/app/private ]; then
    cp -r storage/app/private "$BACKUP_TEMP/storage-private"
    ARQUIVOS=$(find "$BACKUP_TEMP/storage-private" -type f | wc -l)
    echo -e "    ${VERDE}✓${RESET} $ARQUIVOS arquivo(s) privado(s)"
    echo "[OK] $ARQUIVOS arquivos privados" >> "$LOG"
else
    echo -e "    ${AMARELO}!${RESET} storage/app/private nao existe ainda"
fi

# 3. Arquivos publicos
echo -e "${AZUL}[3/5]${RESET} Arquivos publicos do storage..."
if [ -d storage/app/public ]; then
    cp -r storage/app/public "$BACKUP_TEMP/storage-public"
    ARQUIVOS=$(find "$BACKUP_TEMP/storage-public" -type f | wc -l)
    echo -e "    ${VERDE}✓${RESET} $ARQUIVOS arquivo(s) publico(s)"
else
    echo -e "    ${AMARELO}!${RESET} storage/app/public nao existe ainda"
fi

# 4. .env
echo -e "${AZUL}[4/5]${RESET} Configuracao (.env)..."
if [ -f .env ]; then
    cp .env "$BACKUP_TEMP/.env.backup"
    echo -e "    ${VERDE}✓${RESET} .env salvo (contem senhas - mantenha seguro!)"
else
    echo -e "    ${AMARELO}!${RESET} .env nao encontrado"
fi

# LEIA-ME dentro do backup
cat > "$BACKUP_TEMP/LEIA-ME.md" << EOF
# Atlas - Backup

**Data do backup:** $(date '+%Y-%m-%d %H:%M:%S')
**Versao do sistema:** v1.3+

## Conteudo

- \`database.sql\` - Dump completo do PostgreSQL (formato plain SQL)
- \`storage-private/\` - Atestados, advertencias, curriculos, biblioteca, fotos
- \`storage-public/\` - Arquivos publicos
- \`.env.backup\` - Configuracoes (CONTEM SENHAS - manter seguro!)

## Como restaurar

Use o script \`./restaurar.sh ${BACKUP_NAME}.tar.gz\`

Ou manualmente:
1. \`./parar.sh\`
2. Copie \`.env.backup\` para \`.env\` na raiz
3. \`./iniciar.sh\`
4. Restaure o banco:
   \`docker compose exec -T postgres psql -U etc_user atlas < database.sql\`
5. Restaure arquivos: copie \`storage-private\` e \`storage-public\` para \`storage/app/\`
6. Limpe caches: \`docker compose exec app php artisan cache:clear\`
EOF

# 5. Empacotar
echo -e "${AZUL}[5/5]${RESET} Compactando em tar.gz..."
if tar -czf "$BACKUP_FINAL" -C "$BACKUP_TEMP" .; then
    rm -rf "$BACKUP_TEMP"
    FINAL_SIZE=$(du -h "$BACKUP_FINAL" | cut -f1)
    echo -e "    ${VERDE}✓${RESET} Backup criado: $FINAL_SIZE"
    echo "[OK] Backup final: $FINAL_SIZE" >> "$LOG"
else
    echo -e "    ${VERMELHO}✗${RESET} Falha ao criar tar.gz"
    exit 1
fi

# Retencao automatica (30 dias)
echo
echo -e "  ${AZUL}Aplicando retencao automatica (30 dias)...${RESET}"
removidos=0
while IFS= read -r f; do
    rm -f "$f"
    removidos=$((removidos+1))
done < <(find backups -maxdepth 1 -name "atlas-backup_*.tar.gz" -mtime +30 2>/dev/null)

if [ $removidos -eq 0 ]; then
    echo -e "    ${VERDE}✓${RESET} Nenhum backup antigo a remover"
else
    echo -e "    ${VERDE}✓${RESET} $removidos backup(s) antigo(s) removido(s)"
fi

echo
echo -e "${VERDE}================================================================${RESET}"
echo -e "${VERDE}    BACKUP CONCLUIDO!${RESET}"
echo -e "${VERDE}================================================================${RESET}"
echo
echo -e "  Arquivo: ${AZUL}${BACKUP_FINAL}${RESET}"
echo -e "  Log:     ${AZUL}${LOG}${RESET}"
echo
echo -e "  ${AMARELO}IMPORTANTE:${RESET} mantenha em local seguro (disco externo, Drive, NAS)."
echo "  Ele contem o .env com senhas."
echo
echo "  Para listar: ./backup.sh --listar"
echo "  Para restaurar: ./restaurar.sh ${BACKUP_NAME}.tar.gz"
echo
