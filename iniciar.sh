#!/usr/bin/env bash
# ===================================================================
#   Atlas - Iniciar (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}    Atlas - Iniciar Servicos${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo

# Docker rodando?
if ! docker info &>/dev/null; then
    echo -e "  ${VERMELHO}✗${RESET} Docker daemon nao esta rodando."
    echo "    Inicie o Docker e tente novamente."
    exit 1
fi

# Tem docker-compose.yml?
if [ ! -f docker-compose.yml ]; then
    echo -e "  ${VERMELHO}✗${RESET} docker-compose.yml nao encontrado."
    echo "    Voce esta na pasta correta do projeto?"
    exit 1
fi

# Tem .env?
if [ ! -f .env ]; then
    echo -e "  ${AMARELO}!${RESET} .env nao encontrado."
    echo "    Rode primeiro: ./instalar.sh"
    exit 1
fi

echo "  Iniciando servicos..."
if ! docker compose up -d; then
    echo
    echo -e "  ${VERMELHO}✗${RESET} Falha ao subir containers."
    echo "    Verifique: docker compose logs"
    exit 1
fi

echo
echo "  Aguardando servicos..."

# PostgreSQL
TENTATIVAS=0
while [ $TENTATIVAS -lt 15 ]; do
    if docker compose exec -T postgres pg_isready -U etc_user &>/dev/null; then
        echo -e "    ${VERDE}✓${RESET} PostgreSQL"
        break
    fi
    TENTATIVAS=$((TENTATIVAS+1))
    sleep 1
done

# App HTTP
APP_PORT=$(grep -E "^APP_PORT=" .env 2>/dev/null | cut -d= -f2)
APP_PORT=${APP_PORT:-8000}

TENTATIVAS=0
while [ $TENTATIVAS -lt 15 ]; do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${APP_PORT}" --max-time 2 || echo "000")
    if [ "$CODE" = "200" ] || [ "$CODE" = "302" ]; then
        echo -e "    ${VERDE}✓${RESET} Aplicacao web (HTTP $CODE)"
        break
    fi
    TENTATIVAS=$((TENTATIVAS+1))
    sleep 1
done

echo
echo -e "${VERDE}================================================================${RESET}"
echo -e "${VERDE}    SERVICOS INICIADOS${RESET}"
echo -e "${VERDE}================================================================${RESET}"
echo
echo -e "  Sistema:  ${AZUL}http://localhost:${APP_PORT}${RESET}"
echo -e "  Mailpit:  ${AZUL}http://localhost:8025${RESET}"
echo

# Abre o browser se possivel
if command -v xdg-open &>/dev/null; then
    xdg-open "http://localhost:${APP_PORT}" 2>/dev/null &
elif command -v open &>/dev/null; then
    open "http://localhost:${APP_PORT}" 2>/dev/null &
fi
