#!/usr/bin/env bash
# ===================================================================
#   Atlas - Popular Banco com Dados Fictícios (Linux/Mac)
#   Versao: 1.0 (Junho/2026)
#
#   Uso:
#     ./popular-dados-teste.sh           Adiciona 10+ registros por módulo
#     ./popular-dados-teste.sh --reset   Limpa e recria do zero (CUIDADO!)
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}    Atlas - Popular Banco com Dados Fictícios${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo

# Pre-requisitos
if ! docker info &>/dev/null; then
    echo -e "  ${VERMELHO}✗${RESET} Docker não está rodando."
    exit 1
fi

if ! docker compose ps --services --status=running 2>/dev/null | grep -q "^app$"; then
    echo -e "  ${VERMELHO}✗${RESET} Container 'app' não está rodando."
    echo "    Inicie com: ./iniciar.sh"
    exit 1
fi

# Bloqueia em produção
APP_ENV=$(docker compose exec -T app printenv APP_ENV 2>/dev/null | tr -d '\r')
if [ "$APP_ENV" = "production" ]; then
    echo -e "  ${VERMELHO}!! ATENÇÃO !!${RESET}"
    echo
    echo "    Você está em APP_ENV=production."
    echo "    Adicionar dados fictícios pode poluir dados reais."
    echo
    read -p "    Tem CERTEZA que quer continuar? Digite 'SIM EM PRODUCAO': " confirma
    if [ "$confirma" != "SIM EM PRODUCAO" ]; then
        echo "    Cancelado."
        exit 0
    fi
fi

# Modo --reset: apaga tudo e recria
if [ "${1:-}" = "--reset" ]; then
    echo -e "  ${AMARELO}!! MODO RESET !!${RESET}"
    echo
    echo "    Isso vai:"
    echo -e "      - ${VERMELHO}APAGAR TODAS as tabelas${RESET} (migrate:fresh)"
    echo "      - Recriar do zero com seeders padrão (admin, geografia, perfis)"
    echo "      - Adicionar 10+ registros em cada módulo"
    echo
    read -p "    Digite 'RESETAR' para confirmar: " confirma
    if [ "$confirma" != "RESETAR" ]; then
        echo "    Cancelado."
        exit 0
    fi

    echo
    echo "  Recriando tabelas..."
    docker compose exec -T app php artisan migrate:fresh --force
    echo
    echo "  Rodando seeders padrão (admin + geografia + perfis)..."
    docker compose exec -T app php artisan db:seed --force
fi

# Adiciona dados fictícios
echo
echo "  Populando dados fictícios em cada módulo..."
echo

if docker compose exec -T app php artisan db:seed --class=DadosFicticiosSeeder --force; then
    echo
    echo -e "${VERDE}================================================================${RESET}"
    echo -e "${VERDE}    Dados fictícios criados com sucesso!${RESET}"
    echo -e "${VERDE}================================================================${RESET}"
    echo
    echo "  Agora você pode acessar o sistema e testar todos os módulos:"

    APP_PORT=$(grep -E "^APP_PORT=" .env 2>/dev/null | cut -d= -f2)
    APP_PORT=${APP_PORT:-8000}
    echo -e "    ${AZUL}http://localhost:${APP_PORT}${RESET}"
    echo
    echo "    Login: admin@atlas.local / Admin@123456"
    echo
else
    echo
    echo -e "  ${VERMELHO}✗${RESET} Houve falha ao popular dados. Veja a saída acima."
    exit 1
fi
