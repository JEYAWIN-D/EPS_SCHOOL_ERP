@echo off
setlocal enabledelayedexpansion

echo ========================================================
echo             DASA EduERP - Deployment Script
echo ========================================================
echo.

:: Step 1: Build frontend assets
echo [1/5] Building frontend assets (Vite)...
call npm run build
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Frontend build failed.
    exit /b %ERRORLEVEL%
)

:: Step 2: Clear and optimize Laravel caches
echo.
echo [2/5] Clearing application caches...
call php artisan config:clear
call php artisan cache:clear
call php artisan route:clear
call php artisan view:clear

:: Step 3: Run database migrations on live database
echo.
echo [3/5] Running database migrations...
call php artisan migrate --force

:: Step 4: Check git status and commit
echo.
echo [4/5] Checking git status...
git status --short > "%TEMP%\git_status.tmp"
set /p GIT_CHANGES=<"%TEMP%\git_status.tmp"
del "%TEMP%\git_status.tmp"

if defined GIT_CHANGES (
    echo Staging and committing changes...
    git add .
    set COMMIT_MSG=%*
    if "!COMMIT_MSG!"=="" set COMMIT_MSG=Auto deploy update
    git commit -m "!COMMIT_MSG!"
) else (
    echo No new file changes to commit.
)

:: Step 5: Push to GitHub
echo.
echo [5/6] Pushing to GitHub (origin/main)...
git push origin main
if %ERRORLEVEL% NEQ 0 (
    echo [WARNING] Git push failed. Please check network connection or remote credentials.
) else (
    echo [SUCCESS] Git push completed successfully!
)

:: Step 6: Update Live VPS Server
echo.
echo [6/6] Syncing and updating live server (eps.dasatech.in)...
ssh -o BatchMode=yes -o StrictHostKeyChecking=accept-new root@187.127.150.113 "/var/www/dasa-eps-school/deploy_server.sh"
if %ERRORLEVEL% NEQ 0 (
    echo [INFO] SSH direct trigger skipped. Triggering via Live Deploy Webhook...
    curl.exe -s -X POST -H "X-Deploy-Token: dasa_eps_live_deploy_2026" https://eps.dasatech.in/api/deploy-webhook
    echo [SUCCESS] Live deploy webhook triggered successfully!
) else (
    echo [SUCCESS] Live server updated successfully via SSH!
)

echo.
echo ========================================================
echo        Deployment process finished successfully!
echo        Live URL: https://eps.dasatech.in
echo ========================================================
echo.
pause
