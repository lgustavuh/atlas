@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Ver logs (Windows)
REM   Versao: 3.0 (Maio/2026)
REM
REM   Uso:
REM     logs.bat              - Logs do app + nginx (padrao)
REM     logs.bat app          - So o container app (PHP/Laravel)
REM     logs.bat nginx        - So o nginx
REM     logs.bat postgres     - Logs do banco
REM     logs.bat redis        - Logs do Redis
REM     logs.bat mailpit      - Logs do mailpit
REM     logs.bat todos        - Todos os servicos
REM ===================================================================

set "AZUL=[94m"
set "AMARELO=[93m"
set "RESET=[0m"

REM Docker rodando?
docker info >nul 2>&1
if errorlevel 1 (
    echo  Docker nao esta rodando. Inicie o Docker Desktop e tente novamente.
    pause
    exit /b 1
)

REM Container existe?
if not exist "docker-compose.yml" (
    echo  docker-compose.yml nao encontrado.
    pause
    exit /b 1
)

REM Definir servicos a mostrar
set "SERVICOS=app nginx"
if not "%~1"=="" (
    if /i "%~1"=="todos" (
        set "SERVICOS="
    ) else (
        set "SERVICOS=%~1"
    )
)

title Atlas - Logs [%SERVICOS%] (Ctrl+C para sair)

echo.
echo  %AZUL%================================================================%RESET%
if "%SERVICOS%"=="" (
    echo  %AZUL%    Logs em tempo real - TODOS os servicos%RESET%
) else (
    echo  %AZUL%    Logs em tempo real: %SERVICOS%%RESET%
)
echo  %AZUL%================================================================%RESET%
echo.
echo  %AMARELO%Pressione Ctrl+C para sair.%RESET%
echo.

docker compose logs -f --tail=100 %SERVICOS%
