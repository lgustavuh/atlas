@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion

REM ===================================================================
REM   Atlas - Sistema de Backup Completo
REM   Versao: 1.0 (Junho/2026)
REM ===================================================================
REM
REM   O que faz backup:
REM     1. Dump do PostgreSQL (banco completo, formato SQL)
REM     2. Arquivos privados (atestados, advertencias, curriculos,
REM        biblioteca, fotos de colaboradores)
REM     3. Arquivos publicos do storage
REM     4. .env (com avisos por conter credenciais)
REM     5. Tudo empacotado em UM arquivo ZIP com timestamp
REM
REM   Uso:
REM     backup.bat              - Backup completo (com retencao auto)
REM     backup.bat --db         - So o banco
REM     backup.bat --arquivos   - So os arquivos de storage
REM     backup.bat --listar     - Lista backups existentes
REM     backup.bat --limpar     - Remove backups > 30 dias
REM ===================================================================

title Atlas - Backup

REM ANSI
reg add HKCU\Console /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
for /f %%E in ('echo prompt $E ^| cmd') do set "ESC=%%E"
set "VERDE=%ESC%[92m"
set "AMARELO=%ESC%[93m"
set "VERMELHO=%ESC%[91m"
set "AZUL=%ESC%[94m"
set "RESET=%ESC%[0m"

REM Garante diretorios
if not exist "backups" mkdir "backups"
if not exist "logs-instalacao" mkdir "logs-instalacao"

REM Argumento?
if "%~1"=="--listar"   goto :listar
if "%~1"=="--limpar"   goto :limpar
if "%~1"=="--db"       goto :backup_db_only
if "%~1"=="--arquivos" goto :backup_files_only
if "%~1"=="--help"     goto :ajuda
if "%~1"=="-h"         goto :ajuda
if "%~1"=="/?"         goto :ajuda

REM ==== Backup completo (modo padrao) ====
cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Atlas - Backup Completo%RESET%
echo  %AZUL%================================================================%RESET%
echo.

REM Pre-requisitos
docker info >nul 2>&1
if errorlevel 1 (
    echo  %VERMELHO%X Docker nao esta rodando.%RESET%
    pause & exit /b 1
)

docker compose ps --services --status=running 2>nul | findstr "postgres" >nul
if errorlevel 1 (
    echo  %VERMELHO%X Container PostgreSQL nao esta rodando.%RESET%
    echo    Inicie o sistema com: iniciar.bat
    pause & exit /b 1
)

REM Timestamp
for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "TIMESTAMP=%%i"
set "BACKUP_NAME=atlas-backup_%TIMESTAMP%"
set "BACKUP_TEMP=backups\_temp_%TIMESTAMP%"
set "BACKUP_FINAL=backups\%BACKUP_NAME%.zip"
set "LOG=logs-instalacao\backup_%TIMESTAMP%.log"

echo  Backup: %AZUL%%BACKUP_FINAL%%RESET%
echo  Log:    %AZUL%%LOG%%RESET%
echo.

mkdir "%BACKUP_TEMP%"
echo [!TIMESTAMP!] === Inicio do backup === > "%LOG%"

REM ---- 1. Dump do banco ----
echo  %AZUL%[1/5]%RESET% Fazendo dump do PostgreSQL...

REM Le DB_DATABASE e DB_USERNAME do .env (fallback para defaults)
set "DB_NAME=atlas"
set "DB_USER=etc_user"
for /f "tokens=2 delims==" %%i in ('findstr /B "DB_DATABASE=" .env 2^>nul') do set "DB_NAME=%%i"
for /f "tokens=2 delims==" %%i in ('findstr /B "DB_USERNAME=" .env 2^>nul') do set "DB_USER=%%i"

docker compose exec -T postgres pg_dump -U "!DB_USER!" -d "!DB_NAME!" --clean --if-exists --no-owner --no-privileges > "%BACKUP_TEMP%\database.sql" 2>>"%LOG%"
if errorlevel 1 (
    echo    %VERMELHO%X Falha no pg_dump. Veja %LOG%%RESET%
    rmdir /s /q "%BACKUP_TEMP%" 2>nul
    pause & exit /b 1
)

REM Tamanho do dump
for %%A in ("%BACKUP_TEMP%\database.sql") do set "DB_SIZE=%%~zA"
set /a DB_SIZE_KB=!DB_SIZE!/1024
echo    %VERDE%OK%RESET% database.sql ^(!DB_SIZE_KB! KB^)
echo [OK] Dump do banco: !DB_SIZE_KB! KB >> "%LOG%"

