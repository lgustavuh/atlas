@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Parar servicos (Windows)
REM   Versao: 3.0 (Maio/2026)
REM
REM   Opcoes:
REM     parar.bat          - Para os containers (volumes preservados)
REM     parar.bat --tudo   - Para e remove TUDO (incluindo banco)
REM ===================================================================

title Atlas - Parar

set "VERDE=[92m"
set "AMARELO=[93m"
set "VERMELHO=[91m"
set "AZUL=[94m"
set "RESET=[0m"

cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Atlas - Parar Servicos%RESET%
echo  %AZUL%================================================================%RESET%
echo.

REM -- Docker rodando?
docker info >nul 2>&1
if errorlevel 1 (
    echo  %AMARELO%!%RESET% Docker nao esta rodando - nada a parar.
    pause
    exit /b 0
)

REM -- Tem docker-compose.yml?
if not exist "docker-compose.yml" (
    echo  %VERMELHO%X docker-compose.yml nao encontrado%RESET%
    pause
    exit /b 1
)

REM -- Modo destruir tudo?
if "%~1"=="--tudo" goto :destruir
if "%~1"=="--all"  goto :destruir
if "%~1"=="-a"     goto :destruir

REM Modo normal: so para containers, preserva dados
echo  Parando containers...
docker compose down
if errorlevel 1 (
    echo  %VERMELHO%X Falha ao parar os servicos%RESET%
    pause
    exit /b 1
)
echo.
echo  %VERDE%OK%RESET% Servicos parados.
echo.
echo   Os dados ^(banco, uploads^) foram preservados.
echo   Use iniciar.bat para subir novamente.
echo.
echo   Para apagar tambem os volumes ^(perde tudo^):
echo     parar.bat --tudo
echo.
pause
exit /b 0

:destruir
echo  %VERMELHO%!! ATENCAO !!%RESET%
echo.
echo   Voce esta prestes a:
echo     - Parar todos os containers
echo     - Remover os volumes Docker
echo     - %VERMELHO%APAGAR PERMANENTEMENTE o banco de dados%RESET%
echo     - %VERMELHO%APAGAR PERMANENTEMENTE arquivos enviados%RESET%
echo.
set /p confirma="   Digite 'APAGAR' para confirmar: "
if not "!confirma!"=="APAGAR" (
    echo.
    echo   Cancelado. Nada foi alterado.
    pause
    exit /b 0
)

echo.
echo  Parando e removendo containers + volumes...
docker compose down -v
echo.
echo  %VERDE%OK%RESET% Tudo removido.
echo.
echo   Para reinstalar do zero, rode: instalar.bat
echo.
pause
exit /b 0
