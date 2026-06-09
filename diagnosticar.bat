@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Diagnostico e reset de portas
REM   Use quando "instalar.bat" falhar com "port is already allocated"
REM ===================================================================

title Atlas - Diagnostico

cls
echo.
echo  ================================================================
echo     Atlas - Diagnostico de Portas
echo  ================================================================
echo.

REM 1. Confere se Docker esta rodando
docker info >nul 2>&1
if errorlevel 1 (
    echo  X Docker Desktop nao esta rodando.
    echo    Abra o Docker Desktop e tente novamente.
    pause
    exit /b 1
)

echo  [1/4] Removendo containers antigos do projeto...
echo.
docker compose down --remove-orphans >nul 2>&1
echo  OK Containers parados e orphans removidos
echo.

echo  [2/4] Verificando portas usadas pelo projeto...
echo.

call :checa_porta 8000 "Aplicacao (Nginx)"
call :checa_porta 5432 "PostgreSQL"
call :checa_porta 6379 "Redis"
call :checa_porta 1025 "Mailpit SMTP"
call :checa_porta 8025 "Mailpit UI"

echo.

echo  [3/4] Conteudo atual de portas no .env...
echo.
if exist .env (
    findstr /B "APP_PORT= DB_PORT= REDIS_PORT= MAIL_PORT= MAIL_UI_PORT=" .env
    if errorlevel 1 (
        echo    ^(nenhuma variavel de porta customizada definida^)
    )
) else (
    echo    ^(.env nao existe - rode instalar.bat^)
)
echo.

echo  [4/4] Sugestao automatica de remapeamento...
echo.

if not exist .env (
    echo    Crie o .env primeiro rodando instalar.bat
    pause
    exit /b 0
)

call :remapeia_se_ocupada APP_PORT 8000
call :remapeia_se_ocupada DB_PORT 5432
call :remapeia_se_ocupada REDIS_PORT 6379
call :remapeia_se_ocupada MAIL_PORT 1025
call :remapeia_se_ocupada MAIL_UI_PORT 8025

echo.
echo  [5/5] Verificando assets compilados (CRITICO para login)...
echo.

if exist "public\build\manifest.json" (
    echo    OK  public\build\manifest.json existe
) else (
    echo    !!  public\build\manifest.json NAO existe
    echo        Causa COMUM de: "login mostra email/senha na URL e nao funciona"
    echo        Solucao: docker compose exec app npm run build
)

echo.
echo  ================================================================
echo     PRONTO! Agora rode novamente: instalar.bat
echo  ================================================================
echo.
pause
exit /b 0

REM ===================================================================
REM   SUB-ROTINAS
REM ===================================================================

:checa_porta
set "PORTA=%~1"
set "DESC=%~2"
netstat -ano | findstr ":!PORTA! " | findstr "LISTENING" >nul 2>&1
if errorlevel 1 (
    echo    OK  Porta !PORTA! livre        ^(!DESC!^)
) else (
    REM Mostra qual PID esta usando
    set "PIDS="
    for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":!PORTA! " ^| findstr "LISTENING"') do (
        set "PIDS=!PIDS! %%p"
    )
    echo    !!  Porta !PORTA! EM USO       ^(!DESC!^) - PID:!PIDS!
)
exit /b 0

:remapeia_se_ocupada
setlocal EnableDelayedExpansion
set "VAR_NOME=%~1"
set "PORTA_PADRAO=%~2"

set "PORTA_ATUAL="
for /f "tokens=2 delims==" %%i in ('findstr /B "!VAR_NOME!=" .env 2^>nul') do set "PORTA_ATUAL=%%i"
if "!PORTA_ATUAL!"=="" set "PORTA_ATUAL=!PORTA_PADRAO!"

netstat -ano | findstr ":!PORTA_ATUAL! " | findstr "LISTENING" >nul 2>&1
if errorlevel 1 (
    endlocal & exit /b 0
)

set /a PORTA_NOVA=!PORTA_ATUAL!+1
set CONTADOR=0
:procura
set /a CONTADOR+=1
if !CONTADOR! GEQ 50 (
    echo    X !VAR_NOME!: nao foi possivel encontrar porta livre
    endlocal & exit /b 1
)
netstat -ano | findstr ":!PORTA_NOVA! " | findstr "LISTENING" >nul 2>&1
if not errorlevel 1 (
    set /a PORTA_NOVA+=1
    goto procura
)

findstr /B "!VAR_NOME!=" .env >nul
if errorlevel 1 (
    powershell -NoProfile -Command "Add-Content -Path .env -Value '!VAR_NOME!=!PORTA_NOVA!'"
) else (
    powershell -NoProfile -Command "(Get-Content .env) -replace '^!VAR_NOME!=.*', '!VAR_NOME!=!PORTA_NOVA!' | Set-Content .env"
)
echo    OK !VAR_NOME!: !PORTA_ATUAL! -^> !PORTA_NOVA!
endlocal & exit /b 0
