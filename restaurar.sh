#!/usr/bin/env bash
# ===================================================================
#   Atlas - Restaurar Backup (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
#
#   Uso:
#     ./restaurar.sh ARQUIVO.tar.gz   - Restaura backup completo
#     ./restaurar.sh ARQUIVO.sql      - Restaura somente dump SQL
#     ./restaurar.sh                  - Lista e pede pra escolher
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}    Atlas - Restauracao de Backup${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo

# Credenciais do banco
DB_NAME=$(grep -E "^DB_DATABASE=" .env 2>/dev/null | cut -d= -f2)
DB_USER=$(grep -E "^DB_USERNAME=" .env 2>/dev/null | cut -d= -f2)
DB_NAME=${DB_NAME:-atlas}
DB_USER=${DB_USER:-etc_user}

# Selecionar arquivo
ARQUIVO_PATH=""
if [ $# -gt 0 ]; then
    if [ -f "$1" ]; then
        ARQUIVO_PATH="$1"
    elif [ -f "backups/$1" ]; then
        ARQUIVO_PATH="backups/$1"
    else
        echo -e "  ${VERMELHO}✗${RESET} Arquivo nao encontrado: $1"
        echo "    Tente: ./restaurar.sh (sem args) para listar"
        exit 1
    fi
else
    echo "  Backups disponiveis em backups/:"
    echo

    arquivos=()
    while IFS= read -r f; do
        arquivos+=("$f")
    done < <(ls -1t backups/*.tar.gz backups/*.zip backups/*.sql 2>/dev/null)

    if [ ${#arquivos[@]} -eq 0 ]; then
        echo -e "  ${AMARELO}!${RESET} Nenhum backup encontrado."
        echo "    Crie um primeiro com: ./backup.sh"
        exit 1
    fi

    for i in "${!arquivos[@]}"; do
        echo "    [$((i+1))] $(basename "${arquivos[$i]}")"
    done

    echo
    read -p "  Numero do backup (0 para cancelar): " escolha
    if [ "$escolha" = "0" ] || [ -z "$escolha" ]; then
        exit 0
    fi

    idx=$((escolha-1))
    if [ $idx -lt 0 ] || [ $idx -ge ${#arquivos[@]} ]; then
        echo -e "  ${VERMELHO}✗${RESET} Numero invalido"
        exit 1
    fi
    ARQUIVO_PATH="${arquivos[$idx]}"
fi

echo
echo -e "  Arquivo: ${AZUL}${ARQUIVO_PATH}${RESET}"
echo
echo -e "  ${VERMELHO}!! ATENCAO !!${RESET}"
echo
echo "  A restauracao vai:"
echo "    - Sobrescrever o banco de dados atual (DROP + recreate)"
echo "    - Sobrescrever arquivos em storage/app/"
echo "    - Opcionalmente restaurar o .env (voce escolhe)"
echo
echo -e "  ${VERMELHO}TODOS OS DADOS ATUAIS SERAO PERDIDOS!${RESET}"
echo
read -p "  Digite 'RESTAURAR' para confirmar: " confirma
if [ "$confirma" != "RESTAURAR" ]; then
    echo
    echo "  Cancelado."
    exit 0
fi

# Snapshot do estado atual antes de restaurar
echo
echo -e "${AZUL}[1/5]${RESET} Snapshot do estado atual (seguranca)..."
echo

if ! docker compose ps --services --status=running 2>/dev/null | grep -q "^postgres$"; then
    echo -e "  ${VERMELHO}✗${RESET} Container PostgreSQL nao esta rodando."
    echo "    Inicie com: ./iniciar.sh"
    exit 1
fi

mkdir -p backups
TIMESTAMP=$(date +%Y-%m-%d_%H%M%S)
SNAPSHOT="backups/snapshot-pre-restauracao_${TIMESTAMP}.sql"

if docker compose exec -T postgres pg_dump -U "$DB_USER" -d "$DB_NAME" \
    --clean --if-exists --no-owner --no-privileges > "$SNAPSHOT" 2>/dev/null; then
    echo -e "  ${VERDE}✓${RESET} Snapshot salvo: $SNAPSHOT"
    echo "    (use este para reverter se algo der errado)"
else
    echo -e "  ${AMARELO}!${RESET} Falha no snapshot (banco vazio?). Continuando..."
fi

# Detecta tipo do arquivo
case "$ARQUIVO_PATH" in
    *.sql)
        # SQL puro
        echo
        echo -e "${AZUL}[2/3]${RESET} Restaurando banco a partir de SQL..."
        if docker compose exec -T postgres psql -U "$DB_USER" -d "$DB_NAME" < "$ARQUIVO_PATH" >/dev/null 2>&1; then
            echo -e "  ${VERDE}✓${RESET} Banco restaurado"
        else
            echo -e "  ${AMARELO}!${RESET} Houve avisos (normais para dumps com --if-exists)"
        fi

        echo
        echo -e "${AZUL}[3/3]${RESET} Limpando caches..."
        docker compose exec -T app php artisan config:clear >/dev/null 2>&1 || true
        docker compose exec -T app php artisan cache:clear >/dev/null 2>&1 || true
        docker compose exec -T app php artisan queue:restart >/dev/null 2>&1 || true
        echo -e "  ${VERDE}✓${RESET} Caches limpos"
        ;;

    *.tar.gz|*.tgz|*.zip)
        # Backup completo
        echo
        echo -e "${AZUL}[2/5]${RESET} Extraindo backup..."

        EXTRACT_DIR="backups/_restore_${TIMESTAMP}"
        mkdir -p "$EXTRACT_DIR"

        case "$ARQUIVO_PATH" in
            *.tar.gz|*.tgz)
                tar -xzf "$ARQUIVO_PATH" -C "$EXTRACT_DIR"
                ;;
            *.zip)
                if command -v unzip &>/dev/null; then
                    unzip -q "$ARQUIVO_PATH" -d "$EXTRACT_DIR"
                else
                    echo -e "  ${VERMELHO}✗${RESET} unzip nao instalado"
                    exit 1
                fi
                ;;
        esac
        echo -e "  ${VERDE}✓${RESET} Extraido"

        # Restaurar banco
        echo
        echo -e "${AZUL}[3/5]${RESET} Restaurando banco..."
        if [ ! -f "$EXTRACT_DIR/database.sql" ]; then
            echo -e "  ${VERMELHO}✗${RESET} database.sql nao encontrado no backup"
            rm -rf "$EXTRACT_DIR"
            exit 1
        fi
        docker compose exec -T postgres psql -U "$DB_USER" -d "$DB_NAME" < "$EXTRACT_DIR/database.sql" >/dev/null 2>&1 || \
            echo -e "  ${AMARELO}!${RESET} Houve avisos (normal com --if-exists)"
        echo -e "  ${VERDE}✓${RESET} Banco restaurado"

        # Restaurar arquivos
        echo
        echo -e "${AZUL}[4/5]${RESET} Restaurando arquivos do storage..."
        if [ -d "$EXTRACT_DIR/storage-private" ]; then
            mkdir -p storage/app/private
            cp -r "$EXTRACT_DIR/storage-private/"* storage/app/private/ 2>/dev/null || true
            echo -e "  ${VERDE}✓${RESET} Arquivos privados restaurados"
        fi
        if [ -d "$EXTRACT_DIR/storage-public" ]; then
            mkdir -p storage/app/public
            cp -r "$EXTRACT_DIR/storage-public/"* storage/app/public/ 2>/dev/null || true
            echo -e "  ${VERDE}✓${RESET} Arquivos publicos restaurados"
        fi

        # .env (opcional)
        if [ -f "$EXTRACT_DIR/.env.backup" ]; then
            echo
            read -p "  Restaurar o .env do backup tambem? (s/N): " restaura_env
            if [ "$restaura_env" = "s" ] || [ "$restaura_env" = "S" ]; then
                cp .env .env.pre-restauracao 2>/dev/null || true
                cp "$EXTRACT_DIR/.env.backup" .env
                echo -e "  ${VERDE}✓${RESET} .env restaurado"
                echo -e "  ${AMARELO}!${RESET} Reinicie containers: ./parar.sh && ./iniciar.sh"
            fi
        fi

        # Pos-restauracao
        echo
        echo -e "${AZUL}[5/5]${RESET} Limpando caches..."
        docker compose exec -T app php artisan config:clear >/dev/null 2>&1 || true
        docker compose exec -T app php artisan cache:clear >/dev/null 2>&1 || true
        docker compose exec -T app php artisan view:clear >/dev/null 2>&1 || true
        docker compose exec -T app php artisan route:clear >/dev/null 2>&1 || true
        docker compose exec -T app php artisan queue:restart >/dev/null 2>&1 || true
        echo -e "  ${VERDE}✓${RESET} Caches limpos, workers reiniciados"

        rm -rf "$EXTRACT_DIR"
        ;;

    *)
        echo -e "  ${VERMELHO}✗${RESET} Formato nao reconhecido: $ARQUIVO_PATH"
        echo "    Suportados: .tar.gz, .tgz, .zip, .sql"
        exit 1
        ;;
esac

echo
echo -e "${VERDE}================================================================${RESET}"
echo -e "${VERDE}    RESTAURACAO CONCLUIDA!${RESET}"
echo -e "${VERDE}================================================================${RESET}"
echo
echo -e "  Snapshot pre-restauracao: ${AZUL}${SNAPSHOT}${RESET}"
echo "  (use para reverter se algo der errado)"
echo
echo "  Recomenda-se reiniciar os containers:"
echo "    ./parar.sh && ./iniciar.sh"
echo