REM ---- 2. Arquivos privados (uploads) ----
echo  %AZUL%[2/5]%RESET% Copiando arquivos privados do storage...

if exist "storage\app\private" (
    xcopy /E /I /Q /Y "storage\app\private" "%BACKUP_TEMP%\storage-private" >>"%LOG%" 2>&1
    REM Conta arquivos copiados
    set ARQUIVOS_PRIV=0
    for /f %%c in ('dir /s /b /a:-d "%BACKUP_TEMP%\storage-private" 2^>nul ^| find /v /c ""') do set ARQUIVOS_PRIV=%%c
    echo    %VERDE%OK%RESET% !ARQUIVOS_PRIV! arquivo^(s^) privado^(s^)
    echo [OK] !ARQUIVOS_PRIV! arquivos privados copiados >> "%LOG%"
) else (
    echo    %AMARELO%!%RESET% Pasta storage\app\private nao existe ainda
    echo [AVISO] storage\app\private nao existe >> "%LOG%"
)

REM ---- 3. Arquivos publicos ----
echo  %AZUL%[3/5]%RESET% Copiando arquivos publicos do storage...

if exist "storage\app\public" (
    xcopy /E /I /Q /Y "storage\app\public" "%BACKUP_TEMP%\storage-public" >>"%LOG%" 2>&1
    set ARQUIVOS_PUB=0
    for /f %%c in ('dir /s /b /a:-d "%BACKUP_TEMP%\storage-public" 2^>nul ^| find /v /c ""') do set ARQUIVOS_PUB=%%c
    echo    %VERDE%OK%RESET% !ARQUIVOS_PUB! arquivo^(s^) publico^(s^)
    echo [OK] !ARQUIVOS_PUB! arquivos publicos copiados >> "%LOG%"
) else (
    echo    %AMARELO%!%RESET% Pasta storage\app\public nao existe ainda
)

REM ---- 4. .env ----
echo  %AZUL%[4/5]%RESET% Salvando .env ^(configuracao^)...

if exist ".env" (
    copy ".env" "%BACKUP_TEMP%\.env.backup" >nul
    echo    %VERDE%OK%RESET% .env salvo ^(contem senhas - mantenha o backup seguro!^)
    echo [OK] .env incluido no backup >> "%LOG%"
) else (
    echo    %AMARELO%!%RESET% .env nao encontrado
)

REM Cria README dentro do backup explicando como restaurar
(
    echo # Atlas - Backup
    echo.
    echo **Data do backup:** %date% %time%
    echo **Versao do sistema:** v1.3+
    echo.
    echo ## Conteudo
    echo.
    echo - `database.sql` - Dump completo do PostgreSQL ^(formato plain SQL^)
    echo - `storage-private/` - Atestados, advertencias, curriculos, biblioteca, fotos
    echo - `storage-public/` - Arquivos publicos do storage
    echo - `.env.backup` - Configuracoes ^(CONTEM SENHAS - manter seguro!^)
    echo.
    echo ## Como restaurar
    echo.
    echo Use o script `restaurar.bat` ^(Windows^) ou siga os passos manuais abaixo.
    echo.
    echo ### Passos manuais
    echo.
    echo 1. **Pare o sistema:** `parar.bat`
    echo 2. **Restaure o .env:** copie `.env.backup` para `.env` na raiz do projeto
    echo 3. **Suba o sistema:** `iniciar.bat`
    echo 4. **Restaure o banco:**
    echo    ```
    echo    docker compose exec -T postgres psql -U etc_user atlas ^< database.sql
    echo    ```
    echo 5. **Restaure arquivos:** copie `storage-private` e `storage-public` para `storage/app/`
    echo 6. **Limpe caches:** `docker compose exec app php artisan cache:clear`
    echo.
    echo Em caso de duvida, consulte a documentacao do projeto.
) > "%BACKUP_TEMP%\LEIA-ME.md"

REM ---- 5. Empacotar ZIP ----
echo  %AZUL%[5/5]%RESET% Compactando em ZIP...

powershell -NoProfile -Command "Compress-Archive -Path '%BACKUP_TEMP%\*' -DestinationPath '%BACKUP_FINAL%' -CompressionLevel Optimal -Force" >>"%LOG%" 2>&1
if errorlevel 1 (
    echo    %VERMELHO%X Falha ao criar ZIP. Conteudo em %BACKUP_TEMP%%RESET%
    pause & exit /b 1
)

