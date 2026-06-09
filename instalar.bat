@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Instalador automatizado (Windows)
REM   Versao: 3.3 (Junho/2026)
REM ===================================================================
REM
REM   Mudancas v3.3:
REM     - Fix: erro "sintaxe do nome do arquivo incorreta" em loop
REM       (causa: setlocal/endlocal escondia %LOG_FILE% das subrotinas)
REM     - Timestamps via PowerShell (evita problemas com %date%/%time% pt-BR)
REM     - LOG_FILE em variavel global em arquivo separado
REM ===================================================================

title Atlas - Instalador v3.3

REM Habilita VT100 no cmd.exe (cores ANSI funcionam em Windows 10+)
reg add HKCU\Console /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1

REM Define escape ANSI
for /f %%E in ('echo prompt $E ^| cmd') do set "ESC=%%E"
set "VERDE=%ESC%[92m"
set "AMARELO=%ESC%[93m"
set "VERMELHO=%ESC%[91m"
set "AZUL=%ESC%[94m"
set "RESET=%ESC%[0m"

REM -------------------------------------------------------------------
REM  Setup do log
REM -------------------------------------------------------------------
if not exist "logs-instalacao" mkdir "logs-instalacao" 2>nul

REM Timestamp via PowerShell (evita problemas de locale)
for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "TIMESTAMP=%%i"

REM LOG_FILE em variavel global - acessivel de qualquer subrotina sem setlocal
set "LOG_FILE=logs-instalacao\install_!TIMESTAMP!.log"

REM Marca inicio
call :gravar_log "=== Inicio da instalacao ==="

cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Atlas - Sistema de Gestao - Instalador v3.3%RESET%
echo  %AZUL%================================================================%RESET%
echo.
echo  Log de instalacao: %AZUL%!LOG_FILE!%RESET%
echo.

REM Detectar WSL
where wsl >nul 2>&1
if not errorlevel 1 (
    wsl -e bash -c "exit 0" >nul 2>&1
    if not errorlevel 1 (
        echo  %AMARELO%i%RESET% Voce tem WSL instalado.
        echo     Se preferir, pode usar o instalar.sh dentro do WSL.
        echo.
        set /p usar_wsl="   Continuar com este instalador Windows? (S/n): "
        if /i "!usar_wsl!"=="n" (
            call :gravar_log "Usuario optou por usar WSL"
            pause
            exit /b 0
        )
        echo.
    )
)

REM -------------------------------------------------------------------
REM  1. Pre-requisitos
REM -------------------------------------------------------------------
call :secao "1/9" "Verificando pre-requisitos..."

docker --version >nul 2>&1
if errorlevel 1 (
    call :erro "Docker nao encontrado. Instale o Docker Desktop em https://www.docker.com/products/docker-desktop"
    pause & exit /b 1
)
for /f "tokens=*" %%i in ('docker --version') do call :ok "%%i"

docker compose version >nul 2>&1
if errorlevel 1 (
    call :erro "Docker Compose v2 nao disponivel. Atualize o Docker Desktop."
    pause & exit /b 1
)
for /f "tokens=*" %%i in ('docker compose version') do call :ok "%%i"

docker info >nul 2>&1
if errorlevel 1 (
    call :erro "Docker Desktop nao esta rodando. Abra-o e tente novamente."
    pause & exit /b 1
)
call :ok "Docker daemon ativo"

powershell -NoProfile -Command "if ($PSVersionTable.PSVersion.Major -lt 5) { exit 1 }" >nul 2>&1
if errorlevel 1 (
    call :erro "PowerShell 5.1+ necessario"
    pause & exit /b 1
)
call :ok "PowerShell disponivel"

for /f "tokens=3" %%a in ('dir /-c "%~dp0" ^| findstr /R "bytes free"') do set ESPACO=%%a
set ESPACO_GB=!ESPACO:~0,-9!
if !ESPACO_GB! LSS 3 (
    call :aviso "Pouco espaco em disco: !ESPACO_GB!GB livres"
    set /p continuar="   Continuar? (s/N): "
    if /i not "!continuar!"=="s" exit /b 1
) else (
    call :ok "Espaco em disco: !ESPACO_GB!GB livres"
)

REM -------------------------------------------------------------------
REM  2. .env
REM -------------------------------------------------------------------
call :secao "2/9" "Configurando arquivo de ambiente (.env)..."

