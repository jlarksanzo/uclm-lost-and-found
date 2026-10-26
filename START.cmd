@echo off
cd /d "%~dp0"
if exist "..\..\work\runtime\php\php.exe" (
  "..\..\work\runtime\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
) else (
  php artisan serve --host=127.0.0.1 --port=8000
)
pause
