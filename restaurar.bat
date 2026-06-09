@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Restauracao de Backup
REM   Versao: 1.0 (Junho/2026)
REM ===================================================================
REM
REM   Uso:
REM     restaurar.bat ARQUIVO.zip   Restaura backup completo
REM     restaurar.bat ARQUIVO.sql   Restaura somente dump SQL
REM     restaurar.bat               Lista backups e pede pra escolher
REM ===================================================================

title Atlas - Restaurar Backup

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
echo  %AZUL%    Atlas - Restauracao de Backup%RESET%
echo  %AZUL%================================================================%RESET%
echo.

REM Sem argumento: lista e pede pra escolher
set "ARQUIVO=%~1"
if "%ARQUIVO%"=="" goto :escolher

REM Argumento veio com caminho ou apenas nome?
if exist "%ARQUIVO%" (
    set "ARQUIVO_PATH=%ARQUIVO%"
) else if exist "backups\%ARQUIVO%" (
    set "ARQUIVO_PATH=backups\%ARQUIVO%"
) else (
    echo  %VERMELHO%X Arquivo nao encontrado: %ARQUIVO%%RESET%
    echo.
    echo  Tente: restaurar.bat ^(sem argumentos^) para listar opcoes
    pause & exit /b 1
)

goto :confirma_restauracao

:escolher
echo  Backups disponiveis em backups\:
echo.

if not exist "backups\*.zip" if not exist "backups\*.sql" (
    echo  %AMARELO%!%RESET% Nenhum backup encontrado.
    echo.
    echo  Crie um primeiro com: backup.bat
    pause & exit /b 1
)

set "INDICE=0"
for %%F in (backups\*.zip backups\*.sql) do (
    set /a INDICE+=1
    set "ARQ_!INDICE!=%%F"
    echo    [!INDICE!] %%~nxF
)

echo.
set /p escolha="   Digite o numero do backup a restaurar (ou 0 para cancelar): "
if "!escolha!"=="0" exit /b 0
if "!escolha!"=="" exit /b 0

set "ARQUIVO_PATH=!ARQ_%escolha%!"
if "!ARQUIVO_PATH!"=="" (
    echo  %VERMELHO%X Numero invalido%RESET%
    pause & exit /b 1
)

:confirma_restauracao
echo.
echo  Arquivo: %AZUL%!ARQUIVO_PATH!%RESET%
echo.
echo  %VERMELHO%!! ATENCAO !!%RESET%
echo.
echo  A restauracao vai:
echo    - Sobrescrever o banco de dados atual ^(DROP + recreate^)
echo    - Sobrescrever arquivos em storage\app\
echo    - Opcionalmente restaurar o .env ^(voce escolhe^)
echo.
echo  %VERMELHO%TODOS OS DADOS ATUAIS SERAO PERDIDOS!%RESET%
echo.
set /p confirma="   Digite 'RESTAURAR' para confirmar: "
if not "!confirma!"=="RESTAURAR" (
    echo.
    echo  Cancelado.
    pause & exit /b 0
)

REM Backup do estado atual antes de restaurar (precaucao)
echo.
echo  %AZUL%[1/5]%RESET% Fazendo snapshot do estado atual ^(seguranca^)...
echo.

docker compose ps --services --status=running 2>nul | findstr "postgres" >nul
if errorlevel 1 (
    echo    %VERMELHO%X Container PostgreSQL nao esta rodando.%RESET%
    echo    Inicie com: iniciar.bat
    pause & exit /b 1
)

for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "TIMESTAMP=%%i"
set "SNAPSHOT=backups\snapshot-pre-restauracao_%TIMESTAMP%.sql"

set "DB_NAME=atlas"
set "DB_USER=etc_user"
for /f "tokens=2 delims==" %%i in ('findstr /B "DB_DATABASE=" .env 2^>nul') do set "DB_NAME=%%i"
for /f "tokens=2 delims==" %%i in ('findstr /B "DB_USERNAME=" .env 2^>nul') do set "DB_USER=%%i"

docker compose exec -T postgres pg_dump -U "!DB_USER!" -d "!DB_NAME!" --clean --if-exists --no-owner --no-privileges > "!SNAPSHOT!" 2>nul
if errorlevel 1 (
    echo    %AMARELO%!%RESET% Falha no snapshot ^(banco vazio?^). Continuando...
) else (
    echo    %VERDE%OK%RESET% Snapshot salvo: !SNAPSHOT!
    echo       ^(use este se precisar voltar atras^)
)

REM Detecta tipo do arquivo
echo !ARQUIVO_PATH! | findstr /I "\.sql$" >nul
if not errorlevel 1 (
    REM Eh um dump SQL puro
    goto :restaura_sql_only
)

REM Eh ZIP - extrair primeiro
echo.
echo  %AZUL%[2/5]%RESET% Extraindo backup...

set "EXTRACT_DIR=backups\_restore_%TIMESTAMP%"
mkdir "!EXTRACT_DIR!" 2>nul