if exist ".env" (
    call :aviso "Arquivo .env ja existe - mantendo o atual"
    goto :pos_env
)

if not exist ".env.example" (
    call :erro ".env.example nao encontrado. Voce esta na pasta correta?"
    pause & exit /b 1
)

copy ".env.example" ".env" >nul

call :info "Gerando senhas aleatorias seguras..."

REM Chamadas a subrotinas - importante: NAO usar PowerShell complexo
REM dentro de blocos if/else, pois o cmd quebra com parenteses
call :gerar_senha DB_PWD
call :gerar_senha REDIS_PWD

powershell -NoProfile -Command "(Get-Content .env -Raw) -replace 'DB_PASSWORD=dev_password_change_me', ('DB_PASSWORD=' + $env:DB_PWD) | Set-Content .env -NoNewline"
powershell -NoProfile -Command "(Get-Content .env -Raw) -replace 'REDIS_PASSWORD=dev_redis_change_me', ('REDIS_PASSWORD=' + $env:REDIS_PWD) | Set-Content .env -NoNewline"

call :ok ".env criado com senhas aleatorias"

:pos_env

REM -------------------------------------------------------------------
REM  3. Verificar portas
REM -------------------------------------------------------------------
call :secao "3/9" "Verificando portas disponiveis..."

call :verifica_porta APP_PORT 8000 "Aplicacao (Nginx)"
call :verifica_porta DB_PORT 5432 "PostgreSQL"
call :verifica_porta REDIS_PORT 6379 "Redis"
call :verifica_porta MAIL_PORT 1025 "Mailpit SMTP"
call :verifica_porta MAIL_UI_PORT 8025 "Mailpit UI"

REM -------------------------------------------------------------------
REM  4. Storage
REM -------------------------------------------------------------------
call :secao "4/9" "Preparando estrutura de storage..."

if not exist "storage\app\private" mkdir "storage\app\private" 2>nul
if not exist "storage\app\private\atestados" mkdir "storage\app\private\atestados" 2>nul
if not exist "storage\app\private\advertencias" mkdir "storage\app\private\advertencias" 2>nul
if not exist "storage\app\private\curriculos" mkdir "storage\app\private\curriculos" 2>nul
if not exist "storage\app\private\biblioteca" mkdir "storage\app\private\biblioteca" 2>nul
if not exist "storage\app\private\fotos" mkdir "storage\app\private\fotos" 2>nul
if not exist "storage\app\public" mkdir "storage\app\public" 2>nul
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data" 2>nul
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions" 2>nul
if not exist "storage\framework\views" mkdir "storage\framework\views" 2>nul
if not exist "storage\logs" mkdir "storage\logs" 2>nul
if not exist "logs-instalacao" mkdir "logs-instalacao" 2>nul
if not exist "backups" mkdir "backups" 2>nul

call :ok "Diretorios prontos (storage, logs, backups)"

REM -------------------------------------------------------------------
REM  5. Build
REM -------------------------------------------------------------------
call :secao "5/9" "Construindo imagens Docker (primeira vez demora ~5min)..."

REM Limpa containers zumbis de runs anteriores
docker compose down --remove-orphans >>"!LOG_FILE!" 2>&1

docker compose build >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :erro "Falha ao construir as imagens. Veja: !LOG_FILE!"
    pause & exit /b 1
)
call :ok "Imagens construidas"

REM -------------------------------------------------------------------
REM  6. Up
REM -------------------------------------------------------------------
call :secao "6/9" "Iniciando servicos..."

docker compose up -d >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :erro "Falha ao subir containers."
    echo.
    echo   %AZUL%Causa mais comum:%RESET% conflito de porta com servico ja instalado.
    echo   %AZUL%Para resolver:%RESET%
    echo     1. Rode: diagnosticar.bat ^(detecta e remapeia portas^)
    echo     2. Rode novamente: instalar.bat
    echo.
    echo   Detalhes: !LOG_FILE!
    echo.
    pause & exit /b 1
)

call :info "Aguardando servicos ficarem prontos..."
set TENTATIVAS=0
:aguarda_postgres
set /a TENTATIVAS+=1
docker compose exec -T postgres pg_isready -U etc_user >nul 2>&1
if errorlevel 1 (
    if !TENTATIVAS! GEQ 30 (
        call :erro "Timeout aguardando PostgreSQL. Veja !LOG_FILE! e docker compose logs postgres"
        pause & exit /b 1
    )
    timeout /t 2 /nobreak >nul
    goto aguarda_postgres
)
call :ok "PostgreSQL pronto"

