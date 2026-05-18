@echo off
setlocal

set "APP_DIR=%~dp0"
if not exist "%APP_DIR%app.py" (
    if exist "C:\Reparaciones\reparaciones_python\app.py" set "APP_DIR=C:\Reparaciones\reparaciones_python\"
)
if not exist "%APP_DIR%app.py" (
    if exist "C:\REPARACIONES\reparaciones_python\app.py" set "APP_DIR=C:\REPARACIONES\reparaciones_python\"
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

if not defined PYTHON_EXE (
    echo No se encontro Python instalado.
    echo Instale Python 3.10 o superior desde https://www.python.org/downloads/
    echo Durante la instalacion marque la opcion "Add python.exe to PATH".
    pause
    exit /b 1
)

"%PYTHON_EXE%" %PYTHON_ARGS% app.py
pause
