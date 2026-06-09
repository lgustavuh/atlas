#!/usr/bin/env bash
# ===================================================================
#   Atlas - Diagnostico (Linux/Mac)
#   Versao: 1.1 (Junho/2026)
# ===================================================================

set -u

VERDE='\033[0;32m'
AMARELO='\033[0;33m'
VERMELHO='\033[0;31m'
AZUL='\033[0;34m'
RESET='\033[0m'

ok()    { echo -e "  ${VERDE}✓${RESET} $1"; }
aviso() { echo -e "  ${AMARELO}!${RESET} $1"; }
erro()  { echo -e "  ${VERMELHO}✗${RESET} $1"; }

clear
echo -e "${AZUL}================================================================${RESET}"
echo -e "${AZUL}   Atlas - Diagnostico${RESET}"
echo -e "${AZUL}================================================================${RESET}"
echo

# 1. Docker
echo -e "${AZUL}[1/6]${RESET} Docker..."
if docker info &>/dev/null; then ok "Daemon ativo"; else erro "Daemon offline"; exit 1; fi

# 2. Containers
echo
echo -e "${AZUL}[2/6]${RESET} Containers..."
for s in app nginx postgres redis mailpit; do
    if docker compose ps --services --status=running 2>/dev/null | grep -q "^${s}$"; then
        ok "${s} rodando"
    else
        aviso "${s} NAO esta rodando"
    fi
done

# 3. Portas
echo
echo -e "${AZUL}[3/6]${RESET} Portas..."
for p in 8000 5432 6379 1025 8025; do
    if command -v ss &>/dev/null; then
        if ss -tln | grep -q ":${p} "; then
            ok "${p} em uso (esperado se container rodando)"
        else
            aviso "${p} nao esta sendo escutada"
        fi
    fi
done

# 4. .env
echo
echo -e "${AZUL}[4/6]${RESET} Configuracao..."
if [ ! -f .env ]; then erro ".env nao encontrado"; exit 1; fi
grep -q "^APP_KEY=base64:" .env && ok "APP_KEY presente" || erro "APP_KEY ausente"

# 5. ASSETS - causa comum de "login nao funciona / dados na URL"
echo
echo -e "${AZUL}[5/6]${RESET} Assets compilados (CRITICO para login funcionar)..."
if [ -f public/build/manifest.json ]; then
    ok "public/build/manifest.json existe"
    count=$(find public/build/assets -type f 2>/dev/null | wc -l)
    if [ "$count" -gt 0 ]; then
        ok "${count} arquivo(s) em public/build/assets/"
    else
        aviso "public/build/assets/ vazio - reconstrua: docker compose exec app npm run build"
    fi
else
    erro "public/build/manifest.json AUSENTE"
    erro "Causa COMUM de: 'login mostra email/senha na URL e nao redireciona'"
    aviso "Solucao: docker compose exec app npm run build"
fi

# 6. HTTP
echo
echo -e "${AZUL}[6/6]${RESET} HTTP..."
APP_PORT=$(grep -E "^APP_PORT=" .env | cut -d= -f2 || echo 8000)
APP_PORT=${APP_PORT:-8000}

CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${APP_PORT}" --max-time 3 || echo "000")
case "$CODE" in
    200|302) ok "App respondeu HTTP ${CODE}" ;;
    000)     erro "Sem conexao em localhost:${APP_PORT}" ;;
    *)       aviso "HTTP ${CODE} (esperado 200 ou 302)" ;;
esac

# Sanidade do login: a pagina deve conter @vite tags renderizadas
echo
echo "Verificando renderizacao do login..."
HTML=$(curl -s "http://localhost:${APP_PORT}/" --max-time 5 || echo "")
if [ -z "$HTML" ]; then
    erro "Pagina inicial vazia"
elif echo "$HTML" | grep -q "wire:submit"; then
    ok "Form do Livewire renderizado"

    # Checa script do Vite (assets compilados)
    if echo "$HTML" | grep -qE 'src="[^"]+/build/assets/app-[a-zA-Z0-9]+\.js"'; then
        ok "Tags <script> do Vite presentes (build OK)"
    else
        erro "Tags <script> do Vite AUSENTES - rode: docker compose exec app npm run build"
    fi

    # Checa script do Livewire (carregado por @livewireScripts)
    if echo "$HTML" | grep -qE 'src="/livewire/livewire\.[a-zA-Z0-9]+\.js"|src="/livewire/livewire\.js"'; then
        ok "Script do Livewire injetado"
    else
        erro "Script do Livewire AUSENTE - login NAO vai interceptar"
        aviso "Causa comum: @livewireScripts esta faltando no layout"
        aviso "Veja: resources/views/layouts/guest.blade.php"
    fi

    # Checa CSRF
    if echo "$HTML" | grep -q 'name="csrf-token"'; then
        ok "Meta CSRF presente"
    else
        erro "Meta CSRF ausente"
    fi

    # Checa se form tem method indevido (resíduo de v1.2 que quebra com POST)
    if echo "$HTML" | grep -qE '<form method="post" wire:submit'; then
        erro "FORM com method=\"post\" + wire:submit detectado!"
        erro "Isso causa 'Method Not Allowed' apos clicar Entrar"
        aviso "Solucao: atualize pra v1.4+ (que tem o fix)"
    fi
else
    aviso "Form do Livewire NAO encontrado na pagina inicial"
fi

echo
echo -e "${AZUL}================================================================${RESET}"
echo -e "  Diagnostico concluido"
echo -e "${AZUL}================================================================${RESET}"