REM Remove temp
rmdir /s /q "%BACKUP_TEMP%" 2>nul

REM Tamanho final
for %%A in ("%BACKUP_FINAL%") do set "FINAL_SIZE=%%~zA"
set /a FINAL_SIZE_MB=!FINAL_SIZE!/1048576
if !FINAL_SIZE_MB! LSS 1 (
    set /a FINAL_SIZE_KB=!FINAL_SIZE!/1024
    echo    %VERDE%OK%RESET% ZIP criado: !FINAL_SIZE_KB! KB
) else (
    echo    %VERDE%OK%RESET% ZIP criado: !FINAL_SIZE_MB! MB
)
echo [OK] Backup ZIP: !FINAL_SIZE! bytes >> "%LOG%"

REM Limpeza automatica de backups antigos (mantem ultimos 30 dias)
echo.
echo  %AZUL%Aplicando retencao automatica ^(30 dias^)...%RESET%
set DELETADOS=0
forfiles /p "backups" /m "atlas-backup_*.zip" /d -30 /c "cmd /c del @path" 2>nul && set DELETADOS=1
if !DELETADOS!==1 (
    echo    %VERDE%OK%RESET% Backups antigos removidos
) else (
    echo    %VERDE%OK%RESET% Nenhum backup antigo a remover
)

echo.
echo  %VERDE%================================================================%RESET%
echo  %VERDE%       BACKUP CONCLUIDO!%RESET%
echo  %VERDE%================================================================%RESET%
echo.
echo   Arquivo: %AZUL%%BACKUP_FINAL%%RESET%
echo   Log:     %AZUL%%LOG%%RESET%
echo.
echo   %AMARELO%IMPORTANTE:%RESET% mantenha este arquivo em local seguro
echo   ^(disco externo, OneDrive/Drive, NAS^). Ele contem senhas.
echo.
echo  %AZUL%Para listar todos os backups:%RESET%
echo     backup.bat --listar
echo.
echo  %AZUL%Para restaurar:%RESET%
echo     restaurar.bat %BACKUP_NAME%.zip
echo.
pause
exit /b 0

REM ===================================================================
REM   --db: backup somente do banco
REM ===================================================================
:backup_db_only
cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Backup somente do Banco de Dados%RESET%
echo  %AZUL%================================================================%RESET%
echo.

docker compose ps --services --status=running 2>nul | findstr "postgres" >nul
if errorlevel 1 (
    echo  %VERMELHO%X Container PostgreSQL nao esta rodando.%RESET%
    pause & exit /b 1
)

for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "TIMESTAMP=%%i"
set "BACKUP_DB=backups\db-only_%TIMESTAMP%.sql"

set "DB_NAME=atlas"
set "DB_USER=etc_user"
for /f "tokens=2 delims==" %%i in ('findstr /B "DB_DATABASE=" .env 2^>nul') do set "DB_NAME=%%i"
for /f "tokens=2 delims==" %%i in ('findstr /B "DB_USERNAME=" .env 2^>nul') do set "DB_USER=%%i"

echo  Salvando em: %AZUL%%BACKUP_DB%%RESET%
docker compose exec -T postgres pg_dump -U "!DB_USER!" -d "!DB_NAME!" --clean --if-exists --no-owner --no-privileges > "%BACKUP_DB%"
if errorlevel 1 (
    echo  %VERMELHO%X Falha no pg_dump%RESET%
    pause & exit /b 1
)

for %%A in ("%BACKUP_DB%") do set "SIZE=%%~zA"
set /a SIZE_KB=!SIZE!/1024
echo.
echo  %VERDE%OK%RESET% Backup do banco: %BACKUP_DB% ^(!SIZE_KB! KB^)
echo.
pause
exit /b 0

REM ===================================================================
REM   --arquivos: backup somente storage
REM ===================================================================
:backup_files_only
cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Backup somente dos Arquivos%RESET%
echo  %AZUL%================================================================%RESET%
echo.

for /f "delims=" %%i in ('powershell -NoProfile -Command "(Get-Date).ToString('yyyy-MM-dd_HHmmss')"') do set "TIMESTAMP=%%i"
set "BACKUP_FILES=backups\arquivos_%TIMESTAMP%.zip"

if not exist "storage\app" (
    echo  %VERMELHO%X Pasta storage\app nao existe%RESET%
    pause & exit /b 1
)