powershell -NoProfile -Command "Expand-Archive -Path '!ARQUIVO_PATH!' -DestinationPath '!EXTRACT_DIR!' -Force"
if errorlevel 1 (
    echo    %VERMELHO%X Falha ao extrair %RESET%
    pause & exit /b 1
)
echo    %VERDE%OK%RESET% Extraido em !EXTRACT_DIR!

REM Restaura banco
echo.
echo  %AZUL%[3/5]%RESET% Restaurando banco de dados...

if not exist "!EXTRACT_DIR!\database.sql" (
    echo    %VERMELHO%X database.sql nao encontrado no backup%RESET%
    rmdir /s /q "!EXTRACT_DIR!" 2>nul
    pause & exit /b 1
)

docker compose exec -T postgres psql -U "!DB_USER!" -d "!DB_NAME!" < "!EXTRACT_DIR!\database.sql" >nul 2>nul
if errorlevel 1 (
    echo    %AMARELO%!%RESET% Houve avisos na restauracao ^(normal, --if-exists^)
)
echo    %VERDE%OK%RESET% Banco restaurado

REM Restaura arquivos
echo.
echo  %AZUL%[4/5]%RESET% Restaurando arquivos do storage...

if exist "!EXTRACT_DIR!\storage-private" (
    if not exist "storage\app\private" mkdir "storage\app\private"
    xcopy /E /I /Q /Y "!EXTRACT_DIR!\storage-private" "storage\app\private" >nul
    echo    %VERDE%OK%RESET% Arquivos privados restaurados
)

if exist "!EXTRACT_DIR!\storage-public" (
    if not exist "storage\app\public" mkdir "storage\app\public"
    xcopy /E /I /Q /Y "!EXTRACT_DIR!\storage-public" "storage\app\public" >nul
    echo    %VERDE%OK%RESET% Arquivos publicos restaurados
)

REM .env (opcional)
if exist "!EXTRACT_DIR!\.env.backup" (
    echo.
    set /p restaura_env="   Restaurar o .env do backup tambem? (s/N): "
    if /i "!restaura_env!"=="s" (
        copy ".env" ".env.pre-restauracao" >nul 2>&1
        copy "!EXTRACT_DIR!\.env.backup" ".env" >nul
        echo    %VERDE%OK%RESET% .env restaurado ^(backup do anterior em .env.pre-restauracao^)
        echo    %AMARELO%!%RESET% Sera necessario reiniciar containers para .env entrar em efeito
    )
)

REM Pos-restauracao: limpar caches
echo.
echo  %AZUL%[5/5]%RESET% Limpando caches e revalidando...

docker compose exec -T app php artisan config:clear >nul 2>&1
docker compose exec -T app php artisan cache:clear >nul 2>&1
docker compose exec -T app php artisan view:clear >nul 2>&1
docker compose exec -T app php artisan route:clear >nul 2>&1
docker compose exec -T app php artisan queue:restart >nul 2>&1
echo    %VERDE%OK%RESET% Caches limpos, workers reiniciados

REM Limpa diretorio temporario
rmdir /s /q "!EXTRACT_DIR!" 2>nul

echo.
echo  %VERDE%================================================================%RESET%
echo  %VERDE%       RESTAURACAO CONCLUIDA!%RESET%
echo  %VERDE%================================================================%RESET%
echo.
echo   Snapshot pre-restauracao: %AZUL%!SNAPSHOT!%RESET%
echo   ^(use este para reverter se algo der errado^)
echo.
echo   Recomenda-se reiniciar os containers:
echo     parar.bat
echo     iniciar.bat
echo.
pause
exit /b 0

REM ===================================================================
REM   Restauracao de SQL puro
REM ===================================================================
:restaura_sql_only
echo.
echo  %AZUL%[2/3]%RESET% Restaurando banco de dados a partir de SQL...

docker compose exec -T postgres psql -U "!DB_USER!" -d "!DB_NAME!" < "!ARQUIVO_PATH!" >nul 2>nul
if errorlevel 1 (
    echo    %AMARELO%!%RESET% Houve avisos ^(normais para dumps com --if-exists^)
)
echo    %VERDE%OK%RESET% Banco restaurado

echo.
echo  %AZUL%[3/3]%RESET% Limpando caches...
docker compose exec -T app php artisan config:clear >nul 2>&1
docker compose exec -T app php artisan cache:clear >nul 2>&1
docker compose exec -T app php artisan queue:restart >nul 2>&1
echo    %VERDE%OK%RESET% Caches limpos

echo.
echo  %VERDE%================================================================%RESET%
echo  %VERDE%       RESTAURACAO DO BANCO CONCLUIDA!%RESET%
echo  %VERDE%================================================================%RESET%
echo.
echo   Snapshot pre-restauracao: %AZUL%!SNAPSHOT!%RESET%
echo.
pause
exit /b 0
