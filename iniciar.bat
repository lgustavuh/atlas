@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Iniciar servicos (Windows)
REM   Versao: 3.0 (Maio/2026)
REM ===================================================================

title Atlas - Iniciar

set "VERDE=[92m"
set "AMARELO=[93m"
set "VERMELHO=[91m"
set "AZUL=[94m"
set "RESET=[0m"

cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Atlas - Iniciar Servicos%RESET%
echo  %AZUL%================================================================%RESET%
echo.

REM -- Docker rodando?
docker info >nul 2>&1
if errorlevel 1 (
    echo  %VERMELHO%X Docker Desktop nao esta rodando%RESET%
    echo.
    echo   Abra o Docker Desktop, aguarde inicializar, e tente novamente.
    echo.
    pause
    exit /b 1
)

REM -- Tem docker-compose.yml na pasta?
if not exist "docker-compose.yml" (
    echo  %VERMELHO%X docker-compose.yml nao encontrado%RESET%
    echo.
    echo   Voce esta na pasta correta do projeto?
    echo.
    pause
    exit /b 1
)

REM -- Tem .env?
if not exist ".env" (
    echo  %AMARELO%!%RESET% Arquivo .env nao encontrado.
    echo   Rode primeiro o instalar.bat para configurar o sistema.
    echo.
    pause
    exit /b 1
)

echo  Iniciando servicos do Docker...
docker compose up -d
if errorlevel 1 (
    echo.
    echo  %VERMELHO%X Falha ao iniciar os servicos%RESET%
    echo.
    echo   Verifique os logs com: docker compose logs
    pause
    exit /b 1
)

echo.
echo  Aguardando servicos ficarem prontos...

REM Espera PostgreSQL responder
set TENTATIVAS=0
:aguarda_pg
set /a TENTATIVAS+=1
docker compose exec -T postgres pg_isready -U etc_user >nul 2>&1
if errorlevel 1 (
    if !TENTATIVAS! GEQ 15 goto :timeout
    timeout /t 1 /nobreak >nul
    goto aguarda_pg
)
echo   %VERDE%OK%RESET% PostgreSQL

REM Espera o app responder (HTTP)
set TENTATIVAS=0
:aguarda_app
set /a TENTATIVAS+=1
powershell -NoProfile -Command "try { $r = Invoke-WebRequest -Uri 'http://localhost:8000' -UseBasicParsing -TimeoutSec 2; exit 0 } catch { exit 1 }" >nul 2>&1
if errorlevel 1 (
    if !TENTATIVAS! GEQ 15 (
        echo   %AMARELO%!%RESET% App demorou para responder ^(pode levar mais alguns segundos^)
        goto :pronto
    )
    timeout /t 1 /nobreak >nul
    goto aguarda_app
)
echo   %VERDE%OK%RESET% Aplicacao web

:pronto
echo.
echo  %VERDE%================================================================%RESET%
echo  %VERDE%    SERVICOS INICIADOS COM SUCESSO%RESET%
echo  %VERDE%================================================================%RESET%
echo.
echo   Sistema:  %AZUL%http://localhost:8000%RESET%
echo   Mailpit:  %AZUL%http://localhost:8025%RESET%
echo.
timeout /t 2 /nobreak >nul
start http://localhost:8000
exit /b 0

:timeout
echo.
echo  %VERMELHO%X Timeout aguardando PostgreSQL%RESET%
echo.
echo   Servicos iniciados mas o banco demorou demais.
echo   Verifique os logs com: logs.bat ou docker compose logs postgres
echo.
pause
exit /b 1
