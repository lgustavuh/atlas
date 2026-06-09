#!/usr/bin/env bash
# ===================================================================
#   Atlas - Logs (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
#
#   Uso:
#     ./logs.sh              - app + nginx (default)
#     ./logs.sh app          - so app
#     ./logs.sh postgres     - so banco
#     ./logs.sh todos        - todos os servicos
# ===================================================================

set -u

AZUL='\033[0;34m'
AMARELO='\033[0;33m'
RESET='\033[0m'

if ! docker info &>/dev/null; then
    echo "  Docker nao esta rodando. Inicie e tente novamente."
    exit 1
fi

if [ ! -f docker-compose.yml ]; then
    echo "  docker-compose.yml nao encontrado."
    exit 1
fi

SERVICOS="app nginx"
if [ $# -gt 0 ]; then
    case "$1" in
        todos|all) SERVICOS="" ;;
        *)         SERVICOS="$1" ;;
    esac
fi

echo
echo -e "${AZUL}================================================================${RESET}"
if [ -z "$SERVICOS" ]; then
    echo -e "${AZUL}    Logs em tempo real - TODOS${RESET}"
else
    echo -e "${AZUL}    Logs em tempo real: ${SERVICOS}${RESET}"
fi
echo -e "${AZUL}================================================================${RESET}"
echo
echo -e "  ${AMARELO}Pressione Ctrl+C para sair.${RESET}"
echo

exec docker compose logs -f --tail=100 $SERVICOS