docker compose exec -T redis redis-cli ping >nul 2>&1
if errorlevel 1 (
    call :aviso "Redis ainda nao respondeu"
) else (
    call :ok "Redis pronto"
)

REM -------------------------------------------------------------------
REM  7. Dependencias
REM -------------------------------------------------------------------
call :secao "7/9" "Instalando dependencias..."

call :info "Composer install..."
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :erro "Falha no Composer. Veja !LOG_FILE!"
    echo.
    echo   %AMARELO%Erro tipico:%RESET% "proc_open is not available" significa que o php.ini
    echo   esta com proc_open desabilitado. Edite docker/php/php.ini e retire
    echo   proc_open e popen da linha disable_functions, depois rode:
    echo     docker compose build app
    echo     instalar.bat
    echo.
    pause & exit /b 1
)
call :ok "Dependencias PHP"

call :info "npm install..."
docker compose exec -T app npm install --silent >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :erro "Falha no npm. Veja !LOG_FILE!"
    pause & exit /b 1
)
call :ok "Dependencias JS"

REM -------------------------------------------------------------------
REM  8. App
REM -------------------------------------------------------------------
call :secao "8/9" "Configurando a aplicacao..."

findstr /B "APP_KEY=base64:" .env >nul
if errorlevel 1 (
    call :info "Gerando chave de aplicacao..."
    docker compose exec -T app php artisan key:generate --force >>"!LOG_FILE!" 2>&1
    call :ok "APP_KEY gerada"
) else (
    call :ok "APP_KEY ja existe"
)

call :info "Limpando caches..."
docker compose exec -T app php artisan config:clear >>"!LOG_FILE!" 2>&1
docker compose exec -T app php artisan cache:clear >>"!LOG_FILE!" 2>&1
docker compose exec -T app php artisan view:clear >>"!LOG_FILE!" 2>&1
docker compose exec -T app php artisan route:clear >>"!LOG_FILE!" 2>&1
call :ok "Caches limpos"

call :info "Executando migrations..."
docker compose exec -T app php artisan migrate --force >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :erro "Falha nas migrations. Veja !LOG_FILE!"
    pause & exit /b 1
)
call :ok "Migrations aplicadas"

call :info "Seeders..."
docker compose exec -T app php artisan db:seed --force >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :aviso "Seeders ja executados anteriormente"
) else (
    call :ok "Seeders executados"
)

docker compose exec -T app php artisan storage:link >>"!LOG_FILE!" 2>&1
call :ok "Storage linked"

call :info "Importando cidades IBGE..."
docker compose exec -T app php artisan etc:importar-cidades-ibge >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :aviso "Cidades IBGE puladas"
) else (
    call :ok "Cidades IBGE importadas"
)

REM -------------------------------------------------------------------
REM  9. Assets + validacao
REM -------------------------------------------------------------------
call :secao "9/9" "Compilando assets e validando..."

call :info "Rodando npm run build (pode demorar 1-2min)..."
docker compose exec -T app npm run build >>"!LOG_FILE!" 2>&1
if errorlevel 1 (
    call :erro "Falha ao compilar assets - LOGIN VAI QUEBRAR sem isso!"
    call :aviso "Tente manual: docker compose exec app npm run build"
) else (
    call :ok "npm run build executado"
)

REM Validacao CRITICA: sem manifest.json, Livewire nao carrega e login fica quebrado
REM (sintoma: email/senha aparecem na URL apos clicar Entrar)
if exist "public\build\manifest.json" (
    call :ok "Manifest do Vite presente (assets compilados)"
) else (
    call :erro "ATENCAO: public\build\manifest.json NAO existe!"
    call :erro "Sem ele, Livewire nao carrega e o LOGIN FICA QUEBRADO."
    call :erro "Sintoma: ao logar, email/senha aparecem na URL e nao redireciona."
    call :aviso "Solucao: docker compose exec app npm run build"
    set "INSTALACAO_OK=0"
)

call :info "Validando instalacao..."

set "INSTALACAO_OK=1"
findstr /B "APP_KEY=base64:" .env >nul
if errorlevel 1 (
    call :erro "APP_KEY nao foi gerada"
    set "INSTALACAO_OK=0"
)

findstr "DB_PASSWORD=dev_password_change_me" .env >nul
if not errorlevel 1 (
    call :aviso "DB_PASSWORD ainda e a default - troque antes de ir pra producao"
)

