@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Popular Banco com Dados Ficticios (Windows)
REM   Versao: 1.0 (Junho/2026)
REM
REM   Uso:
REM     popular-dados-teste.bat           Adiciona 10+ registros por modulo
REM     popular-dados-teste.bat --reset   Limpa e recria do zero (CUIDADO!)
REM ===================================================================

title Atlas - Popular Dados Teste

reg add HKCU\Console /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
for /f %%E in ('echo prompt $E ^| cmd') do set "ESC=%%E"
set "VERDE=%ESC%[92m"
set "AMARELO=%ESC%[93m"
set "VERMELHO=%ESC%[91m"
set "AZUL=%ESC%[94m"
set "RESET=%ESC%[0m"

cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Atlas - Popular Banco com Dados Ficticios%RESET%
echo  %AZUL%================================================================%RESET%
echo.

docker info >nul 2>&1
if errorlevel 1 (
    echo   %VERMELHO%X%RESET% Docker nao esta rodando.
    pause & exit /b 1
)

docker compose ps --services --status=running 2>nul | findstr "app" >nul
if errorlevel 1 (
    echo   %VERMELHO%X%RESET% Container 'app' nao esta rodando.
    echo     Inicie com: iniciar.bat
    pause & exit /b 1
)

REM Modo --reset
if "%~1"=="--reset" goto :modo_reset
goto :popular_dados

:modo_reset
echo   %AMARELO%!! MODO RESET !!%RESET%
echo.
echo     Isso vai:
echo       - %VERMELHO%APAGAR TODAS as tabelas%RESET% ^(migrate:fresh^)
echo       - Recriar do zero com seeders padrao ^(admin, geografia, perfis^)
echo       - Adicionar 10+ registros em cada modulo
echo.
set /p confirma="    Digite 'RESETAR' para confirmar: "
if not "!confirma!"=="RESETAR" (
    echo     Cancelado.
    pause & exit /b 0
)

echo.
echo   Recriando tabelas...
docker compose exec -T app php artisan migrate:fresh --force
if errorlevel 1 (
    echo   %VERMELHO%X%RESET% Falha no migrate:fresh
    pause & exit /b 1
)

echo.
echo   Rodando seeders padrao ^(admin + geografia + perfis^)...
docker compose exec -T app php artisan db:seed --force
if errorlevel 1 (
    echo   %VERMELHO%X%RESET% Falha no seed padrao
    pause & exit /b 1
)

:popular_dados
echo.
echo   Populando dados ficticios em cada modulo...
echo.

docker compose exec -T app php artisan db:seed --class=DadosFicticiosSeeder --force
if errorlevel 1 (
    echo.
    echo   %VERMELHO%X%RESET% Houve falha ao popular dados. Veja a saida acima.
    pause & exit /b 1
)

echo.
echo  %VERDE%================================================================%RESET%
echo  %VERDE%    Dados ficticios criados com sucesso!%RESET%
echo  %VERDE%================================================================%RESET%
echo.
echo   Agora voce pode acessar o sistema e testar todos os modulos:

set "FINAL_APP_PORT=8000"
for /f "tokens=2 delims==" %%i in ('findstr /B "APP_PORT=" .env 2^>nul') do set "FINAL_APP_PORT=%%i"
echo     %AZUL%http://localhost:!FINAL_APP_PORT!%RESET%
echo.
echo     Login: admin@atlas.local / Admin@123456
echo.
pause
