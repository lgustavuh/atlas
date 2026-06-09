@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Atualizar (Windows)
REM   Versao: 3.2 (Junho/2026) - com logging
REM ===================================================================

title Atlas - Atualizar v3.2

reg add HKCU\Console /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
for /f %%E in ('echo prompt $E ^| cmd') do set "ESC=%%E"
set "VERDE=%ESC%[92m"
set "AMARELO=%ESC%[93m"
set "VERMELHO=%ESC%[91m"
set "AZUL=%ESC%[94m"
set "RESET=%ESC%[0m"

if not exist "logs-instalacao" mkdir "logs-instalacao"

for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "TIMESTAMP=%%i"
set "LOG_FILE=logs-instalacao\update_%TIMESTAMP%.log"
echo [!TIMESTAMP!] === Inicio do update === > "%LOG_FILE%"

cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Atlas - Atualizar Sistema%RESET%
echo  %AZUL%================================================================%RESET%
echo.
echo  Log: %AZUL%%LOG_FILE%%RESET%
echo.

docker compose ps --services --status=running 2>nul | findstr "app" >nul
if errorlevel 1 (
    echo  %AMARELO%!%RESET% Containers parados. Subindo primeiro...
    docker compose up -d >>"%LOG_FILE%" 2>&1
    if errorlevel 1 (
        echo  %VERMELHO%X Falha ao subir containers. Veja %LOG_FILE%%RESET%
        pause & exit /b 1
    )
    echo   Aguardando inicializacao...
    timeout /t 5 /nobreak >nul
    echo.
)

echo  %AZUL%[1/6]%RESET% Atualizando dependencias PHP...
docker compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader >>"%LOG_FILE%" 2>&1
if errorlevel 1 goto :erro
echo  %VERDE%OK%RESET% Composer atualizado
echo.

echo  %AZUL%[2/6]%RESET% Atualizando dependencias JS...
docker compose exec -T app npm install --silent >>"%LOG_FILE%" 2>&1
if errorlevel 1 goto :erro
echo  %VERDE%OK%RESET% npm atualizado
echo.

echo  %AZUL%[3/6]%RESET% Aplicando migrations pendentes...
docker compose exec -T app php artisan migrate --force >>"%LOG_FILE%" 2>&1
if errorlevel 1 goto :erro
echo  %VERDE%OK%RESET% Migrations aplicadas
echo.

echo  %AZUL%[4/6]%RESET% Reiniciando workers da fila...
docker compose exec -T app php artisan queue:restart >>"%LOG_FILE%" 2>&1
echo  %VERDE%OK%RESET% Workers serao reciclados na proxima iteracao
echo.

echo  %AZUL%[5/6]%RESET% Limpando e recriando caches...
docker compose exec -T app php artisan config:clear >>"%LOG_FILE%" 2>&1
docker compose exec -T app php artisan cache:clear >>"%LOG_FILE%" 2>&1
docker compose exec -T app php artisan view:clear >>"%LOG_FILE%" 2>&1
docker compose exec -T app php artisan route:clear >>"%LOG_FILE%" 2>&1

REM Reinicia container app pra invalidar OPcache (sem isso, alteracoes
REM em middleware/PHP podem demorar 60s pra entrar em vigor)
echo   Recarregando PHP-FPM ^(invalida OPcache, mudancas refletem agora^)...
docker compose restart app >>"%LOG_FILE%" 2>&1
timeout /t 3 /nobreak >nul
echo  %VERDE%OK%RESET% PHP-FPM recarregado

findstr "APP_ENV=production" .env >nul
if not errorlevel 1 (
    echo   Detectado APP_ENV=production - regenerando caches otimizados...
    docker compose exec -T app php artisan config:cache >>"%LOG_FILE%" 2>&1
    docker compose exec -T app php artisan route:cache >>"%LOG_FILE%" 2>&1
    docker compose exec -T app php artisan view:cache >>"%LOG_FILE%" 2>&1
    docker compose exec -T app php artisan event:cache >>"%LOG_FILE%" 2>&1
    echo  %VERDE%OK%RESET% Caches de producao gerados
) else (
    echo  %VERDE%OK%RESET% Caches limpos ^(dev mode^)
)
echo.

echo  %AZUL%[6/6]%RESET% Recompilando assets de frontend...
docker compose exec -T app npm run build >>"%LOG_FILE%" 2>&1
if errorlevel 1 (
    echo  %AMARELO%!%RESET% Falha ao compilar assets ^(visual pode ficar quebrado^)
) else (
    echo  %VERDE%OK%RESET% Assets recompilados
)
echo.

echo  %VERDE%================================================================%RESET%
echo  %VERDE%       ATUALIZACAO CONCLUIDA COM SUCESSO!%RESET%
echo  %VERDE%================================================================%RESET%
echo.
echo   Acesse: %AZUL%http://localhost:8000%RESET%
echo   Log:    %AZUL%%LOG_FILE%%RESET%
echo.
echo [!TIMESTAMP!] === Update concluido === >> "%LOG_FILE%"
pause
exit /b 0

:erro
echo.
echo  %VERMELHO%================================================================%RESET%
echo  %VERMELHO%   X FALHA NA ATUALIZACAO%RESET%
echo  %VERMELHO%================================================================%RESET%
echo.
echo   Veja detalhes em: %AZUL%%LOG_FILE%%RESET%
echo.
echo [!TIMESTAMP!] === Update falhou === >> "%LOG_FILE%"
pause
exit /b 1