for /f "tokens=2 delims==" %%i in ('findstr /B "APP_PORT=" .env') do set "FINAL_APP_PORT=%%i"
if "!FINAL_APP_PORT!"=="" set "FINAL_APP_PORT=8000"

powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri 'http://localhost:!FINAL_APP_PORT!' -UseBasicParsing -TimeoutSec 5; if ($r.StatusCode -eq 200 -or $r.StatusCode -eq 302) { exit 0 } else { exit 1 } } catch { exit 1 }" >nul 2>&1
if errorlevel 1 (
    call :aviso "Site ainda nao respondeu (pode levar mais alguns segundos)"
) else (
    call :ok "Site respondendo na porta !FINAL_APP_PORT!"
)

echo.

REM Finalizacao
for /f "tokens=2 delims==" %%i in ('findstr /B "APP_PORT=" .env') do set "FINAL_APP_PORT=%%i"
for /f "tokens=2 delims==" %%i in ('findstr /B "MAIL_UI_PORT=" .env') do set "FINAL_MAIL_UI_PORT=%%i"
if "!FINAL_APP_PORT!"=="" set "FINAL_APP_PORT=8000"
if "!FINAL_MAIL_UI_PORT!"=="" set "FINAL_MAIL_UI_PORT=8025"

if "!INSTALACAO_OK!"=="1" (
    echo  %VERDE%================================================================%RESET%
    echo  %VERDE%       INSTALACAO CONCLUIDA COM SUCESSO!%RESET%
    echo  %VERDE%================================================================%RESET%
    call :gravar_log "=== Instalacao concluida com sucesso ==="
) else (
    echo  %AMARELO%================================================================%RESET%
    echo  %AMARELO%   INSTALACAO CONCLUIDA COM AVISOS%RESET%
    echo  %AMARELO%================================================================%RESET%
    call :gravar_log "=== Instalacao concluida com avisos ==="
)
echo.
echo   Sistema:  %AZUL%http://localhost:!FINAL_APP_PORT!%RESET%
echo   Mailpit:  %AZUL%http://localhost:!FINAL_MAIL_UI_PORT!%RESET%
echo.
echo   %AZUL%Credenciais de admin:%RESET%
echo     Email:  admin@atlas.local
echo     Senha:  Admin@123456
echo.
echo   %AMARELO%IMPORTANTE:%RESET% Troque a senha do admin no primeiro acesso.
echo.
echo  ----------------------------------------------------------------
echo   %AZUL%Comandos uteis:%RESET%
echo     iniciar.bat       - Sobe os servicos
echo     parar.bat         - Para os servicos
echo     logs.bat          - Ve os logs em tempo real
echo     atualizar.bat     - Atualiza apos pull do git
echo     backup.bat        - Faz backup completo do sistema
echo     diagnosticar.bat  - Diagnostica problemas
echo  ----------------------------------------------------------------
echo.
echo   Log desta instalacao: %AZUL%!LOG_FILE!%RESET%
echo.

set /p abrir="   Abrir o navegador agora? (S/n): "
if /i not "!abrir!"=="n" (
    start http://localhost:!FINAL_APP_PORT!
)

echo.
pause
endlocal
exit /b 0


REM ===================================================================
REM   SUB-ROTINAS
REM ===================================================================
REM
REM   IMPORTANTE: as subrotinas abaixo NAO usam setlocal/endlocal proprios
REM   para que LOG_FILE (variavel global) fique visivel. Variaveis locais
REM   de subrotina (como em :verifica_porta) usam nomes prefixados com VP_
REM   para evitar conflito com variaveis do main.
REM
REM   Tambem evitamos %date% e %time% direto no echo: usamos PowerShell
REM   no inicio de cada chamada para timestamp consistente em qualquer locale.
REM ===================================================================

:gravar_log
REM Argumento %~1 = mensagem
REM Grava no log com timestamp via PowerShell (locale-safe)
for /f "delims=" %%T in ('powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd HH:mm:ss'"') do (
    echo [%%T] %~1>> "!LOG_FILE!"
)
exit /b 0

:secao
echo.
echo  %AZUL%[%~1]%RESET% %~2
echo.
call :gravar_log "[SECAO %~1] %~2"
exit /b 0

:ok
echo    %VERDE%OK%RESET% %~1
call :gravar_log "[OK] %~1"
exit /b 0

