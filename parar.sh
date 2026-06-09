#!/usr/bin/env bash
# ===================================================================
#   Atlas - Parar (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
#
#   Uso:
#     ./parar.sh         - Para containers (preserva dados)
#     ./parar.sh --tudo  - Para e APAGA TUDO (incluindo banco)
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}    Atlas - Parar Servicos${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo

if ! docker info &>/dev/null; then
    echo -e "  ${AMARELO}!${RESET} Docker nao esta rodando - nada a parar."
    exit 0
fi

if [ ! -f docker-compose.yml ]; then
    echo -e "  ${VERMELHO}✗${RESET} docker-compose.yml nao encontrado."
    exit 1
fi

if [ "${1:-}" = "--tudo" ] || [ "${1:-}" = "--all" ] || [ "${1:-}" = "-a" ]; then
    echo -e "  ${VERMELHO}!! ATENCAO !!${RESET}"
    echo
    echo "    Voce esta prestes a:"
    echo "      - Parar todos os containers"
    echo "      - Remover os volumes Docker"
    echo -e "      - ${VERMELHO}APAGAR PERMANENTEMENTE o banco de dados${RESET}"
    echo -e "      - ${VERMELHO}APAGAR PERMANENTEMENTE arquivos enviados${RESET}"
    echo
    read -p "    Digite 'APAGAR' para confirmar: " confirma
    if [ "$confirma" != "APAGAR" ]; then
        echo
        echo "    Cancelado. Nada foi alterado."
        exit 0
    fi

    echo
    echo "    Parando e removendo containers + volumes..."
    docker compose down -v
    echo
    echo -e "  ${VERDE}✓${RESET} Tudo removido."
    echo
    echo "    Para reinstalar do zero, rode: ./instalar.sh"
    exit 0
fi

# Modo normal
echo "  Parando containers..."
if ! docker compose down; then
    echo -e "  ${VERMELHO}✗${RESET} Falha ao parar."
    exit 1
fi

echo
echo -e "  ${VERDE}✓${RESET} Servicos parados."
echo
echo "    Os dados (banco, uploads) foram preservados."
echo "    Use ./iniciar.sh para subir novamente."
echo
echo "    Para apagar tambem os volumes:"
echo "      ./parar.sh --tudo"
echo