echo  Empacotando storage\app em: %AZUL%%BACKUP_FILES%%RESET%
powershell -NoProfile -Command "Compress-Archive -Path 'storage\app\*' -DestinationPath '%BACKUP_FILES%' -CompressionLevel Optimal -Force"
if errorlevel 1 (
    echo  %VERMELHO%X Falha ao criar ZIP%RESET%
    pause & exit /b 1
)

for %%A in ("%BACKUP_FILES%") do set "SIZE=%%~zA"
set /a SIZE_MB=!SIZE!/1048576
echo.
echo  %VERDE%OK%RESET% Backup criado: %BACKUP_FILES% ^(!SIZE_MB! MB^)
echo.
pause
exit /b 0

REM ===================================================================
REM   --listar
REM ===================================================================
:listar
cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Backups disponiveis%RESET%
echo  %AZUL%================================================================%RESET%
echo.

if not exist "backups\*.zip" if not exist "backups\*.sql" (
    echo  %AMARELO%!%RESET% Nenhum backup encontrado em backups\
    echo.
    echo  Crie o primeiro com: backup.bat
    echo.
    pause & exit /b 0
)

echo  Tipo            Tamanho     Data              Arquivo
echo  --------------- ----------- ----------------- ------------------------------------------
for %%F in (backups\*.zip backups\*.sql) do (
    set "name=%%~nxF"
    set "size=%%~zF"
    set /a size_kb=!size!/1024
    set "data=%%~tF"

    REM Detecta tipo pelo prefixo
    set "tipo=Completo"
    echo !name! | findstr /B "db-only_" >nul && set "tipo=Banco"
    echo !name! | findstr /B "arquivos_" >nul && set "tipo=Arquivos"
    echo !name! | findstr /B "pre-deploy_" >nul && set "tipo=Pre-deploy"

    echo  !tipo!     !size_kb! KB   !data!  !name!
)

echo.
pause
exit /b 0

REM ===================================================================
REM   --limpar
REM ===================================================================
:limpar
cls
echo.
echo  %AZUL%================================================================%RESET%
echo  %AZUL%    Limpar backups antigos%RESET%
echo  %AZUL%================================================================%RESET%
echo.

set /p dias="   Manter backups dos ultimos quantos dias? (default 30): "
if "!dias!"=="" set "dias=30"

echo.
echo  Removendo backups com mais de !dias! dias...
echo.

set "REMOVIDOS=0"
for /f %%F in ('forfiles /p "backups" /m "*.zip" /d -!dias! /c "cmd /c echo @file" 2^>nul') do (
    set /a REMOVIDOS+=1
    echo    Removendo %%F
    del "backups\%%~F" 2>nul
)
for /f %%F in ('forfiles /p "backups" /m "*.sql" /d -!dias! /c "cmd /c echo @file" 2^>nul') do (
    set /a REMOVIDOS+=1
    echo    Removendo %%F
    del "backups\%%~F" 2>nul
)

echo.
if !REMOVIDOS!==0 (
    echo  %VERDE%OK%RESET% Nenhum backup antigo encontrado
) else (
    echo  %VERDE%OK%RESET% !REMOVIDOS! backup^(s^) removido^(s^)
)
echo.
pause
exit /b 0

REM ===================================================================
REM   --help
REM ===================================================================
:ajuda
echo.
echo   %AZUL%backup.bat%RESET% - Sistema de backup do Atlas
echo.
echo   %AZUL%Uso:%RESET%
echo     backup.bat                Backup completo ^(banco + arquivos + .env^)
echo     backup.bat --db           Backup somente do banco PostgreSQL
echo     backup.bat --arquivos     Backup somente dos arquivos do storage
echo     backup.bat --listar       Lista todos os backups disponiveis
echo     backup.bat --limpar       Remove backups antigos ^(default 30 dias^)
echo     backup.bat --help         Mostra esta ajuda
echo.
echo   %AZUL%Local dos backups:%RESET%
echo     backups\atlas-backup_YYYY-MM-DD_HHMMSS.zip
echo.
echo   %AZUL%Retencao automatica:%RESET%
echo     Backups com mais de 30 dias sao removidos automaticamente
echo     no proximo backup completo.
echo.
echo   %AMARELO%IMPORTANTE:%RESET%
echo     Os backups contem o .env, que tem senhas. Mantenha-os em
echo     local seguro ^(disco externo, OneDrive/Drive, NAS^).
echo.
pause
exit /b 0