:aviso
echo    %AMARELO%!%RESET% %~1
call :gravar_log "[AVISO] %~1"
exit /b 0

:erro
echo    %VERMELHO%X%RESET% %~1
call :gravar_log "[ERRO] %~1"
exit /b 0

:info
echo    %~1
call :gravar_log "[INFO] %~1"
exit /b 0

REM ===================================================================
REM   :gerar_senha  -  NOME_DA_VAR
REM
REM   Gera senha aleatoria de 32 chars [A-Za-z0-9] e armazena
REM   na variavel cujo nome foi passado (ex: chame "call :gerar_senha DB_PWD"
REM   para definir DB_PWD com a senha gerada).
REM
REM   IMPORTANTE: O comando PowerShell complexo com (), |, $ fica seguro
REM   aqui dentro porque sub-rotinas tem parser proprio (nao estao dentro
REM   de bloco if/else como antes).
REM ===================================================================
:gerar_senha
for /f "delims=" %%P in ('powershell -NoProfile -Command "-join ((48..57 + 65..90 + 97..122) | Get-Random -Count 32 | ForEach-Object {[char]$_})"') do set "%~1=%%P"
exit /b 0

REM ===================================================================
REM   :verifica_porta  -  VAR_NOME  PORTA_PADRAO  DESCRICAO
REM
REM   IMPORTANTE: NAO usa setlocal! Variaveis sao prefixadas VP_ para
REM   nao conflitar com main. Isso permite que !LOG_FILE! seja visivel.
REM ===================================================================
:verifica_porta
set "VP_VAR=%~1"
set "VP_PADRAO=%~2"
set "VP_DESC=%~3"

set "VP_ATUAL="
for /f "tokens=2 delims==" %%i in ('findstr /B "!VP_VAR!=" .env 2^>nul') do set "VP_ATUAL=%%i"
if "!VP_ATUAL!"=="" set "VP_ATUAL=!VP_PADRAO!"

netstat -ano | findstr ":!VP_ATUAL! " | findstr "LISTENING" >nul 2>&1
if errorlevel 1 (
    echo    %VERDE%OK%RESET% Porta !VP_ATUAL! disponivel ^(!VP_DESC!^)
    call :gravar_log "[OK] Porta !VP_ATUAL! disponivel para !VP_DESC!"
    exit /b 0
)

echo    %AMARELO%!%RESET% Porta !VP_ATUAL! em uso por outro processo ^(!VP_DESC!^)
call :gravar_log "[AVISO] Porta !VP_ATUAL! em uso, remapeando !VP_DESC!"

set /a VP_NOVA=!VP_ATUAL!+1
set VP_CONT=0
:vp_busca
set /a VP_CONT+=1
if !VP_CONT! GEQ 50 (
    echo    %VERMELHO%X%RESET% Nao foi possivel encontrar porta livre apos 50 tentativas
    call :gravar_log "[ERRO] Nao achou porta livre para !VP_VAR!"
    exit /b 1
)
netstat -ano | findstr ":!VP_NOVA! " | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 (
    set /a VP_NOVA+=1
    goto vp_busca
)

echo       Remapeando para porta !VP_NOVA!...

REM Edita .env via subrotina (NAO em bloco if/else - parens do PS quebram cmd)
findstr /B "!VP_VAR!=" .env >nul
if errorlevel 1 (
    set "VP_OP=ADD"
) else (
    set "VP_OP=REPLACE"
)

if "!VP_OP!"=="ADD"     call :env_add !VP_VAR! !VP_NOVA!
if "!VP_OP!"=="REPLACE" call :env_replace !VP_VAR! !VP_NOVA!

echo    %VERDE%OK%RESET% !VP_DESC! agora usara porta !VP_NOVA!
call :gravar_log "[OK] !VP_VAR! remapeado de !VP_ATUAL! para !VP_NOVA!"
exit /b 0

REM ===================================================================
REM   :env_add  - adiciona linha "VAR=valor" ao final do .env
REM   :env_replace - substitui linha "VAR=..." por "VAR=valor"
REM ===================================================================
:env_add
powershell -NoProfile -Command "Add-Content -Path .env -Value '%~1=%~2'"
exit /b 0

:env_replace
powershell -NoProfile -Command "(Get-Content .env) -replace ('^' + '%~1' + '=.*'), ('%~1=' + '%~2') | Set-Content .env"
exit /b 0
