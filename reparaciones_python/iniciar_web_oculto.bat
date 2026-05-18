@echo off
setlocal

set "APP_DIR=%~dp0"
if not exist "%APP_DIR%web_app.py" (
    if exist "C:\Reparaciones\reparaciones_python\web_app.py" set "APP_DIR=C:\Reparaciones\reparaciones_python\"
)
if not exist "%APP_DIR%web_app.py" (
    if exist "C:\REPARACIONES\reparaciones_python\web_app.py" set "APP_DIR=C:\REPARACIONES\reparaciones_python\"
)
cd /d "%APP_DIR%"

set "PYTHON_EXE="
set "PYTHON_ARGS="

if exist "%APP_DIR%python_runtime\python.exe" set "PYTHON_EXE=%APP_DIR%python_runtime\python.exe"

if not defined PYTHON_EXE (
    where py >nul 2>nul
    if not errorlevel 1 (
        set "PYTHON_EXE=py"
        set "PYTHON_ARGS=-3"
    )
)

if not defined PYTHON_EXE (
    where python >nul 2>nul
    if not errorlevel 1 set "PYTHON_EXE=python"
)

if not defined PYTHON_EXE exit /b 1

for /f "tokens=5" %%p in ('netstat -ano ^| findstr ":8765" ^| findstr "LISTENING"') do taskkill /PID %%p /F >nul 2>nul
"%PYTHON_EXE%" %PYTHON_ARGS% -u web_app.py --no-browser > "%APP_DIR%reparaciones_error.log" 2>&1
